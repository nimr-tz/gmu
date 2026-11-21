@extends('auth.welcome.layout')

@section('title', 'Your post at NIMR')

@section('content')
    <p class="wl-eyebrow">Step 3 of {{ count($steps) }}</p>
    <h1 class="wl-title">A little more about you, so everything runs smoothly.</h1>
    <p class="wl-lede">
        To make NIMR Grants work well for you, we need a few details about your post at NIMR.
        You give them once, and we won’t ask again.
    </p>

    @if($errors->any())
        <div class="alert alert-error wl-errors" role="alert">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <div>
                <strong>Please check {{ $errors->count() === 1 ? 'one field' : $errors->count() . ' fields' }}</strong>
                <ul>@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.complete.store') }}" class="wl-form">
        @csrf
        <div class="form-grid">
            <div class="field">
                <label class="label" for="designation">Designation</label>
                <select id="designation" name="designation" class="select @error('designation') is-invalid @enderror" required>
                    <option value="">Choose…</option>
                    @foreach($designations as $d)
                        <option value="{{ $d }}" @selected(old('designation', $user->designation) === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="research_center">NIMR centre</label>
                <select id="research_center" name="research_center" class="select @error('research_center') is-invalid @enderror" required>
                    <option value="">Choose…</option>
                    @foreach($centres as $c)
                        <option value="{{ $c }}" @selected(old('research_center', $user->research_center) === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="department">Department or section</label>
                <input id="department" name="department" class="input @error('department') is-invalid @enderror"
                       value="{{ old('department', $user->department) }}" required maxlength="255">
            </div>
            <div class="field">
                <label class="label" for="phone">Mobile phone</label>
                <input id="phone" name="phone" class="input mono @error('phone') is-invalid @enderror" inputmode="tel"
                       value="{{ old('phone', $user->phone) }}" placeholder="+255 712 345 678" required>
                <span class="help">For reminders about deadlines and review requests.</span>
            </div>

            <fieldset class="field wide wl-fieldset">
                <legend class="label">Employment type</legend>
                <div class="wl-choices">
                    @foreach(\App\Models\User::EMPLOYMENT_TYPES as $value => $label)
                        <label class="wl-choice">
                            <input type="radio" name="employment_type" value="{{ $value }}" required
                                   @checked(old('employment_type', $user->employment_type) === $value)>
                            <span><strong>{{ $label }}</strong>
                                <span class="small muted">{{ $value === 'permanent' ? 'On NIMR’s permanent establishment' : 'On a fixed-term NIMR contract' }}</span></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="field wide">
                <label class="label" for="first_appointment_date">Date of first appointment at NIMR</label>
                <input id="first_appointment_date" type="date" name="first_appointment_date"
                       class="input @error('first_appointment_date') is-invalid @enderror"
                       value="{{ old('first_appointment_date', $user->first_appointment_date?->toDateString()) }}"
                       max="{{ now()->toDateString() }}" required>
            </div>
        </div>

        <div class="wl-actions">
            <a href="{{ route('profile.complete.nreims') }}" class="wl-back"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back</a>
            <button type="submit" class="btn btn-primary">Save and continue <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
        </div>
    </form>
@endsection
