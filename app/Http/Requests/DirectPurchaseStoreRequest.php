<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DirectPurchaseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'          => ['nullable', 'exists:users,id'],
            'supplier_id'          => ['nullable', 'exists:suppliers,id'],
            'warehouse_id'         => ['nullable', 'exists:warehouses,id'],
            'payment_type'         => ['required', Rule::in(['advance', 'due'])],
            'purchase_date'        => ['required', 'date'],
            'invoice_number'       => ['nullable', 'string', 'max:120'],
            'reference_number'     => ['nullable', 'string', 'max:120'],
            'remarks'              => ['nullable', 'string', 'max:1000'],

            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'exists:products,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
            'items.*.unit'         => ['nullable', 'string', 'max:40'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.discount'     => ['nullable', 'numeric', 'min:0'],
            'items.*.tax'          => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('items', []) as $i => $item) {
                $qty      = (int) ($item['quantity'] ?? 0);
                $price    = (float) ($item['purchase_price'] ?? 0);
                $discount = (float) ($item['discount'] ?? 0);

                // A per-line discount cannot exceed the line's gross value.
                if ($discount > $qty * $price) {
                    $validator->errors()->add("items.$i.discount", 'Discount cannot exceed the line value.');
                }
            }
        });
    }
}
