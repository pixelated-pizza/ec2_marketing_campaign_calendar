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
            'wc_id' => ['sometimes', 'uuid', 'exists:website_campaigns,wc_id'],

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

    public function messages(): array
    {
        return [
            'featured_products_sheet_url.url' => 'Must be a valid URL',
            'event_master_sheet_url.url' => 'Event master sheet must be a valid URL.',
            'run_sheet_url.url' => 'Run sheet must be a valid URL.',
            'is_sku_list_to_feature.boolean' => 'The SKU list flag must be true or false.',
        ];
    }
}

