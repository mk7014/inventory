<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $this->user()?->can($role ? 'roles.update' : 'roles.create') === true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name'          => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($roleId)],
            'description'   => ['nullable', 'string', 'max:1000'],
            'status'        => ['required', Rule::in(['active', 'inactive'])],
            'permissions'   => ['array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function permissionIds(): array
    {
        return array_map('intval', $this->input('permissions', []));
    }
}
