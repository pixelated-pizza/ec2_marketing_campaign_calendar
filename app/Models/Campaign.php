<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

class Campaign extends Model
{
    use HasUuids;

    protected $primaryKey = 'campaign_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $appends = ['channel_name'];

    protected $fillable = [
        'channel_id',
        'name',
        'background_color',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime:Y-m-d H:i',
        'end_date' => 'datetime:Y-m-d H:i',
    ];
    public function channel()
    {
        return $this->belongsTo(CategoryChannel::class, 'channel_id', 'channel_id');
    }

    public function getChannelNameAttribute()
    {
        return $this->channel->name ?? null;
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }
}
