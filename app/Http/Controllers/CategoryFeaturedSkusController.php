<?php

namespace App\Http\Controllers;

use App\Models\CategoryFeaturedSkus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\NetoService;

class CategoryFeaturedSkusController extends Controller
{
    public function index(): JsonResponse
    {
        $skus = CategoryFeaturedSkus::orderBy('created_at', 'desc')->get();

        return response()->json($skus);
    }

    public function sync(): JsonResponse
    {
        $featured = CategoryFeaturedSkus::all();

        if ($featured->isEmpty()) {
            return response()->json(['message' => 'Nothing to sync']);
        }

        $skus        = $featured->pluck('sku')->toArray();
        $netoService = app(NetoService::class);
        $netoData    = collect($netoService->getProducts(skus: $skus)['Item'] ?? [])
            ->keyBy('SKU');

        foreach ($featured as $item) {
            $live = $netoData->get($item->sku);

            if (!$live) continue;

            $oldWebsitePrice  = (float) $item->website_price;
            $newWebsitePrice  = (float) ($live['DefaultPrice'] ?? 0);
            $priceChange      = $newWebsitePrice - $oldWebsitePrice;

            $item->update([
                'rrp'           => $live['RRP']                   ?? $item->rrp,
                'website_price' => $newWebsitePrice,
                'special_price' => $live['PromotionPrice']        ?? $item->special_price,
                'qty'           => $live['AvailableSellQuantity'] ?? $item->qty,
                'price_change'  => $priceChange != 0
                    ? number_format($priceChange, 2)
                    : $item->price_change,
            ]);
        }

        return response()->json(['message' => 'Sync complete']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku'                  => 'required|string|max:25',
            'category_name'        => 'nullable|string|max:255',
            'block_id'             => 'nullable|string|max:255',
            'block_name'           => 'nullable|string|max:255',
            'identifier'           => 'nullable|string|max:255',
            'type'                 => 'nullable|string|max:255',
            'website'              => 'nullable|string|max:255',
            'product_landing_page' => 'nullable|string|max:255',
            'creative_location'    => 'nullable|string|max:255',
            'landing_page'         => 'nullable|string|max:255',
            'note'                 => 'nullable|string',
            'qty'                  => 'nullable|integer',
            'rrp'                  => 'nullable|numeric',
            'website_price'        => 'nullable|numeric',
            'special_price'        => 'nullable|numeric',
        ]);

        $sku = CategoryFeaturedSkus::create($validated);

        return response()->json($sku, 201);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items'                          => 'required|array|min:1',
            'items.*.sku'                    => 'required|string|max:25',
            'items.*.category_name'          => 'nullable|string|max:255',
            'items.*.block_id'               => 'nullable|string|max:255',
            'items.*.block_name'             => 'nullable|string|max:255',
            'items.*.identifier'             => 'nullable|string|max:255',
            'items.*.type'                   => 'nullable|string|max:255',
            'items.*.website'                => 'nullable|string|max:255',
            'items.*.product_landing_page'   => 'nullable|string|max:255',
            'items.*.creative_location'      => 'nullable|string|max:255',
            'items.*.landing_page'           => 'nullable|string|max:255',
            'items.*.price_change'           => 'nullable|string|max:255',
            'items.*.note'                   => 'nullable|string',
            'items.*.qty'                    => 'nullable|integer',
            'items.*.rrp'                    => 'nullable|numeric',
            'items.*.website_price'          => 'nullable|numeric',
            'items.*.special_price'          => 'nullable|numeric',
        ]);

        $rows = collect($validated['items'])->map(function ($item) {
            return array_merge($item, [
                'sku_featured_id' => (string) \Illuminate\Support\Str::uuid(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        })->toArray();

        CategoryFeaturedSkus::insert($rows);

        return response()->json(['message' => 'Bulk insert successful', 'count' => count($rows)], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $item = CategoryFeaturedSkus::findOrFail($id);

        $validated = $request->validate([
            'category_name'        => 'nullable|string|max:255',
            'block_id'             => 'nullable|string|max:255',
            'block_name'           => 'nullable|string|max:255',
            'identifier'           => 'nullable|string|max:255',
            'type'                 => 'nullable|string|max:255',
            'website'              => 'nullable|string|max:255',
            'product_landing_page' => 'nullable|string|max:255',
            'creative_location'    => 'nullable|string|max:255',
            'landing_page'         => 'nullable|string|max:255',
            'note'                 => 'nullable|string',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy(string $id): JsonResponse
    {
        $sku = CategoryFeaturedSkus::findOrFail($id);
        $sku->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function deleteAll(): JsonResponse
    {
        try {
            $count = CategoryFeaturedSkus::count();

            CategoryFeaturedSkus::query()->delete();

            return response()->json([
                'message' => "Deleted {$count} featured SKUs successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete all featured SKUs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
