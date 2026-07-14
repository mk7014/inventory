<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DeletionService;
use App\Support\VoidedUsers;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private DeletionService $deletionService,
        private AuditService $audit,
    ) {
    }

    public function index(): View
    {
        $users = User::query()->with('role')->latest()->paginate(20);

        // Blast radius for the delete dialog, resolved once per row.
        $impacts = $users->mapWithKeys(
            fn (User $user) => [$user->id => $this->deletionService->userImpact($user)]
        );

        return view('users.index', [
            'users' => $users,
            'impacts' => $impacts,
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

    /**
     * Void a user: keep every record for audit, but drop them out of the books.
     * Their sales leave revenue, their purchases leave the cost basis, their expenses
     * leave operating costs, and their wallet leaves the fund/spend totals. They can
     * no longer log in. Reversible — unlike destroy().
     *
     * Stock is deliberately untouched: the goods physically moved, and voiding is an
     * accounting decision, not a warehouse correction.
     */
    public function void(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($actor->id === $user->id) {
            return back()->withErrors(['void' => 'You cannot void your own account.']);
        }

        if ($user->isAdmin() && $this->adminCount() <= 1) {
            return back()->withErrors(['void' => 'This is the last administrator — voiding it would lock everyone out.']);
        }

        if ($user->isVoided()) {
            return back()->with('success', $user->name.' is already voided.');
        }

        $user->forceFill(['voided_at' => now(), 'voided_by' => $actor->id])->save();
        VoidedUsers::flush();

        $this->audit->record('user.voided', $user, ['voided_at' => null], ['voided_at' => $user->voided_at]);

        return back()->with('success', $user->name.' was voided — their sales, purchases and expenses no longer count anywhere.');
    }

    /** Put a voided user back into the books. */
    public function restore(Request $request, User $user): RedirectResponse
    {
        if (! $user->isVoided()) {
            return back()->with('success', $user->name.' is already active.');
        }

        $user->forceFill(['voided_at' => null, 'voided_by' => null])->save();
        VoidedUsers::flush();

        $this->audit->record('user.restored', $user, null, ['voided_at' => null]);

        return back()->with('success', $user->name.' was restored — their records count again.');
    }

    /**
     * Hard-purge a user and everything they own. Irreversible — the caller must
     * retype the user's name to confirm, and two lockout guards apply first.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        // Deleting yourself, or the last remaining admin, leaves nobody able to
        // administer the system — an unrecoverable lockout, not a policy choice.
        $blocked = match (true) {
            $actor->id === $user->id => 'You cannot delete your own account.',
            $user->isAdmin() && $this->adminCount() <= 1 => 'This is the last administrator — deleting it would lock everyone out.',
            $request->input('confirm_name') !== $user->name => 'The name you typed does not match. Nothing was deleted.',
            default => null,
        };

        if ($blocked !== null) {
            // Carry the id back so the dialog reopens on the right row, not the first one.
            return back()
                ->withErrors(['confirm_name' => $blocked])
                ->with('delete_failed_user', $user->id);
        }

        $name = $user->name;
        $this->deletionService->deleteUser($user, $actor);

        return redirect()->route('users.index')
            ->with('success', $name.' and all of their records were permanently deleted.');
    }

    private function adminCount(): int
    {
        return User::whereHas('role', fn ($query) => $query->where('slug', 'admin'))->count();
    }
}
