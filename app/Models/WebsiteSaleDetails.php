<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSaleDetails extends Model
{
    protected $primaryKey = 'wsd_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'event_name',
        'channel_name',
        'start_date',
        'end_date',
        'terms_conditions',
        'mockup_banner_locations',
        'mockup_banner_img',
        'featured_products_sheet_url',
        'event_master_sheet_url',
        'run_sheet_url',
        'is_sku_list_to_feature',
        'ess',
        'cms_to_audit',
        'featured_banner_text',
        'sku_in_category_creative',
        'url_text',
    ];

    // websiteCampaign() relationship removed — no FK to website_campaigns anymore
}