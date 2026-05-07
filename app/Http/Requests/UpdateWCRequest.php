<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWCRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'campaign_type_id' => 'sometimes|uuid|exists:website_campaign_types,campaign_type_id',
            'section_id' => 'sometimes|uuid|exists:sections,section_id',
            'store_id' => 'sometimes|uuid|exists:stores,store_id',
            'is_applied_to_both_stores' => 'sometimes|boolean',
            'is_archived' => 'sometimes|boolean',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'campaign_type_id.uuid' => 'The campaign type ID must be a valid UUID.',
            'campaign_type_id.exists' => 'The selected campaign type does not exist.',
            'section_id.uuid' => 'The section ID must be a valid UUID.',
            'section_id.exists' => 'The selected section does not exist.',
            'store_id.uuid' => 'The store ID must be a valid UUID.',
            'store_id.exists' => 'The selected store does not exist.',
            'start_date.date' => 'The start date must be a valid date.',
            'end_date.date' => 'The end date must be a valid date.',
            'is_applied_to_both_stores.boolean' => 'The is applied to both stores field must be true or false.',
            'is_archived.boolean' => 'The is archived field must be true or false.',
        ];
    }
}
