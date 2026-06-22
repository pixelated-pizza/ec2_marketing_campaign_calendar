<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPreviewRequest;
use App\Http\Requests\ImportCommitRequest;
use App\Services\WSDImportService;

class WebsiteSaleDetailsImportController extends Controller
{
    public function __construct(protected WSDImportService $importService)
    {
    }

    public function preview(ImportPreviewRequest $request)
    {
        return response()->json(['rows' => $this->importService->preview($request->validated()['rows'])]);
    }

    public function commit(ImportCommitRequest $request)
    {
        return response()->json($this->importService->commit($request->validated()['rows']));
    }
}