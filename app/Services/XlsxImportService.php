<?php

namespace App\Services;

use App\Models\XlsxImport;
use App\Models\XlsxImportRow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Parses uploaded .xlsx files and stores one chosen sheet as an editable dataset.
 *
 * Flow: upload() keeps the file in a temp location and lists its sheets,
 * preview() shows the top rows so the header row can be picked,
 * import() parses the chosen sheet into xlsx_imports / xlsx_import_rows.
 */
class XlsxImportService
{
    public const MAX_ROWS = 20000;
    public const MAX_COLS = 200;
    public const MAX_CELL_LENGTH = 32000;
    private const TMP_DIR = 'xlsx-imports/tmp';
    private const TMP_TTL_HOURS = 24;

    // -----------------------------------------------------------------
    // Upload / preview
    // -----------------------------------------------------------------

    /** Store the upload temporarily and list its sheets. */
    public function upload(UploadedFile $file): array
    {
        $this->pruneTempFiles();

        $token = Str::random(40);
        $file->storeAs(self::TMP_DIR, $token . '.xlsx', 'local');

        try {
            $sheets = $this->listSheets($this->tempPath($token));
        } catch (\Throwable $e) {
            $this->forgetTemp($token);
            throw new InvalidArgumentException('That file could not be read as an Excel workbook.');
        }

        if (empty($sheets)) {
            $this->forgetTemp($token);
            throw new InvalidArgumentException('The workbook has no sheets.');
        }

        // Remember the original name next to the temp file
        Storage::disk('local')->put(
            self::TMP_DIR . '/' . $token . '.json',
            json_encode(['filename' => $file->getClientOriginalName()])
        );

        return [
            'token' => $token,
            'filename' => $file->getClientOriginalName(),
            'sheets' => $sheets,
        ];
    }

    /** Top rows of a sheet as a plain grid (used to pick the header row). */
    public function preview(string $token, string $sheetName, int $limit = 40): array
    {
        $grid = $this->readGrid($this->tempPath($token), $sheetName, $limit, false);

        $cols = 0;
        foreach ($grid as $row) {
            $cols = max($cols, $this->lastFilledIndex($row) + 1);
        }

        return [
            'columns' => $cols,
            'rows' => array_map(fn($r) => array_slice(array_pad($r, $cols, null), 0, $cols), $grid),
        ];
    }

    // -----------------------------------------------------------------
    // Import
    // -----------------------------------------------------------------

    public function import(
        string $token,
        string $sheetName,
        int $headerRow,
        string $name,
        bool $fillMerged = false
    ): XlsxImport {
        $path = $this->tempPath($token);
        $grid = $this->readGrid($path, $sheetName, self::MAX_ROWS + $headerRow, $fillMerged);

        if (count($grid) < $headerRow) {
            throw new InvalidArgumentException("Row {$headerRow} is beyond the end of the sheet.");
        }

        $headerCells = $grid[$headerRow - 1];
        $body = array_slice($grid, $headerRow);

        // Drop fully empty rows
        $body = array_values(array_filter($body, fn($r) => $this->lastFilledIndex($r) >= 0));

        // Column count = widest of header and data, capped
        $cols = $this->lastFilledIndex($headerCells) + 1;
        foreach ($body as $r) {
            $cols = max($cols, $this->lastFilledIndex($r) + 1);
        }
        $cols = min($cols, self::MAX_COLS);

        if ($cols === 0) {
            throw new InvalidArgumentException('No data found in that sheet.');
        }

        $headers = $this->buildHeaders($headerCells, $cols);

        $filename = null;
        $metaPath = self::TMP_DIR . '/' . $token . '.json';
        if (Storage::disk('local')->exists($metaPath)) {
            $filename = json_decode(Storage::disk('local')->get($metaPath), true)['filename'] ?? null;
        }

        $import = DB::transaction(function () use ($name, $filename, $sheetName, $headers, $body, $cols) {
            $import = XlsxImport::create([
                'name' => $name,
                'original_filename' => $filename,
                'sheet_name' => $sheetName,
                'headers' => $headers,
                'row_count' => count($body),
            ]);

            $now = now();
            foreach (array_chunk($body, 500, true) as $chunk) {
                $insert = [];
                foreach ($chunk as $i => $r) {
                    $insert[] = [
                        'xlsx_import_id' => $import->id,
                        'row_index' => $i,
                        'data' => json_encode($this->normaliseCells($r, $cols), JSON_UNESCAPED_UNICODE),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('xlsx_import_rows')->insert($insert);
            }

            return $import;
        });

        $this->forgetTemp($token);

        return $import;
    }

    // -----------------------------------------------------------------
    // Edit / delete
    // -----------------------------------------------------------------

    public function updateImport(XlsxImport $import, array $attrs): XlsxImport
    {
        if (isset($attrs['headers'])) {
            $cols = count($import->headers);
            $headers = array_slice(array_pad(array_values($attrs['headers']), $cols, ''), 0, $cols);
            $attrs['headers'] = $this->buildHeaders($headers, $cols);
        }

        $import->update($attrs);

        return $import->refresh();
    }

    public function addRow(XlsxImport $import, ?array $data = null): XlsxImportRow
    {
        return DB::transaction(function () use ($import, $data) {
            $max = XlsxImportRow::where('xlsx_import_id', $import->id)->max('row_index');
            $cols = count($import->headers);

            $row = XlsxImportRow::create([
                'xlsx_import_id' => $import->id,
                'row_index' => $max === null ? 0 : ((int) $max + 1),
                'data' => $this->normaliseCells($data ?? [], $cols),
            ]);

            $this->refreshCount($import);

            return $row;
        });
    }

    /** @param array<int, array{id:int, data:array}> $rows */
    public function bulkUpdate(XlsxImport $import, array $rows): int
    {
        $cols = count($import->headers);
        $updated = 0;

        DB::transaction(function () use ($import, $rows, $cols, &$updated) {
            foreach ($rows as $r) {
                $updated += XlsxImportRow::where('xlsx_import_id', $import->id)
                    ->where('id', $r['id'])
                    ->update([
                        'data' => json_encode($this->normaliseCells($r['data'], $cols), JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);
            }
        });

        return $updated;
    }

    /** @param int[] $ids */
    public function bulkDelete(XlsxImport $import, array $ids): int
    {
        $deleted = 0;

        DB::transaction(function () use ($import, $ids, &$deleted) {
            foreach (array_chunk($ids, 500) as $chunk) {
                $deleted += XlsxImportRow::where('xlsx_import_id', $import->id)
                    ->whereIn('id', $chunk)
                    ->delete();
            }
            $this->refreshCount($import);
        });

        return $deleted;
    }

    /**
     * Make the stored rows match the sheet's current structure.
     *
     * The sheet sends its rows in display order. A row with an id is an existing
     * row (kept and re-ordered, and its data refreshed when `data` is included);
     * a row without one is new (created from `data`);
     * any stored row that is not listed was deleted in the sheet.
     *
     * @param array<int, array{id:?int, data?:array}> $rows
     * @return int[] ids in the same order as $rows
     */
    public function sync(XlsxImport $import, array $rows): array
    {
        $cols = count($import->headers);

        return DB::transaction(function () use ($import, $rows, $cols) {
            $existing = XlsxImportRow::where('xlsx_import_id', $import->id)
                ->pluck('row_index', 'id')
                ->all();

            $seen = [];
            $resultIds = [];
            $reorder = [];

            foreach ($rows as $i => $r) {
                $id = $r['id'] ?? null;

                if ($id && isset($existing[$id]) && !isset($seen[$id])) {
                    $seen[$id] = true;
                    $resultIds[$i] = (int) $id;
                    if ((int) $existing[$id] !== $i) {
                        $reorder[$id] = $i;
                    }
                    // Edited cells travel with the sync when the sheet includes them
                    if (isset($r['data']) && is_array($r['data'])) {
                        XlsxImportRow::where('id', $id)->update([
                            'data' => json_encode($this->normaliseCells($r['data'], $cols), JSON_UNESCAPED_UNICODE),
                            'updated_at' => now(),
                        ]);
                    }
                    continue;
                }

                // New (or duplicated-by-insert) row
                $created = XlsxImportRow::create([
                    'xlsx_import_id' => $import->id,
                    'row_index' => $i,
                    'data' => $this->normaliseCells($r['data'] ?? [], $cols),
                ]);
                $seen[$created->id] = true;
                $resultIds[$i] = $created->id;
            }

            // Anything not listed has been deleted
            $toDelete = array_values(array_diff(array_keys($existing), array_keys($seen)));
            foreach (array_chunk($toDelete, 500) as $chunk) {
                XlsxImportRow::where('xlsx_import_id', $import->id)->whereIn('id', $chunk)->delete();
            }

            // Re-number moved rows with one CASE update per chunk
            foreach (array_chunk($reorder, 500, true) as $chunk) {
                // Values are ints we computed, so they are inlined (Postgres cannot infer
                // the type of a bound parameter in CASE ... THEN ?)
                $case = '';
                foreach ($chunk as $id => $index) {
                    $case .= ' WHEN ' . (int) $id . ' THEN ' . (int) $index;
                }
                $ids = array_map('intval', array_keys($chunk));
                $placeholders = implode(',', array_fill(0, count($ids), '?'));

                DB::update(
                    "UPDATE xlsx_import_rows SET row_index = CASE id{$case} END WHERE xlsx_import_id = ? AND id IN ({$placeholders})",
                    array_merge([$import->id], $ids)
                );
            }

            $this->refreshCount($import);

            ksort($resultIds);

            return array_values($resultIds);
        });
    }

    public function deleteImport(XlsxImport $import): void
    {
        $import->delete(); // rows cascade
    }

    // -----------------------------------------------------------------
    // Parsing internals
    // -----------------------------------------------------------------

    /** @return array<int, array{name:string, rows:int, columns:int}> */
    private function listSheets(string $path): array
    {
        $reader = IOFactory::createReader('Xlsx');

        return array_map(fn($s) => [
            'name' => $s['worksheetName'],
            'rows' => (int) $s['totalRows'],
            'columns' => (int) $s['totalColumns'],
        ], $reader->listWorksheetInfo($path));
    }

    /**
     * Read the first $maxRows rows of one sheet into a 0-indexed grid of plain values.
     * Only that sheet is loaded, so large multi-sheet workbooks stay cheap.
     */
    private function readGrid(string $path, string $sheetName, int $maxRows, bool $fillMerged): array
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(120);

        $reader = IOFactory::createReader('Xlsx');
        $reader->setLoadSheetsOnly([$sheetName]);
        $reader->setReadFilter(new class($maxRows, self::MAX_COLS) implements IReadFilter {
            public function __construct(private int $maxRows, private int $maxCols) {}

            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= $this->maxRows
                    && \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnAddress) <= $this->maxCols;
            }
        });

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();

        $lastRow = min($sheet->getHighestDataRow(), $maxRows);
        $lastCol = min(
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()),
            self::MAX_COLS
        );

        $grid = $lastRow > 0 && $lastCol > 0
            ? array_fill(0, $lastRow, array_fill(0, $lastCol, null))
            : [];

        // Only cells that actually exist in the file are visited
        foreach ($sheet->getCoordinates(false) as $coordinate) {
            [$c, $r] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::indexesFromString($coordinate);
            if ($r > $lastRow || $c > $lastCol) {
                continue;
            }
            $grid[$r - 1][$c - 1] = $this->cellValue($sheet->getCell($coordinate));
        }

        if ($fillMerged) {
            $this->fillMergedCells($sheet, $grid, $lastRow, $lastCol);
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $grid;
    }

    private function cellValue(Cell $cell): mixed
    {
        // Formulas: use the value Excel cached in the file; no recalculation needed
        $value = $cell->isFormula() ? $cell->getOldCalculatedValue() : $cell->getValue();

        if ($value instanceof RichText) {
            $value = $value->getPlainText();
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && !is_string($value) && Date::isDateTime($cell)) {
            $dt = Date::excelToDateTimeObject($value);

            return $dt->format('H:i:s') === '00:00:00' ? $dt->format('Y-m-d') : $dt->format('Y-m-d H:i:s');
        }

        if (is_float($value) && floor($value) === $value && abs($value) < 1e15) {
            return (int) $value;
        }

        if (is_string($value)) {
            $value = str_replace("\r\n", "\n", $value);
            return mb_substr($value, 0, self::MAX_CELL_LENGTH);
        }

        return is_scalar($value) ? $value : (string) $value;
    }

    private function fillMergedCells(Worksheet $sheet, array &$grid, int $lastRow, int $lastCol): void
    {
        foreach ($sheet->getMergeCells() as $range) {
            [[$c1, $r1], [$c2, $r2]] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::rangeBoundaries($range);
            $value = $grid[$r1 - 1][$c1 - 1] ?? null;
            if ($value === null) {
                continue;
            }
            for ($r = $r1; $r <= min($r2, $lastRow); $r++) {
                for ($c = $c1; $c <= min($c2, $lastCol); $c++) {
                    $grid[$r - 1][$c - 1] = $value;
                }
            }
        }
    }

    private function buildHeaders(array $cells, int $cols): array
    {
        $headers = [];
        $used = [];

        for ($i = 0; $i < $cols; $i++) {
            $label = trim((string) ($cells[$i] ?? ''));
            $label = preg_replace('/\s+/', ' ', $label);
            if ($label === '') {
                $label = 'Column ' . ($i + 1);
            }

            $base = $label;
            $n = 2;
            while (isset($used[mb_strtolower($label)])) {
                $label = "{$base} ({$n})";
                $n++;
            }
            $used[mb_strtolower($label)] = true;
            $headers[] = $label;
        }

        return $headers;
    }

    /** Pad/trim to $cols and keep only scalar values. */
    private function normaliseCells(array $cells, int $cols): array
    {
        $cells = array_values($cells);
        $out = [];

        for ($i = 0; $i < $cols; $i++) {
            $v = $cells[$i] ?? null;
            if (is_string($v)) {
                $v = mb_substr($v, 0, self::MAX_CELL_LENGTH);
                $v = $v === '' ? null : $v;
            } elseif (!is_null($v) && !is_scalar($v)) {
                $v = null;
            }
            $out[] = $v;
        }

        return $out;
    }

    private function lastFilledIndex(array $row): int
    {
        for ($i = count($row) - 1; $i >= 0; $i--) {
            if ($row[$i] !== null && $row[$i] !== '') {
                return $i;
            }
        }

        return -1;
    }

    private function refreshCount(XlsxImport $import): void
    {
        $import->update([
            'row_count' => XlsxImportRow::where('xlsx_import_id', $import->id)->count(),
        ]);
    }

    // -----------------------------------------------------------------
    // Temp files
    // -----------------------------------------------------------------

    private function assertToken(string $token): void
    {
        if (!preg_match('/^[A-Za-z0-9]{40}$/', $token)) {
            throw new InvalidArgumentException('Invalid upload token.');
        }
    }

    private function tempPath(string $token): string
    {
        $this->assertToken($token);
        $relative = self::TMP_DIR . '/' . $token . '.xlsx';

        if (!Storage::disk('local')->exists($relative)) {
            throw new InvalidArgumentException('The uploaded file has expired. Please upload it again.');
        }

        return Storage::disk('local')->path($relative);
    }

    private function forgetTemp(string $token): void
    {
        $this->assertToken($token);
        Storage::disk('local')->delete([
            self::TMP_DIR . '/' . $token . '.xlsx',
            self::TMP_DIR . '/' . $token . '.json',
        ]);
    }

    private function pruneTempFiles(): void
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subHours(self::TMP_TTL_HOURS)->getTimestamp();

        foreach ($disk->files(self::TMP_DIR) as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
            }
        }
    }
}
