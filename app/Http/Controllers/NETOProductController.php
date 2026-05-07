<?php

namespace App\Http\Controllers;

use App\Services\NetoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NETOProductController extends Controller
{
    public function __construct(protected NetoService $netoService) {}

    public function index(Request $request): JsonResponse
    {
        $skus = $request->filled('sku')
            ? array_map('trim', explode(',', $request->sku))
            : null;

        $items = $this->netoService->getAllProducts(50, $skus);

        $rows = collect($items)->map(function ($item) {

            $warehouses = collect($item['WarehouseQuantity'] ?? [])
                ->map(fn($wh) => [
                    'name' => $wh['WarehouseName'] ?? null,
                    'qty'  => $wh['Quantity'] ?? 0,
                ])
                ->values();

            return [
                'sku' => $item['SKU'] ?? null,
                'rrp' => $item['RRP'] ?? null,
                'website_price' => $item['DefaultPrice'] ?? null,
                'special_price' => $item['PromotionPrice'] ?? null,
                'stock' => $item['AvailableSellQuantity'] ?? null,
                'warehouses' => $warehouses,

                'stock_buffer' => $warehouses
                    ->firstWhere('name', 'Stock Buffer')['qty'] ?? 0,
            ];
        })->values();

        return response()->json([
            'total'   => $rows->count(),
            'columns' => ['SKU', 'RRP', 'Website Price', 'Special Price', 'Warehouse Stock'],
            'rows'    => $rows,
        ]);
    }
}
