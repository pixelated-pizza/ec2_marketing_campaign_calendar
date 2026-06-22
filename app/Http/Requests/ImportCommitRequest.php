<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCommitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => 'required|array|min:1',
            'rows.*.event_name' => 'required|string',
            'rows.*.channel_name' => 'required|string',
            'rows.*.start_date' => 'nullable|string',
            'rows.*.end_date' => 'nullable|string',
            'rows.*.fields' => 'nullable|array',
        ];
    }
}