<?php

namespace App\Services;

use Illuminate\Support\Collection;

use App\Models\SKUFeatureCategory;

class SkuFeatureService
{
    public function all(): Collection
    {
        return SKUFeatureCategory::get();
    }

    public function find(string $id): ?SKUFeatureCategory
    {
        return SKUFeatureCategory::find($id);
    }

    public function create(array $data): SKUFeatureCategory
    {
        return SKUFeatureCategory::create($data);
    }

    public function update(string $id, array $data): ?SKUFeatureCategory
    {
        $skuFeatureCategory = SKUFeatureCategory::find($id);
        if (!$skuFeatureCategory) {
            return null;
        }

        $skuFeatureCategory->update($data);
        return $skuFeatureCategory;
    }

    public function delete(string $id): bool
    {
        $skuFeatureCategory = SKUFeatureCategory::find($id);
        if (!$skuFeatureCategory) {
            return false;
        }

        return (bool) $skuFeatureCategory->delete();
    }
}
