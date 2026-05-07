<?php

namespace App\Services;

use App\Models\URLTextLandingPage;

use Illuminate\Support\Collection;

class URLTLPService
{
    public function all(): Collection
    {
        return URLTextLandingPage::get();
    }

    public function find(string $id): ?URLTextLandingPage
    {
        return URLTextLandingPage::find($id);
    }

    public function create(array $data): URLTextLandingPage
    {
        return URLTextLandingPage::create($data);
    }

    public function update(string $id, array $data): ?URLTextLandingPage
    {
        $urlTLP = URLTextLandingPage::find($id);
        if (!$urlTLP) {
            return null;
        }

        $urlTLP->update($data);
        return $urlTLP;
    }

    public function delete(string $id): bool
    {
        $urlTLP = URLTextLandingPage::find($id);
        if (!$urlTLP) {
            return false;
        }

        return (bool) $urlTLP->delete();
    }
}
