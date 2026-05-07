<?php

namespace App\Http\Controllers;

use App\Services\CategoryChannelService;
use Illuminate\Http\Request;

class CategoryChannelController extends Controller
{
    protected $service;

    public function __construct(CategoryChannelService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->all());
    }

    public function show(string $id)
    {
        $channel = $this->service->find($id);
        if (!$channel) {
            return response()->json(['message' => 'Channel not found'], 404);
        }
        return response()->json($channel);
    }
}
