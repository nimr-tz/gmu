{{-- The person's details, shared by "add" and "edit". Expects $roles, $sso, and $user (null when adding). --}}
<div class="grid gap-x-4 gap-y-5 sm:grid-cols-2">
    <x-field class="sm:col-span-2" label="Full name" name="name" required>
        <input id="name" name="name" class="input" value="{{ old('name', $user?->name) }}" required maxlength="255">
    </x-field>
    <x-field label="Email" name="email" required :help="$user ? null : ($sso ? 'The address on their NREIMS account.' : 'This is what they sign in with.')">
        <input id="email" type="email" name="email" class="input" value="{{ old('email', $user?->email) }}" required>
    </x-field>
    <x-field label="Role" name="role_id" required :help="$user ? null : 'An account with no role can see nothing at all.'">
        <select id="role_id" name="role_id" class="select" required>
            @unless($user)<option value="">Choose a role…</option>@endunless
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected(old('role_id', $user?->role_id) == $role->id)>{{ $role->display_name }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field label="Department" name="department" optional>
        <input id="department" name="department" class="input" value="{{ old('department', $user?->department) }}">
    </x-field>
    <x-field label="Research centre" name="research_center" optional>
        <input id="research_center" name="research_center" class="input" value="{{ old('research_center', $user?->research_center) }}">
    </x-field>
    <x-field label="Phone" name="phone" optional>
        <input id="phone" name="phone" class="input" value="{{ old('phone', $user?->phone) }}" placeholder="0754 000 000">
    </x-field>
</div>
