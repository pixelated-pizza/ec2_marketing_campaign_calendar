<?php

namespace App\Http\Controllers;

use App\Models\XlsxImport;
use App\Services\XlsxImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class XlsxImportController extends Controller
{
    public function __construct(protected XlsxImportService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(
            XlsxImport::orderByDesc('created_at')
                ->get(['id', 'name', 'original_filename', 'sheet_name', 'row_count', 'created_at', 'updated_at'])
        );
    }

    /** Step 1: upload the workbook, get its sheet list. */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xlsm', 'max:51200'],
        ]);

        try {
            return response()->json($this->service->upload($request->file('file')));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** Step 2: look at the top rows of a sheet to choose the header row. */
    public function preview(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'sheet' => ['required', 'string'],
        ]);

        try {
            return response()->json($this->service->preview($token, $data['sheet']));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Could not read that sheet.'], 422);
        }
    }

    /** Step 3: parse the chosen sheet and store it. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:40'],
            'sheet' => ['required', 'string'],
            'header_row' => ['required', 'integer', 'min:1', 'max:1000'],
            'name' => ['required', 'string', 'max:255'],
            'fill_merged' => ['sometimes', 'boolean'],
        ]);

        try {
            $import = $this->service->import(
                $data['token'],
                $data['sheet'],
                (int) $data['header_row'],
                $data['name'],
                (bool) ($data['fill_merged'] ?? false)
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'The import failed. Check that the sheet contains data.'], 500);
        }

        return response()->json($import, 201);
    }

    public function show(XlsxImport $import): JsonResponse
    {
        return response()->json([
            'import' => $import,
            'rows' => $import->rows()->get(['id', 'data'])->map(fn($r) => [
                'id' => $r->id,
                'data' => $r->data,
            ]),
        ]);
    }

    public function update(Request $request, XlsxImport $import): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'headers' => ['sometimes', 'array'],
            'headers.*' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json($this->service->updateImport($import, $data));
    }

    public function destroy(XlsxImport $import): JsonResponse
    {
        $this->service->deleteImport($import);

        return response()->json(['deleted' => true]);
    }

    public function addRow(Request $request, XlsxImport $import): JsonResponse
    {
        $data = $request->validate([
            'data' => ['sometimes', 'nullable', 'array'],
        ]);

        $row = $this->service->addRow($import, $data['data'] ?? null);

        return response()->json(['id' => $row->id, 'data' => $row->data], 201);
    }

    public function bulkUpdate(Request $request, XlsxImport $import): JsonResponse
    {
        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:2000'],
            'rows.*.id' => ['required', 'integer'],
            'rows.*.data' => ['required', 'array'],
        ]);

        return response()->json(['updated' => $this->service->bulkUpdate($import, $data['rows'])]);
    }

    public function bulkDelete(Request $request, XlsxImport $import): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:20000'],
            'ids.*' => ['integer'],
        ]);

        return response()->json(['deleted' => $this->service->bulkDelete($import, $data['ids'])]);
    }

    public function sync(Request $request, XlsxImport $import): JsonResponse
    {
        $data = $request->validate([
            'rows' => ['present', 'array', 'max:' . XlsxImportService::MAX_ROWS],
            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.data' => ['sometimes', 'nullable', 'array'],
        ]);

        return response()->json(['ids' => $this->service->sync($import, $data['rows'])]);
    }
}
