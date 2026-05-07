<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NetoService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $apiUsername;

    public function __construct()
    {
        $this->apiUrl      = config('services.neto.api_url');
        $this->apiKey      = config('services.neto.api_key');
        $this->apiUsername = config('services.neto.api_username');
    }

    public function getProducts(int $page = 1, int $limit = 50, ?array $skus = null): array
    {
        $payload = [
            'OutputSelector' => [
                'SKU',
                'RRP',
                'DefaultPrice',
                'PromotionPrice',
                'AvailableSellQuantity',
                'WarehouseQuantity',
            ],
        ];

        if (!empty($skus)) {
            $payload['SKU'] = count($skus) === 1 ? $skus[0] : $skus;
        } else {
            $payload['Page']  = $page;
            $payload['Limit'] = $limit;
        }


        $response = Http::withHeaders([
            'NETOAPI_ACTION'   => 'GetItem',
            'NETOAPI_KEY'      => $this->apiKey,
            'NETOAPI_USERNAME' => $this->apiUsername,
            'Accept'           => 'application/json',
            'Content-Type'     => 'application/json',
        ])->post($this->apiUrl, ['Filter' => $payload]);


        $response->throw();

        return $response->json();
    }

    public function getAllProducts(int $limit = 50, ?array $skus = null): array
    {
        $all  = [];
        $page = 1;

        do {
            $data  = $this->getProducts($page, $limit, $skus);
            $items = $data['Item'] ?? [];
            $all   = array_merge($all, $items);
            $page++;
        } while (count($items) === $limit);

        return $all;
    }
}
