<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\WebsitePromoService;
use App\Http\Requests\CreateWebsitePromoRequest;


class WebsitePromoController extends Controller
{
    protected $service;

    public function __construct(WebsitePromoService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->all());
    }

    public function show(string $id)
    {
        $archived_promotion = $this->service->find($id);
        if (!$archived_promotion) {
            return response()->json(['error' => 'Archived promotion not found'], 404);
        }
        return response()->json($archived_promotion);
    }

    public function store(CreateWebsitePromoRequest $request)
    {
        $data = $request->all();
        $archived_promotion = $this->service->create($data);
        return response()->json($archived_promotion, 201);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $archived_promotion = $this->service->update($id, $data);
        if (!$archived_promotion) {
            return response()->json(['error' => 'Archived promotion not found'], 404);
        }
        return response()->json($archived_promotion);
    }

    public function destroy(string $id)
    {
        if (!$this->service->delete($id)) {
            return response()->json(['error' => 'Archived promotion not found'], 404);
        }
        return response()->json(null, 204);
    }

    public function archive(string $id) {
        $archived_promotion = $this->service->archive($id);
        if (!$archived_promotion) {
            return response()->json(['error' => 'Archived promotion not found'], 404);
        }
        return response()->json($archived_promotion);
    }

//     public function unarchive(string $id)
//     {
//         $archived_promotion = $this->service->unarchive($id);
//         if (!$archived_promotion) {
//             return response()->json(['error' => 'Archived promotion not found'], 404);
//         }
//         return response()->json($archived_promotion);
//     }
}