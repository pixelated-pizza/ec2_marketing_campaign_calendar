<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWSDRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'wc_id' => 'required|uuid|exists:website_campaigns,wc_id',
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
            'wc_id.required' => 'Campaign ID is required.',
            'wc_id.uuid' => 'Campaign ID must be an UUID.',
            'wc_id.exists' => 'Campaign ID does not exist.',
            'featured_products_sheet_url.url' => 'Must be a valid Google Spreadsheet URL',
            'event_master_sheet_url.url' => 'Event master sheet must be a valid URL.',
            'run_sheet_url.url' => 'Run sheet must be a valid URL.',
            'is_sku_list_to_feature.boolean' => 'The SKU list flag must be true or false.',
            'mockup_banner_img.image' => 'The banner must be a valid image file.',
            'mockup_banner_img.mimes' => 'Accepted formats: jpeg, png, jpg, gif, webp.',
            'mockup_banner_img.max'=> 'Banner image must not exceed 5MB.',
        ];
    }
}
