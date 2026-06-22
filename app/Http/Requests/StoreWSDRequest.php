<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWSDRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_name' => 'required|string',
            'channel_name' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'terms_conditions' => 'nullable|string',
            'mockup_banner_locations' => 'nullable|string',
            'mockup_banner_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'event_master_sheet_url' => 'nullable|url',
            'run_sheet_url' => 'nullable|url',
            'is_sku_list_to_feature' => 'nullable|boolean',
            'featured_products_sheet_url' => 'nullable|url',
            'ess' => 'nullable|string',
            'cms_to_audit' => 'nullable|string',
            'sku_in_category_creative' => 'nullable|string',
            'featured_banner_text' => 'nullable|string',
            'url_text' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'event_name.required' => 'Event name is required.',
            'channel_name.required' => 'Channel is required.',
            'featured_products_sheet_url.url' => 'Must be a valid URL.',
            'event_master_sheet_url.url' => 'Event master sheet must be a valid URL.',
            'run_sheet_url.url' => 'Run sheet must be a valid URL.',
            'is_sku_list_to_feature.boolean' => 'The SKU list flag must be true or false.',
        ];
    }
}