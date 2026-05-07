<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateArchivedWebsiteSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow all users to make this request, adjust if needed
        return true;
    }

    public function rules(): array
    {
        return [
            'ws_archive_id' => 'required|uuid|unique:archived_website_sales,ws_archive_id',
            'wc_id' => 'required|uuid|exists:website_campaigns,wc_id',
            'archived_at' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'ws_archive_id.required' => 'The archive ID is required.',
            'ws_archive_id.uuid' => 'The archive ID must be a valid UUID.',
            'ws_archive_id.unique' => 'The archive ID must be unique.',
            'wc_id.required' => 'The campaign ID is required.',
            'wc_id.uuid' => 'The campaign ID must be a valid UUID.',
            'wc_id.exists' => 'The campaign ID does not exist.',
            'archived_at.date' => 'The archived at field must be a valid date.',
        ];
    }
}
