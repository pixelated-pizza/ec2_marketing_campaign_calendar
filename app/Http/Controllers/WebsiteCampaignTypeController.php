<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CampaignTypeService;

class WebsiteCampaignTypeController extends Controller
{
    protected $service;

    public function __construct(CampaignTypeService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->all());
    }
}
