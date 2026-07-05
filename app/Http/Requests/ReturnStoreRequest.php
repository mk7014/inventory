<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sale_id' => ['required', 'exists:sales,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'condition' => ['required', Rule::in(['good', 'damaged'])],
            'return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
