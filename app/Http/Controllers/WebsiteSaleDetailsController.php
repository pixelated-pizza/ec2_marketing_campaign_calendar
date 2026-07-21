<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWSDRequest;
use App\Http\Requests\UpdateWSDRequest;
use App\Services\WSDService;
use Illuminate\Http\Request;

class WebsiteSaleDetailsController extends Controller
{
    public function __construct(protected WSDService $service) {}

    public function index()
    {
        return response()->json($this->service->all());
    }

    public function show(string $id)
    {
        $wsd = $this->service->find($id);

        if (!$wsd) {
            return response()->json(['message' => 'Website Sale Details not found.'], 404);
        }

        return response()->json($wsd);
    }

    public function store(StoreWSDRequest $request)
    {
        try {
            return response()->json($this->service->upsert($request->validated()));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateWSDRequest $request, string $id)
    {
        $wsd = $this->service->update($id, $request->validated());

        if (!$wsd) {
            return response()->json(['message' => 'Website Sale Details not found.'], 404);
        }

        return response()->json($wsd);
    }

    public function destroy(string $id)
    {
        if (!$this->service->delete($id)) {
            return response()->json(['message' => 'Website Sale Details not found.'], 404);
        }

        return response()->json(['message' => 'Deleted.']);
    }

    public function blank(Request $request)
    {
        $request->validate([
            'event_name' => 'required|string',
            'channel_name' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        return response()->json([
            'message' => 'Blank Website Sale Details template generated.',
            'data' => $this->service->blankRecord(
                $request->input('event_name'),
                $request->input('channel_name'),
                $request->input('start_date'),
                $request->input('end_date'),
            ),
        ]);
    }

    public function uploadImage(Request $request, string $wsdId)
    {
        $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120']);

        try {
            return response()->json(['success' => true, 'url' => $this->service->uploadImage($wsdId, $request->file('image'))]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteImage(string $wsdId)
    {
        try {
            $this->service->deleteImage($wsdId);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroyAll()
    {
        try {
            $counts = $this->service->deleteAll();
            return response()->json([
                'message' => "Deleted {$counts['wsd']} WSD, {$counts['website_campaigns']} website campaigns, {$counts['campaigns']} campaigns.",
                'counts'  => $counts,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
