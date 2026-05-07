<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteCampaignType extends Model
{
    public $primaryKey = 'campaign_type_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = [
        'campaign_type_name',
    ];

    public function campaigns()
    {
        return $this->hasMany(WebsiteCampaign::class, 'website_campaign_type_id', 'campaign_type_id');
    }
}
