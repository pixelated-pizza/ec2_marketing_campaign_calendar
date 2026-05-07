<?php

namespace App\Services;
use App\Models\Section;
use Illuminate\Support\Collection;

class SectionService
{
    public function all(): Collection {
        return Section::get();
    }

    public function find(string $id): ?Section {
        return Section::find($id);
    }

    public function create(array $data): Section {
        return Section::create($data);
    } 
}
