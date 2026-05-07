<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SectionService;

class SectionController extends Controller
{
    protected $service;

    public function __construct(SectionService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->all());
    }
}
