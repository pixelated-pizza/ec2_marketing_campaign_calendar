<?php

namespace App\Http\Controllers;

use App\Models\SheetMirror;
use App\Models\SheetMirrorRow;
use App\Services\SheetMirrorSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SheetMirrorController extends Controller
{
    public function index(string $key)
    {
        $mirror = SheetMirror::where('key', $key)->firstOrFail();

        return response()->json([
            'headers' => $mirror->headers ?? [],
            'rows' => $mirror->rows()->get()->map(fn ($r) => $r->data)->values(),
            'last_synced_at' => $mirror->last_synced_at,
        ]);
    }

    public function embedUrl(string $key)
    {
        $mirror = SheetMirror::where('key', $key)->firstOrFail();
        return response()->json([
            'url' => "https://docs.google.com/spreadsheets/d/{$mirror->spreadsheet_id}/edit",
        ]);
    }

    public function pull(string $key)
    {
        $mirror = SheetMirror::where('key', $key)->firstOrFail();
        return response()->json((new SheetMirrorSync($mirror))->pullFromSheet());
    }

    public function push(string $key)
    {
        $mirror = SheetMirror::where('key', $key)->firstOrFail();
        return response()->json((new SheetMirrorSync($mirror))->pushToSheet());
    }

    // New: save edits made in the Luckysheet grid straight to MySQL.
    public function save(Request $request, string $key)
    {
        $mirror = SheetMirror::where('key', $key)->firstOrFail();

        $data = $request->validate([
            'headers'   => 'required|array',
            'headers.*' => 'nullable|string',
            'rows'      => 'required|array',
            'rows.*'    => 'array',
        ]);

        DB::transaction(function () use ($mirror, $data) {
            $mirror->rows()->delete();

            $records = [];
            foreach ($data['rows'] as $i => $row) {
                $records[] = [
                    'sheet_mirror_id' => $mirror->id,
                    'row_index' => $i,
                    'data' => json_encode($row),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach (array_chunk($records, 500) as $chunk) {
                SheetMirrorRow::insert($chunk);
            }

            $mirror->update([
                'headers' => array_values(array_filter($data['headers'], fn ($h) => $h !== null && $h !== '')),
            ]);
        });

        return response()->json(['saved_rows' => count($data['rows'])]);
    }

    public function webhook(Request $request, string $key)
    {
        if ($request->header('X-Sync-Secret') !== config('services.google.wsd_webhook_secret')) {
            abort(403);
        }
        $mirror = SheetMirror::where('key', $key)->firstOrFail();
        return response()->json((new SheetMirrorSync($mirror))->pullFromSheet());
    }
}