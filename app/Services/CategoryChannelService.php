<?php

namespace App\Services;

use App\Models\CategoryChannel;
use Illuminate\Support\Collection;

class CategoryChannelService
{

    public function all(): Collection
    {
        return CategoryChannel::orderBy('name')->get();
    }

    public function find(string $id): ?CategoryChannel
    {
        return CategoryChannel::find($id);
    }

    public function create(array $data): CategoryChannel
    {
        return CategoryChannel::create($data);
    }

    public function update(string $id, array $data): ?CategoryChannel
    {
        $channel = CategoryChannel::find($id);
        if (!$channel) return null;
        $channel->update($data);
        return $channel;
    }

    public function delete(string $id): bool
    {
        $channel = CategoryChannel::find($id);
        if (!$channel) return false;
        return (bool) $channel->delete();
    }
}
