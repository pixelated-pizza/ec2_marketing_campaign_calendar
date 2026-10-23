<?php

namespace App\Http\Controllers;

use App\Models\WeeklyCalendarLayout;
use App\Models\WeeklyCalendarSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeeklyCalendarSnapshotController extends Controller
{
    private const LAYOUT_KEY = 'weekly-calendar';

    public function showLayout(): JsonResponse
    {
        $layout = WeeklyCalendarLayout::where('layout_key', self::LAYOUT_KEY)->first();

        return response()->json([
            'workbook_data' => $layout?->workbook_data,
            'updated_at' => $layout?->updated_at,
        ]);
    }

    public function saveLayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workbook_data' => ['required', 'array'],
        ]);

        $layout = WeeklyCalendarLayout::updateOrCreate(
            ['layout_key' => self::LAYOUT_KEY],
            ['workbook_data' => $validated['workbook_data']],
        );

        return response()->json([
            'message' => 'Workbook layout saved.',
            'updated_at' => $layout->updated_at,
        ]);
    }

    public function indexSnapshots(): JsonResponse
    {
        $snapshots = WeeklyCalendarSnapshot::query()
            ->select(['id', 'name', 'source_filename', 'row_count', 'column_count', 'created_at'])
            ->latest('id')
            ->limit(100)
            ->get();

        return response()->json(['data' => $snapshots]);
    }

    public function createSnapshot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'source_filename' => ['nullable', 'string', 'max:255'],
            'workbook_data' => ['required', 'array'],
        ]);

        $sheets = $validated['workbook_data']['sheets'] ?? [];
        $firstSheetId = $validated['workbook_data']['sheetOrder'][0] ?? array_key_first($sheets);
        $firstSheet = $firstSheetId ? ($sheets[$firstSheetId] ?? []) : [];

        $snapshot = WeeklyCalendarSnapshot::create([
            'name' => $validated['name'],
            'source_filename' => $validated['source_filename'] ?? null,
            'row_count' => (int) ($firstSheet['rowCount'] ?? 0),
            'column_count' => (int) ($firstSheet['columnCount'] ?? 0),
            'workbook_data' => $validated['workbook_data'],
        ]);

        return response()->json([
            'message' => 'Snapshot created.',
            'data' => $snapshot->only(['id', 'name', 'source_filename', 'row_count', 'column_count', 'created_at']),
        ], 201);
    }

    public function showSnapshot(WeeklyCalendarSnapshot $snapshot): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $snapshot->id,
                'name' => $snapshot->name,
                'source_filename' => $snapshot->source_filename,
                'created_at' => $snapshot->created_at,
                'workbook_data' => $snapshot->workbook_data,
            ],
        ]);
    }

    public function restoreSnapshot(WeeklyCalendarSnapshot $snapshot): JsonResponse
    {
        return DB::transaction(function () use ($snapshot) {
            WeeklyCalendarLayout::updateOrCreate(
                ['layout_key' => self::LAYOUT_KEY],
                ['workbook_data' => $snapshot->workbook_data],
            );

            return response()->json([
                'message' => 'Snapshot restored.',
                'workbook_data' => $snapshot->workbook_data,
            ]);
        });
    }

    public function deleteSnapshot(WeeklyCalendarSnapshot $snapshot): JsonResponse
    {
        $snapshot->delete();

        return response()->json(['message' => 'Snapshot deleted.']);
    }
}
