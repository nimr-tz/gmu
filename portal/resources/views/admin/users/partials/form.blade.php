{{--
    Adding a person or editing an account, from the owner's "GMU Admin" design: the person,
    the account (when editing), and beside them what the chosen role can do.
    Expects $roles, $sso, and $user (null when adding).
--}}
@php
    use App\Support\RoleLook as R;
    $isEdit = (bool) $user;
    $roleInfo = $roles->mapWithKeys(fn ($r) => [$r->id => ['label' => $r->display_name, 'items' => R::abilities($r->name), 'approver' => R::isApprover($r->name)]]);
    $field = 'flex flex-col gap-1.5';
    $label = 'text-[13px] font-medium';
    $card = 'overflow-hidden rounded-[14px] border border-border bg-card';
    $head = 'border-b border-border bg-muted px-[18px] py-3';
@endphp

<div class="-mx-4 -mt-5 flex flex-col gap-2.5 bg-foreground px-7 pb-[100px] pt-[18px] text-white lg:-mx-6 lg:-mt-6">
    <a href="{{ $isEdit ? route('admin.users.show', $user) : route('admin.users.index') }}" class="inline-flex items-center gap-1.5 self-start text-[13px] font-medium text-gray-300 hover:text-white"><x-icon name="arrow-left" class="size-3.5" />{{ $isEdit ? $user->name : 'People and roles' }}</a>
    <h1 class="text-[30px] font-semibold leading-tight tracking-[-0.025em] text-white">{{ $isEdit ? 'Edit account' : ($sso ? 'Add a person' : 'Create an account') }}</h1>
    <p class="max-w-[760px] text-[15px] text-gray-300">
        {{ $isEdit ? $user->name . ' · ' . $user->email
            : ($sso ? 'They sign in with NREIMS. Their account links to this one the first time they do.' : 'The person is held at their profile page until they replace the password you set here.') }}
    </p>
</div>

<form method="POST" action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}"
      x-data="{ role: @js((string) old('role_id', $user?->role_id ?? '')), roles: @js($roleInfo), active: @js((bool) old('is_active', $user?->is_active ?? true)) }"
      class="-mt-[72px] flex flex-wrap items-start gap-5 pb-8">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="flex min-w-0 flex-[1_1_560px] flex-col gap-5">
        <section class="{{ $card }} shadow-pop">
            <header class="{{ $head }}"><h2 class="text-sm font-semibold">The person</h2></header>
            <div class="grid gap-x-4 gap-y-[18px] p-[18px] sm:grid-cols-2">
                <label class="{{ $field }} sm:col-span-2"><span class="{{ $label }}">Full name</span>
                    <input name="name" class="input @error('name') border-danger @enderror" value="{{ old('name', $user?->name) }}" required maxlength="255" placeholder="As it should appear on letters, e.g. Dr Rose Kitali">
                    @error('name')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Email</span>
                    <input type="email" name="email" class="input @error('email') border-danger @enderror" value="{{ old('email', $user?->email) }}" required placeholder="name@nimr.or.tz">
                    @error('email')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@else<span class="text-xs text-muted-foreground">{{ $sso ? 'The address on their NREIMS account.' : 'This is what they sign in with.' }}</span>@enderror
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Role</span>
                    <select name="role_id" class="select @error('role_id') border-danger @enderror" x-model="role" required>
                        <option value="">Choose a role…</option>
                        @foreach($roles as $r)<option value="{{ $r->id }}">{{ $r->display_name }}</option>@endforeach
                    </select>
                    @error('role_id')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@else<span class="text-xs text-muted-foreground">An account with no role can see nothing at all.</span>@enderror
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Department <span class="font-normal text-muted-foreground">(optional)</span></span>
                    <input name="department" class="input" value="{{ old('department', $user?->department) }}">
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Research centre <span class="font-normal text-muted-foreground">(optional)</span></span>
                    <input name="research_center" class="input" value="{{ old('research_center', $user?->research_center) }}">
                </label>
                <label class="{{ $field }}"><span class="{{ $label }}">Phone <span class="font-normal text-muted-foreground">(optional)</span></span>
                    <input name="phone" class="input font-mono" value="{{ old('phone', $user?->phone) }}" placeholder="0754 000 000">
                </label>
            </div>
            @if(! $isEdit && ! $sso)
                <div class="grid gap-x-4 gap-y-[18px] border-t border-border p-[18px] sm:grid-cols-2">
                    <label class="{{ $field }}"><span class="{{ $label }}">First password</span><input type="password" name="password" class="input" required minlength="12" autocomplete="new-password"><span class="text-xs text-muted-foreground">At least 12 characters, upper and lower case, and a number or a symbol. Hand it over in person.</span>@error('password')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror</label>
                    <label class="{{ $field }}"><span class="{{ $label }}">Confirm password</span><input type="password" name="password_confirmation" class="input" required minlength="12" autocomplete="new-password"></label>
                </div>
            @else
                <details class="group border-t border-border px-[18px] py-3" @if($errors->has('password')) open @endif>
                    <summary class="flex cursor-pointer list-none items-center gap-1.5 text-[13px] font-medium text-gray-700 [&::-webkit-details-marker]:hidden">
                        <x-icon name="chevron-right" class="size-3.5 text-subtle transition-transform group-open:rotate-90" />{{ $isEdit ? 'Reset the password' : 'Give a local password instead' }}
                    </summary>
                    <p class="mt-2 text-xs text-muted-foreground">{{ $isEdit ? 'Setting a password holds the account at the profile page until the person chooses their own.' : 'Only for a fallback administrator who must get in when NREIMS is down.' }}</p>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <label class="{{ $field }}"><span class="{{ $label }}">{{ $isEdit ? 'New password' : 'Password' }}</span><input type="password" name="password" class="input" minlength="12" autocomplete="new-password">@error('password')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror</label>
                        <label class="{{ $field }}"><span class="{{ $label }}">Confirm</span><input type="password" name="password_confirmation" class="input" minlength="12" autocomplete="new-password"></label>
                    </div>
                </details>
            @endif
        </section>

        @if($isEdit)
            <section class="{{ $card }}">
                <header class="{{ $head }}"><h2 class="text-sm font-semibold">Account</h2></header>
                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-b border-gray-100 px-[18px] py-4">
                    <span><span class="block text-sm font-medium">Sign-in</span><span class="mt-0.5 block text-[13px] text-muted-foreground">{{ $user->nreims_id ? 'Linked to NREIMS. NREIMS handles the password; the portal holds the role.' : ($sso ? 'Not linked yet. It links the first time they sign in with NREIMS.' : 'A local password, set in the portal.') }}</span></span>
                    <span class="inline-flex h-6 items-center gap-1.5 rounded-md px-2 text-xs font-medium {{ $user->nreims_id ? 'bg-blue-50 text-ok-ink' : 'bg-gray-100 text-gray-700' }}"><span class="size-1.5 rounded-full {{ $user->nreims_id ? 'bg-primary' : 'bg-gray-400' }}"></span>{{ $user->nreims_id ? 'NREIMS' : 'Local password' }}</span>
                </div>
                <label class="flex cursor-pointer items-start gap-3 px-[18px] py-4">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="switch mt-px checked:bg-foreground" name="is_active" value="1" x-model="active" @disabled($user->id === auth()->id())>
                    @if($user->id === auth()->id())<input type="hidden" name="is_active" value="1">@endif
                    <span class="flex-1"><span class="block text-sm font-medium">Active</span>
                        <span class="mt-0.5 block text-[13px] leading-normal" :class="active ? 'text-muted-foreground' : 'text-danger-ink'"
                              x-text="active ? 'They can sign in and see what their role allows.' : 'Deactivated: they can no longer sign in. Their applications, reviews and decisions stay on record.'">They can sign in and see what their role allows.</span></span>
                </label>
            </section>
        @else
            <input type="hidden" name="is_active" value="1">
        @endif

        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="{{ $isEdit ? route('admin.users.show', $user) : route('admin.users.index') }}" class="inline-flex h-[38px] items-center rounded-lg border border-input bg-card px-3.5 text-[13.5px] font-medium hover:bg-gray-100">Cancel</a>
            <button type="submit" class="h-[38px] rounded-lg bg-foreground px-4 text-[13.5px] font-medium text-white hover:bg-gray-800">{{ $isEdit ? 'Save changes' : ($sso ? 'Add person' : 'Create account') }}</button>
        </div>
    </div>

    <aside class="flex min-w-[280px] flex-[0_1_360px] flex-col gap-4 lg:sticky lg:top-5">
        @include('admin.users.partials.abilities', ['alpine' => true])
    </aside>
</form>
