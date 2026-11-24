<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\Auth\NreimsOAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('role');

        if ($request->role === 'none') {
            $query->whereNull('role_id');
        } elseif ($request->role) {
            $query->whereHas('role', fn($q) => $q->where('name', $request->role));
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('department', 'like', "%{$request->search}%");
            });
        }

        if ($request->status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($request->status === 'active') {
            $query->where('is_active', true);
        }

        $users = $query->orderBy('name')->paginate(25)->withQueryString();
        $roles = Role::orderBy('display_name')->get();
        $sso   = app(NreimsOAuth::class)->enabled();

        // Active accounts per role, for the role tiles (the owner's "GMU Admin" design).
        $byRole = User::where('is_active', true)->selectRaw('role_id, count(*) as n')->groupBy('role_id')->pluck('n', 'role_id');
        $tiles = $roles->sortBy(fn ($r) => [array_search($r->name, \App\Support\RoleLook::TILE_ORDER, true) === false ? 1 : 0,
                array_search($r->name, \App\Support\RoleLook::TILE_ORDER, true), $r->display_name])
            ->filter(fn ($r) => in_array($r->name, \App\Support\RoleLook::TILE_ORDER, true) || ($byRole[$r->id] ?? 0) > 0)
            ->map(fn ($r) => ['key' => $r->name, 'label' => \App\Support\RoleLook::label($r->name, $r->display_name), 'n' => (int) ($byRole[$r->id] ?? 0)])
            ->push(['key' => 'none', 'label' => 'No role', 'n' => (int) ($byRole[''] ?? $byRole[null] ?? 0)])
            ->values();
        $total = User::count();

        return view('admin.users.index', compact('users', 'roles', 'sso', 'tiles', 'total'));
    }

    public function create()
    {
        $this->authorize('create', User::class);

        $roles = Role::orderBy('display_name')->get();
        $sso   = app(NreimsOAuth::class)->enabled();

        return view('admin.users.create', compact('roles', 'sso'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        // With NREIMS on, people sign in there: GMU adds them by email and the
        // account links on their first NREIMS sign-in. A local password is only
        // needed when NREIMS is off, or for a fallback administrator.
        $sso = app(NreimsOAuth::class)->enabled();

        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'password'        => [$sso ? 'nullable' : 'required', 'string', \Illuminate\Validation\Rules\Password::defaults(), 'confirmed'],
            'role_id'         => 'nullable|exists:roles,id',
            'phone'           => 'nullable|string|max:20',
            'department'      => 'nullable|string|max:255',
            'research_center' => 'nullable|string|max:255',
            'is_active'       => 'nullable|boolean',
        ]);

        $data['email']     = mb_strtolower(trim($data['email']));
        $data['is_active'] = $request->boolean('is_active', true);

        $localPassword = filled($data['password'] ?? null);

        if ($localPassword) {
            $data['password'] = Hash::make($data['password']);
            // The Coordinator picked this password, so the user must replace it (H4).
            $data['must_change_password'] = true;
        } else {
            // Nobody knows this password: the person signs in through NREIMS.
            $data['password'] = Hash::make(Str::random(64));
            $data['must_change_password'] = false;
        }

        $user = User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', $localPassword
                ?'Account created. Hand over the password in person.'
                : $user->name . ' is added. Their account links the first time they sign in with NREIMS using ' . $user->email . '.');
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load('role');
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        $roles = Role::orderBy('display_name')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email,' . $user->id,
            'role_id'         => 'nullable|exists:roles,id',
            'phone'           => 'nullable|string|max:20',
            'department'      => 'nullable|string|max:255',
            'research_center' => 'nullable|string|max:255',
            'is_active'       => 'nullable|boolean',
            'password'        => ['nullable', 'string', \Illuminate\Validation\Rules\Password::defaults(), 'confirmed'],
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($data['password']);
            $data['must_change_password'] = true;
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        $user->update($data);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->update(['is_active' => false]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deactivated.');
    }
}
