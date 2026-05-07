<?php

namespace App\Services;

use App\Models\CategoryTitle;

use Illuminate\Support\Collection;

class CategoryTitleService
{
    public function all(): Collection
    {
        return CategoryTitle::get();
    }

    public function find(string $id): ?CategoryTitle
    {
        return CategoryTitle::find($id);
    }

    public function create(array $data): CategoryTitle
    {
        return CategoryTitle::create($data);
    }

    public function update(string $id, array $data): ?CategoryTitle
    {
        $categoryTitle = CategoryTitle::find($id);
        if (!$categoryTitle) {
            return null;
        }

        $categoryTitle->update($data);
        return $categoryTitle;
    }

    public function delete(string $id): bool
    {
        $categoryTitle = CategoryTitle::find($id);
        if (!$categoryTitle) {
            return false;
        }

        return (bool) $categoryTitle->delete();
    }
    
}
