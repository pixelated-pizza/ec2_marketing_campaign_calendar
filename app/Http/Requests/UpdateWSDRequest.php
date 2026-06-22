<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWSDRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_name' => ['sometimes', 'string'],
            'channel_name' => ['sometimes', 'string'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'terms_conditions' => ['sometimes', 'nullable', 'string'],
            'mockup_banner_locations' => ['sometimes', 'nullable', 'string'],
            'ess' => ['sometimes', 'nullable', 'string'],
            'cms_to_audit' => ['sometimes', 'nullable', 'string'],
            'featured_products_sheet_url' => ['sometimes', 'nullable', 'url'],
            'event_master_sheet_url' => ['sometimes', 'nullable', 'url'],
            'run_sheet_url' => ['sometimes', 'nullable', 'url'],
            'sku_in_category_creative' => ['sometimes', 'nullable', 'string'],
            'featured_banner_text' => ['sometimes', 'nullable', 'string'],
            'url_text' => ['sometimes', 'nullable', 'string'],
            'is_sku_list_to_feature' => ['sometimes', 'boolean'],
        ];
    }
}