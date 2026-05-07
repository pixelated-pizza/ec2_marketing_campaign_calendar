<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivedWebsiteSale extends Model
{
    public $timestamps = false;

    public $primaryKey = 'ws_archive_id';

    protected $keyType = 'string';

    protected $fillable = [
        'ws_archive_id',
        'wc_id',
        'archived_at',
    ];

    public function website_campaign()
    {
        return $this->belongsTo(WebsiteCampaign::class, 'wc_id', 'wc_id');
    }

}
