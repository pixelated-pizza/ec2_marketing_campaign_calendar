<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

class WebsiteCampaign extends Model
{
    use HasUuids;

    protected $primaryKey = 'wc_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'website_campaign_key',
        'name',
        'campaign_type_id',
        'section_id',
        'is_applied_to_both_stores',
        'store_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime:Y-m-d H:i:s',
        'end_date' => 'datetime:Y-m-d H:i:s',
    ];


    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'section_id');
    }

    public function campaignType()
    {
        return $this->belongsTo(WebsiteCampaignType::class, 'campaign_type_id', 'campaign_type_id');
    }

    protected static function booted()
{
    static::creating(function ($campaign) {
        if (!$campaign->start_date) {
            $campaign->start_date = Carbon::now()->setHour(9)->setMinute(0)->setSecond(0);
        }

        if (!$campaign->end_date) {
            $campaign->end_date = Carbon::now()->setHour(23)->setMinute(59)->setSecond(59);
        }
    });
}

}
