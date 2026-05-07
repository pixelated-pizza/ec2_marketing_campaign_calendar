<?php

namespace App\Services;

use App\Models\FeaturedCategoryBannerText;

use Illuminate\Support\Collection;

class FCBTService
{
    public function all(): Collection
    {
        return FeaturedCategoryBannerText::get();
    }

    public function find(string $id): ?FeaturedCategoryBannerText
    {
        return FeaturedCategoryBannerText::find($id);
    }


    public function create(array $data): FeaturedCategoryBannerText
    {
        return FeaturedCategoryBannerText::create($data);
    }

    public function update(string $id, array $data): ?FeaturedCategoryBannerText
    {
        $fcbt = FeaturedCategoryBannerText::find($id);
        if (!$fcbt) {
            return null;
        }

        $fcbt->update($data);
        return $fcbt;
    }

    public function delete(string $id): bool
    {
        $fcbt = FeaturedCategoryBannerText::find($id);
        if (!$fcbt) {
            return false;
        }

        return (bool) $fcbt->delete();
    }
}
