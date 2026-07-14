<?php

namespace App\Http\Requests;

use App\Models\StockAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', Rule::in(['increase', 'decrease'])],
            'quantity' => ['required', 'integer', 'min:1'],
            // The reason list differs per direction, so it is only validated once
            // the direction itself is known to be valid.
            'reason' => [
                'required',
                Rule::in(array_keys(StockAdjustment::reasonsFor($this->input('type') === 'increase' ? 'increase' : 'decrease'))),
            ],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.in' => 'Pick a reason that matches the adjustment type.',
        ];
    }
}
