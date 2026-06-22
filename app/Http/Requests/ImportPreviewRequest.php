<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => 'required|array|min:1',
            'rows.*.store_name' => 'nullable|string',
            'rows.*.name' => 'nullable|string',
            'rows.*.start_date' => 'nullable|string',
            'rows.*.end_date' => 'nullable|string',
            'rows.*.fields' => 'nullable|array',
        ];
    }
}