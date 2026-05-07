<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StoreService;

class StoreController extends Controller
{
    protected $service;

    public function __construct(StoreService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->all());
    }
}
