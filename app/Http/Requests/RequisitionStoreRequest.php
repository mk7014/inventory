<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequisitionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.item_type'          => ['required', Rule::in(['product', 'cost'])],
            'items.*.daraz_account_id'   => ['nullable', 'exists:daraz_accounts,id'],
            'items.*.product_id'         => ['nullable', 'exists:products,id'],
            'items.*.order_id_daraz'     => ['nullable', 'string', 'max:120'],
            'items.*.quantity'           => ['nullable', 'integer', 'min:1'],
            'items.*.purchase_price'     => ['nullable', 'numeric', 'min:0.01'],
            'items.*.description'        => ['nullable', 'string', 'max:255'],
            'items.*.amount'             => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('items', []) as $i => $item) {
                $type = $item['item_type'] ?? null;

                if ($type === 'product') {
                    if (empty($item['daraz_account_id'])) {
                        $validator->errors()->add("items.$i.daraz_account_id", 'Daraz account is required for product items.');
                    }
                    if (empty($item['product_id'])) {
                        $validator->errors()->add("items.$i.product_id", 'Product is required for product items.');
                    }
                    if (empty($item['quantity'])) {
                        $validator->errors()->add("items.$i.quantity", 'Quantity is required for product items.');
                    }
                    if (empty($item['purchase_price'])) {
                        $validator->errors()->add("items.$i.purchase_price", 'Unit cost is required for product items.');
                    }
                } elseif ($type === 'cost') {
                    if (blank($item['description'] ?? null)) {
                        $validator->errors()->add("items.$i.description", 'Description is required for other cost items.');
                    }
                    if (empty($item['amount'])) {
                        $validator->errors()->add("items.$i.amount", 'Amount is required for other cost items.');
                    }
                }
            }
        });
    }
}
