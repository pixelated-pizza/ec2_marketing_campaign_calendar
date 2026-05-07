<?php

namespace App\Services;
use App\Models\WebsiteCampaignType;
use Illuminate\Support\Collection;

class CampaignTypeService
{
    public function all(): Collection
    {
        return WebsiteCampaignType::get(); 
    }
}
