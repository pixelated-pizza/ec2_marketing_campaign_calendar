<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateArchiveCreationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow all users to make this request, adjust if needed
        return true;
    }

    public function rules(): array
    {
        return [
            'promo_name' => 'required|string|max:255',
            'wc_id' => 'nullable|uuid|exists:website_campaigns,wc_id',
            'description' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'does_include_parts' => 'required|boolean',
            'does_include_marketplace_products' => 'required|boolean',
            'creatives' => 'nullable|array',
            'coupon_label' => 'nullable|string|max:255',
            'coupon_code' => 'nullable|string|max:100',
            'website_store' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive,pending',
            'updated_at' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'promo_id.required' => 'The promo ID is required.',
            'promo_name.required' => 'The promo name is required.',
            'does_include_parts.boolean' => 'Invalid value for parts inclusion.',
            'does_include_marketplace_products.boolean' => 'Invalid value for marketplace products inclusion.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'end_date.required' => 'End date is required.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date cannot be before start date.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be active, inactive, or pending.',
        ];
    }
}
