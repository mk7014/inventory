<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountStoreRequest;
use App\Models\DarazAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DarazAccountController extends Controller
{
    public function index(): View
    {
        return view('accounts.index', ['accounts' => DarazAccount::query()->latest()->paginate(20)]);
    }

    public function store(AccountStoreRequest $request): RedirectResponse
    {
        DarazAccount::create($request->validated());

        return back()->with('success', 'Account saved.');
    }

    public function update(AccountStoreRequest $request, DarazAccount $account): RedirectResponse
    {
        $account->update($request->validated());

        return back()->with('success', 'Account updated.');
    }
}
