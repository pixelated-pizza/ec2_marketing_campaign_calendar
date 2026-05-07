<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWCRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'campaign_type_id' => 'required|uuid|exists:website_campaign_types,campaign_type_id',
            'section_id' => 'required|uuid|exists:sections,section_id',
            'store_id' => 'required|uuid|exists:stores,store_id',
            'is_applied_to_both_stores' => 'sometimes|boolean',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date',
        ];
    }
}
