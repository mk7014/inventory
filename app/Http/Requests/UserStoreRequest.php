<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $this->user()?->can($user ? 'users.update' : 'users.create') === true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $passwordRule = $userId ? ['nullable', 'string', 'min:8'] : ['required', 'string', 'min:8'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password' => $passwordRule,
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
