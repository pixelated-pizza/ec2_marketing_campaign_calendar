<?php

namespace App\Http\Controllers;

use App\Models\WebsiteSaleDetails;
use App\Services\WsdSheetSync;
use Illuminate\Http\Request;

class WsdSheetController extends Controller
{
    public function index()
    {
        return response()->json(WebsiteSaleDetails::orderByDesc('start_date')->get());
    }

    public function embedUrl()
    {
        $id = config('services.google.wsd_spreadsheet_id');
        return response()->json([
            'url' => "https://docs.google.com/spreadsheets/d/{$id}/edit?rm=embedded",
        ]);
    }

    public function pull(WsdSheetSync $sync)
    {
        return response()->json($sync->pullFromSheet());
    }

    public function push(WsdSheetSync $sync)
    {
        return response()->json($sync->pushToSheet());
    }

    public function webhook(Request $request, WsdSheetSync $sync)
    {
        if ($request->header('X-Sync-Secret') !== config('services.google.wsd_webhook_secret')) {
            abort(403);
        }
        return response()->json($sync->pullFromSheet());
    }
}