<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WebsiteService;
use App\Http\Requests\CreateWCRequest;
use App\Http\Requests\UpdateWCRequest;

class WebsiteCampaignController extends Controller
{
    protected $service;

    public function __construct(WebsiteService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->all());
    }

    public function store(CreateWCRequest $request)
    {

        $validated = $request->validated();

        $campaign = $this->service->create($validated);

        return response()->json($campaign, 201);
    }

    public function update(UpdateWCRequest $request, string $id)
    {
        $validated = $request->validated();

        $campaign = $this->service->update($id, $validated);

        if (!$campaign) {
            return response()->json(['message' => 'Website Campaign not found'], 404);
        }

        return response()->json($campaign);
    }

    public function destroy(string $id)
    {
        if (!$this->service->delete($id)) {
            return response()->json(['message' => 'Website Campaign not found'], 404);
        }

        return response()->json(['message' => 'Deleted']);
    }

    public function archive_campaign(string $id)
    {
        $campaign = $this->service->archive($id);

        if (!$campaign) {
            return response()->json(['message' => 'Website Campaign not found'], 404);
        }

        return response()->json($campaign);
    }
}
