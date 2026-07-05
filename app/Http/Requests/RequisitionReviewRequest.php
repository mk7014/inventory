<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequisitionReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'rejected', 'hold'])],
            'approved_amount' => ['nullable', 'numeric', 'min:0.01', 'required_if:status,approved'],
            'admin_note' => ['nullable', 'string', 'max:2000', 'required_if:status,rejected'],
        ];
    }
}
