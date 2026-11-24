@extends('layouts.app')

{{--
    A person's own details, from the owner's "Staff Pages" design: their details, their
    employment at NIMR (which decides eligibility), and their access. A local-password
    account (no NREIMS) also changes its password here; NREIMS accounts do that in NREIMS.
    The band takes the reader's colour: staff blue, GMU ink, the approvers indigo.
--}}

@php
    use App\Support\RoleLook as R;
    $user = auth()->user();
    $band = $user->can('manage-seed-grants') ? 'bg-foreground' : ($user->can('approve-seed-grants') ? 'bg-indigo-700' : 'bg-blue-700');
    $muted = $user->can('manage-seed-grants') ? 'text-gray-300' : ($user->can('approve-seed-grants') ? 'text-indigo-100' : 'text-blue-100');
    $save = $user->can('manage-seed-grants') ? 'bg-foreground hover:bg-gray-800' : ($user->can('approve-seed-grants') ? 'bg-indigo-700 hover:bg-indigo-800' : 'bg-blue-700 hover:bg-blue-800');
    $linked = (bool) $user->nreims_id;
    $field = 'flex flex-col gap-1.5';
    $label = 'text-[13px] font-medium';
    $card = 'overflow-hidden rounded-[14px] border border-border bg-card';
    $head = 'border-b border-border bg-muted px-[18px] py-3';

    // The years-at-NIMR rule of the call they are most likely to apply to: an open call that has one.
    $ruleCall = \App\Models\Seed\SeedCall::where('status', 'open')->get()
        ->first(fn ($c) => $c->acceptingConceptNotes() && $c->form()->serviceYears());
    $limit = $ruleCall?->form()->serviceYears();
    $joined = old('joined', $user->first_appointment_date?->format('Y-m'));
    $applications = \App\Models\Seed\SeedApplication::where('pi_id', $user->id)->count();
@endphp

@section('title', 'Profile')
@section('context', 'Account · Profile')
@section('topbar-colour', $band)

@section('content')
<div class="-mx-4 -mt-5 flex items-center gap-[18px] px-7 pb-[100px] pt-5 text-white lg:-mx-6 lg:-mt-6 {{ $band }}">
    <span class="grid size-[60px] shrink-0 place-items-center rounded-full bg-white text-xl font-semibold {{ $user->can('manage-seed-grants') ? 'text-foreground' : ($user->can('approve-seed-grants') ? 'text-indigo-700' : 'text-blue-700') }}">{{ R::initials($user->name) }}</span>
    <div class="flex min-w-0 flex-col gap-1.5">
        <h1 class="text-[28px] font-semibold leading-tight tracking-[-0.025em] text-white">{{ $user->name }}</h1>
        <p class="text-[14.5px] {{ $muted }}">{{ $user->email }} · {{ R::label($user->role?->name, $user->role?->display_name) }}</p>
    </div>
</div>

<div class="-mt-[72px] flex flex-wrap items-start gap-5">
    <div class="flex min-w-0 flex-[1_1_560px] flex-col gap-5">
        @if($user->must_change_password)
            <div class="flex gap-3 rounded-xl border border-warn-line bg-warn-soft px-4 py-3.5 shadow-pop">
                <x-icon name="lock" class="mt-0.5 size-4 shrink-0 text-warn-ink" />
                <div><p class="text-sm font-semibold text-warn-ink">Choose your own password to continue</p><p class="mt-0.5 text-[13px] text-gray-700">The password on this account was set by somebody else. Until you replace it below, the rest of the portal is held.</p></div>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="flex flex-col gap-5"
              x-data="{ joined: @js($joined), limit: @js($limit), name: @js(old('name', $user->name)), tried: false,
                        get months() { if (! this.joined) return null; const [y, m] = this.joined.split('-').map(Number); const now = new Date(); return Math.max(0, (now.getFullYear() - y) * 12 + (now.getMonth() + 1 - m)); } }"
              @submit="tried = true; if (! name.trim()) $event.preventDefault()">
            @csrf @method('PATCH')

            <section class="{{ $card }} shadow-pop">
                <header class="{{ $head }}"><h2 class="text-sm font-semibold">Your details</h2><p class="text-[13px] text-muted-foreground">Shown on your applications and on the grant agreement</p></header>
                <div class="grid gap-x-4 gap-y-[18px] p-[18px] sm:grid-cols-2">
                    <label class="{{ $field }} sm:col-span-2"><span class="{{ $label }}">Full name</span>
                        <input name="name" class="input" x-model="name" maxlength="255" autocomplete="name" :class="tried && ! name.trim() && 'border-danger'">
                        <span class="text-[12.5px] text-danger-ink" x-show="tried && ! name.trim()" x-cloak>Enter your full name.</span>
                        @error('name')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror
                    </label>
                    <label class="{{ $field }}"><span class="{{ $label }}">Designation</span>
                        <select name="designation" class="select">
                            <option value="">Choose…</option>
                            @foreach(config('gmu.designations') as $d)<option value="{{ $d }}" @selected(old('designation', $user->designation) === $d)>{{ $d }}</option>@endforeach
                        </select>
                    </label>
                    <label class="{{ $field }}"><span class="{{ $label }}">Research centre</span>
                        <select name="research_center" class="select">
                            <option value="">Choose…</option>
                            @foreach(config('gmu.centres') as $c)<option value="{{ $c }}" @selected(old('research_center', $user->research_center) === $c)>{{ $c }}</option>@endforeach
                        </select>
                    </label>
                    <label class="{{ $field }}"><span class="{{ $label }}">Department <span class="font-normal text-muted-foreground">(optional)</span></span>
                        <input name="department" class="input" value="{{ old('department', $user->department) }}" maxlength="255">
                    </label>
                    <label class="{{ $field }}"><span class="{{ $label }}">Phone</span>
                        <input name="phone" class="input font-mono" inputmode="tel" value="{{ old('phone', $user->phone) }}" maxlength="20" placeholder="+255 712 345 678">
                    </label>
                    <label class="{{ $field }} sm:col-span-2"><span class="{{ $label }}">Email</span>
                        @if($linked)
                            <input class="input" value="{{ $user->email }}" disabled>
                            <span class="text-xs text-muted-foreground">Comes from NREIMS. Change it there.</span>
                        @else
                            <input type="email" name="email" class="input" value="{{ old('email', $user->email) }}" required autocomplete="email">
                            @error('email')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@else<span class="text-xs text-muted-foreground">This account signs in with a local password, so its email is set here.</span>@enderror
                        @endif
                    </label>
                </div>
            </section>

            <section class="{{ $card }}">
                <header class="{{ $head }}"><h2 class="text-sm font-semibold">Employment at NIMR</h2><p class="text-[13px] text-muted-foreground">Used to check eligibility when you apply</p></header>
                <div class="grid items-start gap-x-4 gap-y-[18px] p-[18px] sm:grid-cols-3">
                    <label class="{{ $field }}"><span class="{{ $label }}">Employment</span>
                        <select name="employment_type" class="select">
                            <option value="">Choose…</option>
                            @foreach(\App\Models\User::EMPLOYMENT_TYPES as $value => $name)<option value="{{ $value }}" @selected(old('employment_type', $user->employment_type) === $value)>{{ $name }}</option>@endforeach
                        </select>
                    </label>
                    <label class="{{ $field }}"><span class="{{ $label }}">Joined NIMR</span>
                        <input type="month" name="joined" class="input" x-model="joined" max="{{ now()->format('Y-m') }}">
                        @error('joined')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror
                    </label>
                    <div class="{{ $field }}"><span class="{{ $label }}">Years at NIMR</span>
                        <span class="flex h-[38px] items-center text-xl font-semibold tracking-[-0.01em] tabular-nums" x-text="months === null ? '—' : Math.floor(months / 12) + ' y ' + (months % 12) + ' m'">—</span>
                    </div>
                </div>
                @if($ruleCall)
                    <div class="mx-[18px] mb-[18px] flex items-center gap-3 rounded-[10px] border px-3.5 py-3"
                         :class="months === null || months >= limit * 12 ? 'border-warn-line bg-warn-soft' : 'border-ok-line bg-ok-soft'">
                        <span class="grid size-6 shrink-0 place-items-center rounded-full text-white" :class="months === null || months >= limit * 12 ? 'bg-warn' : 'bg-primary'">
                            <x-icon name="check" class="size-3" x-show="months !== null && months < limit * 12" /><span class="text-xs font-bold" x-show="months === null || months >= limit * 12" x-cloak>!</span>
                        </span>
                        <span class="flex-1 text-[13.5px] leading-normal text-gray-800">
                            <template x-if="months === null"><span><b class="font-semibold text-warn-ink">Add the month you joined.</b> The {{ $ruleCall->code }} call is for staff with under {{ $limit }} years at NIMR; GMU checks it at screening.</span></template>
                            <template x-if="months !== null && months < limit * 12"><span><b class="font-semibold text-ok-ink">Under {{ $limit }} years.</b> You meet the years-at-NIMR rule for the {{ $ruleCall->code }} call. GMU checks it at screening.</span></template>
                            <template x-if="months !== null && months >= limit * 12"><span><b class="font-semibold text-warn-ink">{{ $limit }} years or more.</b> The {{ $ruleCall->code }} call is for staff with under {{ $limit }} years at NIMR. If the date is wrong, correct it here before applying.</span></template>
                        </span>
                    </div>
                @endif
            </section>

            <div class="flex items-center justify-end gap-3">
                @if(session('status') === 'profile-updated')<span class="inline-flex items-center gap-1.5 text-[13px] font-medium text-ok-ink"><x-icon name="check" class="size-3.5" />Saved</span>@endif
                <button type="submit" class="h-10 rounded-[9px] px-[18px] text-sm font-medium text-white {{ $save }}">Save details</button>
            </div>
        </form>

        @unless($linked)
            <form method="POST" action="{{ route('password.update') }}" class="{{ $card }}">
                @csrf @method('PUT')
                <header class="{{ $head }}"><h2 class="text-sm font-semibold">Password</h2><p class="text-[13px] text-muted-foreground">This account signs in with a local password. Use something you do not use anywhere else.</p></header>
                <div class="grid gap-x-4 gap-y-[18px] p-[18px] sm:grid-cols-2">
                    <label class="{{ $field }} sm:col-span-2"><span class="{{ $label }}">Current password</span><input type="password" name="current_password" class="input" required autocomplete="current-password">@error('current_password', 'updatePassword')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror</label>
                    <label class="{{ $field }}"><span class="{{ $label }}">New password</span><input type="password" name="password" class="input" required autocomplete="new-password">@error('password', 'updatePassword')<span class="text-[12.5px] text-danger-ink">{{ $message }}</span>@enderror</label>
                    <label class="{{ $field }}"><span class="{{ $label }}">Confirm new password</span><input type="password" name="password_confirmation" class="input" required autocomplete="new-password"></label>
                </div>
                <footer class="flex items-center justify-end gap-3 border-t border-border bg-gray-50 px-[18px] py-3">
                    @if(session('status') === 'password-updated')<span class="inline-flex items-center gap-1.5 text-[13px] font-medium text-ok-ink"><x-icon name="check" class="size-3.5" />Password changed</span>@endif
                    <button type="submit" class="h-9 rounded-lg bg-foreground px-4 text-[13px] font-medium text-white hover:bg-gray-800">Change password</button>
                </footer>
            </form>
        @endunless
    </div>

    <aside class="flex min-w-[280px] flex-[0_1_340px] flex-col gap-4">
        <section class="{{ $card }} shadow-pop">
            <header class="border-b border-border bg-muted px-4 py-3"><h3 class="text-sm font-semibold">Your access</h3></header>
            <dl class="px-4 py-1">
                @foreach([
                    'Role' => R::label($user->role?->name, $user->role?->display_name),
                    'Sign-in' => $linked ? 'NREIMS' : 'Local password',
                    'Applications' => $applications,
                    'Member since' => $user->created_at?->format('M Y'),
                ] as $k => $v)
                    <div class="flex justify-between gap-3 border-b border-gray-100 py-2.5 text-[13px] last:border-0"><dt class="text-muted-foreground">{{ $k }}</dt><dd class="text-right font-medium">{{ $v }}</dd></div>
                @endforeach
            </dl>
            <p class="px-4 pb-3.5 pt-2.5 text-xs leading-normal text-muted-foreground">Your role decides what you can see and do. Only the GMU Coordinator can change it.{{ $linked ? ' Your password is managed in NREIMS.' : '' }}</p>
            @if($user->isResearcher() && config('gmu.external_grants'))
                <div class="px-4 pb-4"><x-button class="w-full" :href="route('researchers.show', $user)" icon="user">My researcher profile</x-button></div>
            @endif
        </section>
    </aside>
</div>
@endsection
