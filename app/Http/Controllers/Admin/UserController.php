<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Comptes d'administration (codex §23).
 */
class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::query()
                ->with('role')
                ->withTrashed()
                ->orderBy('name')
                ->paginate((int) config('site.per_page.admin')),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.form', [
            'user' => new User,
            'roles' => $this->assignableRoles(),
            'isNew' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->withoutTrashed()],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'integer', Rule::in($this->assignableRoles()->pluck('id')->all())],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['nullable', 'boolean'],
        ], attributes: __('admin.users.attributes'));

        $user = User::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
            'password' => Hash::make($data['password']),
            'password_changed_at' => now(),
        ]);

        // Le compte doit confirmer son adresse avant d'accéder au back-office.
        $user->sendEmailVerificationNotification();

        Log::channel('auth')->notice('Compte administrateur créé.', [
            'user_id' => $user->getKey(),
            'by' => $request->user()->getKey(),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('admin.users.created'))
            ->with('status_variant', 'success');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.form', [
            'user' => $user,
            'roles' => $this->assignableRoles(),
            'isNew' => false,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->getKey())->withoutTrashed()],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'integer', Rule::in($this->assignableRoles()->pluck('id')->all())],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], attributes: __('admin.users.attributes'));

        // Personne ne modifie son propre rôle : cela permettrait de s'accorder
        // des droits supplémentaires.
        if ($request->user()->is($user)) {
            unset($data['role_id']);
        }

        $user->fill(array_filter(
            $data,
            fn (string $key): bool => $key !== 'password',
            ARRAY_FILTER_USE_KEY,
        ));

        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
            $user->password_changed_at = now();
        }

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('admin.users.updated'))
            ->with('status_variant', 'success');
    }

    /**
     * Active ou désactive un compte, sans perdre son historique.
     */
    public function toggle(Request $request, User $user): RedirectResponse
    {
        $this->authorize('toggleActivation', $user);

        $user->is_active = ! $user->is_active;
        $user->save();

        Log::channel('auth')->notice('Activation d’un compte modifiée.', [
            'user_id' => $user->getKey(),
            'is_active' => $user->is_active,
            'by' => $request->user()->getKey(),
        ]);

        return back()
            ->with('status', $user->is_active
                ? __('admin.users.activated')
                : __('admin.users.deactivated'))
            ->with('status_variant', 'success');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('admin.users.deleted'))
            ->with('status_variant', 'success');
    }

    /**
     * Rôles que le compte connecté a le droit d'attribuer : jamais un rôle de
     * niveau supérieur ou égal au sien.
     *
     * @return Collection<int, Role>
     */
    protected function assignableRoles(): Collection
    {
        $level = request()->user()?->roleEnum()?->level() ?? 0;

        return Role::query()
            ->where('level', '<=', $level)
            ->orderByDesc('level')
            ->get();
    }
}
