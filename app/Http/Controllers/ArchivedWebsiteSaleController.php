<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\ArchivedWebsiteSaleService;
use App\Http\Requests\CreateArchivedWebsiteSaleRequest;

class ArchivedWebsiteSaleController extends Controller
{
    protected $service;

    public function __construct(ArchivedWebsiteSaleService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->all());
    }

    public function show(string $id)
    {
        $archived_website_sale = $this->service->find($id);
        if (!$archived_website_sale) {
            return response()->json(['error' => 'Archived website sale not found'], 404);
        }
        return response()->json($archived_website_sale);
    }

    public function store(CreateArchivedWebsiteSaleRequest $request)
    {
        $data = $request->all();
        $archived_website_sale = $this->service->create($data);
        return response()->json($archived_website_sale, 201);
    }

    public function unarchive(string $id)
    {
        $archived_website_sale = $this->service->unarchive($id);
        if (!$archived_website_sale) {
            return response()->json(['error' => 'Archived website sale not found'], 404);
        }
        return response()->json($archived_website_sale);
    }
}
