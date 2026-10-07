<?php

namespace App\Http\Controllers;

use App\Models\SheetSnapshot;
use Illuminate\Http\Request;

class SheetSnapshotController extends Controller
{
    // GET /api/sheet_snapshots/{key}
    public function show(string $key)
    {
        $snapshot = SheetSnapshot::where('key', $key)->first();
        return response()->json(['payload' => $snapshot?->payload]);
    }

    // PUT /api/sheet_snapshots/{key}
    public function update(Request $request, string $key)
    {
        $request->validate(['payload' => 'required|array']);

        $payload = $request->input('payload');
        $encodedSize = strlen(json_encode($payload));
        
        $limitBytes = 200 * 1024 * 1024; 
        if ($encodedSize > $limitBytes) {
            return response()->json([
                'message' => 'This sheet is too large to save in one go ('
                    . round($encodedSize / 1024 / 1024, 1) . 'MB). '
                    . 'Try importing in smaller batches, or raise max_allowed_packet further.',
            ], 413);
        }

        SheetSnapshot::updateOrCreate(
            ['key' => $key],
            ['payload' => $payload],
        );

        return response()->json(['saved' => true]);
    }
}