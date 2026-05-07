<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\URLTLPService;

class URLTextLandingPageController extends Controller
{
    protected $service;

    public function __construct(URLTLPService $service)
    {
        $this->service = $service; 
    }

    public function index()
    {
        return response()->json($this->service->all());
    }

    public function show(string $id)
    {
        $landingPage = $this->service->find($id);

        if (!$landingPage) {
            return response()->json(['message' => 'URL Text Landing Page not found'], 404);
        }

        return response()->json($landingPage);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $landingPage = $this->service->create($data);
        
        if (!$landingPage) {
            return response()->json(['message' => 'Failed to create URL Text Landing Page'], 500);
        }

        return response()->json($landingPage, 201);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->all();

        $landingPage = $this->service->update($id, $data);

        if (!$landingPage) {
            return response()->json(['message' => 'URL Text Landing Page not found'], 404);
        }

        return response()->json($landingPage);
    }

    public function destroy(string $id)
    {
        if (!$this->service->delete($id)) {
            return response()->json(['message' => 'URL Text Landing Page not found'], 404);
        }

        return response()->json(['message' => 'Deleted']);
    }


   
}
