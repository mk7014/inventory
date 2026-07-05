<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'daraz_account_id' => ['required', 'exists:daraz_accounts,id'],
            'product_id' => ['nullable', 'exists:products,id', 'required_if:source,stock'],
            'product_name' => ['nullable', 'string', 'max:255', 'required_without:product_id'],
            'selling_price' => ['required', 'numeric', 'min:0.01'],
            'quantity' => ['required', 'integer', 'min:1'],
            'source' => ['required', Rule::in(['new_purchase', 'stock'])],
            'sold_date' => ['required', 'date'],
        ];
    }
}
