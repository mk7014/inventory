<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Every authenticated (active) user manages their own expenses.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category'     => ['required', 'string', Rule::in(Expense::CATEGORIES)],
            'description'  => ['required', 'string', 'max:255'],
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'note'         => ['nullable', 'string', 'max:1000'],
        ];
    }
}
