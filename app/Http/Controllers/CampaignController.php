<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CampaignService;
use App\Http\Requests\CreateCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;

class CampaignController extends Controller
{
    protected $service;

    public function __construct(CampaignService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->all());
    }

    public function store(CreateCampaignRequest $request)
    {

        $validated = $request->validated();

        $campaign = $this->service->create($validated);

        return response()->json($campaign, 201);
    }

    public function update(UpdateCampaignRequest $request, string $id)
    {
        $validated = $request->validated();

        $campaign = $this->service->update($id, $validated);

        if (!$campaign) {
            return response()->json(['message' => 'Campaign not found'], 404);
        }

        return response()->json($campaign);
    }

    public function destroy(string $id)
    {
        if (!$this->service->delete($id)) {
            return response()->json(['message' => 'Campaign not found'], 404);
        }

        return response()->json(['message' => 'Deleted']);
    }
}
