<?php

// app/Services/SheetMirrorSync.php
namespace App\Services;

use App\Models\SheetMirror;
use App\Models\SheetMirrorRow;
use Revolution\Google\Sheets\Facades\Sheets;
use Illuminate\Support\Facades\DB;

class SheetMirrorSync
{
    public function __construct(protected SheetMirror $mirror) {}

    /**
     * Pull: Google Sheet -> MySQL. Full replace, since columns/rows
     * can change shape freely and there's no guaranteed stable key.
     */
    public function pullFromSheet(): array
    {
        $range = Sheets::spreadsheet($this->mirror->spreadsheet_id)
            ->sheet($this->mirror->sheet_name)
            ->range('A1:ZZ')
            ->all(); // raw 2D array: [ [header...], [row1...], [row2...] ]

        $header = $range[0] ?? [];
        $dataRows = array_slice($range, 1);

        DB::transaction(function () use ($header, $dataRows) {
            $this->mirror->rows()->delete();

            $records = [];
            foreach ($dataRows as $i => $row) {
                // Skip fully-blank rows (Sheets often pads trailing empties)
                if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                    continue;
                }

                $keyed = [];
                foreach ($header as $c => $colName) {
                    if ($colName === '' || $colName === null) continue; // ignore unnamed columns
                    $keyed[$colName] = $row[$c] ?? null;
                }

                $records[] = [
                    'sheet_mirror_id' => $this->mirror->id,
                    'row_index' => $i,
                    'data' => json_encode($keyed),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach (array_chunk($records, 500) as $chunk) {
                SheetMirrorRow::insert($chunk);
            }

            $this->mirror->update([
                'headers' => array_values(array_filter($header, fn ($h) => $h !== '' && $h !== null)),
                'last_synced_at' => now(),
            ]);
        });

        return ['synced_rows' => count($dataRows), 'headers' => $this->mirror->headers];
    }

    /**
     * Push: MySQL -> Google Sheet. Rebuilds the sheet from current
     * headers + rows. Use when something outside Sheets changed the data
     * (rare in this design, since editing happens in Sheets itself).
     */
    public function pushToSheet(): array
    {
        $headers = $this->mirror->headers ?? [];
        $rows = $this->mirror->rows()->get()->map(function ($row) use ($headers) {
            return array_map(fn ($h) => $row->data[$h] ?? '', $headers);
        })->toArray();

        Sheets::spreadsheet($this->mirror->spreadsheet_id)
            ->sheet($this->mirror->sheet_name)
            ->clear()
            ->update(array_merge([$headers], $rows));

        return ['pushed_rows' => count($rows)];
    }
}