<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Collection;

class StoreService
{
    public function all(): Collection {

        return Store::get(); 
    }
}
