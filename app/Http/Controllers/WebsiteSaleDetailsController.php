<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateWSDRequest;
use App\Services\WSDService;

use Illuminate\Http\Request;

class WebsiteSaleDetailsController extends Controller
{

    protected $service;

    public function __construct(WSDService $service)
    {
        $this->service = $service;
    }
    public function index()
    {
        return response()->json($this->service->all());
    }

    public function store(CreateWSDRequest $request)
    {
        $data = $request->validated();

        $wsd = $this->service->create($data);

        if (!$wsd) {
            return response()->json(['message' => 'Failed to create Website Sale Details'], 500);
        }

        return response()->json($wsd, 201);
    }

    public function show(string $id)
    {
        $wsd = $this->service->find($id);

        if (!$wsd) {
            return response()->json(['message' => 'Website Sale Details not found'], 404);
        }

        return response()->json($wsd);
    }

    public function update(CreateWSDRequest $request, string $id)
    {
        $data = $request->validated();

        $wsd = $this->service->update($id, $data);

        if (!$wsd) {
            return response()->json(['message' => 'Website Sale Details not found'], 404);
        }

        return response()->json($wsd);
    }

    public function destroy(string $id)
    {
        if (!$this->service->delete($id)) {
            return response()->json(['message' => 'Website Sale Details not found'], 404);
        }

        return response()->json(['message' => 'Deleted']);
    }

    public function blank(string $wc_id)
    {
        $blank = $this->service->blank_record($wc_id);

        return response()->json([
            'message' => 'Blank Website Sale Details template generated',
            'data' => $blank
        ]);
    }

    public function uploadImage(Request $request, string $wc_id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        try {
            $url = $this->service->uploadImage($wc_id, $request->file('image'));

            return response()->json([
                'success' => true,
                'url'     => $url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function deleteImage(string $wc_id)
    {
        try {
            $this->service->deleteImage($wc_id);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
