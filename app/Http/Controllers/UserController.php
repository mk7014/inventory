<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->with('role')->latest()->paginate(20),
            'roles' => Role::query()->where('status', 'active')->orderByDesc('is_system')->orderBy('name')->get(),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return back()->with('success', 'User saved.');
    }

    public function update(UserStoreRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        if (blank($data['password'] ?? null)) {
            $data = Arr::except($data, ['password']);
        }
        $user->update($data);

        return back()->with('success', 'User updated.');
    }
}
