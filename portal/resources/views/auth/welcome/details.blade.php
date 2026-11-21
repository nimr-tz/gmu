@extends('auth.welcome.layout')

@section('title', 'Your post at NIMR')

@section('content')
    <p class="wl-eyebrow">Step 3 of {{ count($steps) }}</p>
    <h1 class="wl-title">Now, tell us about your work at NIMR.</h1>
    <p class="wl-lede">
        Six details, asked once. They let us confirm your eligibility for you, so you never have to prove it on a form.
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
                <span class="help">Both can lead an application.</span>
            </fieldset>

            <div class="field wide">
                <label class="label" for="first_appointment_date">Date of first appointment at NIMR</label>
                <input id="first_appointment_date" type="date" name="first_appointment_date"
                       class="input @error('first_appointment_date') is-invalid @enderror"
                       value="{{ old('first_appointment_date', $user->first_appointment_date?->toDateString()) }}"
                       max="{{ now()->toDateString() }}" required data-service-date>
                <span class="help">The seed grant is for researchers in their first {{ \App\Models\User::MAX_SERVICE_YEARS_FOR_SEED_GRANT }} years at NIMR. We work this out from your date, so you don’t have to.</span>
            </div>
        </div>

        <div class="wl-elig ok" data-elig="ok" hidden>
            <span class="ico"><i class="fas fa-check" aria-hidden="true"></i></span>
            <div>
                <strong>You can lead a seed grant application</strong>
                <p>You have served at NIMR for <span class="mono" data-service-text></span>.</p>
            </div>
        </div>
        <div class="wl-elig no" data-elig="no" hidden>
            <span class="ico"><i class="fas fa-info" aria-hidden="true"></i></span>
            <div>
                <strong>You can still review and use NIMR Grants</strong>
                <p>You have served at NIMR for <span class="mono" data-service-text></span>. Leading a seed grant application needs under {{ \App\Models\User::MAX_SERVICE_YEARS_FOR_SEED_GRANT }} years’ service.</p>
            </div>
        </div>

        <div class="wl-actions">
            <a href="{{ route('profile.complete.nreims') }}" class="wl-back"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back</a>
            <button type="submit" class="btn btn-primary">Save and continue <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    // Show, as the date is entered, whether the service rule for lead applicants is met.
    (function () {
        var input = document.querySelector('[data-service-date]');
        var ok = document.querySelector('[data-elig="ok"]'), no = document.querySelector('[data-elig="no"]');
        var limit = {{ \App\Models\User::MAX_SERVICE_YEARS_FOR_SEED_GRANT }};
        function update() {
            ok.hidden = no.hidden = true;
            if (!input.value) return;
            var start = new Date(input.value + 'T00:00:00'), now = new Date();
            if (isNaN(start) || start > now) return;
            var months = (now.getFullYear() - start.getFullYear()) * 12 + (now.getMonth() - start.getMonth());
            if (now.getDate() < start.getDate()) months--;
            var y = Math.floor(months / 12), m = months % 12;
            var text = y + (y === 1 ? ' year ' : ' years ') + m + (m === 1 ? ' month' : ' months');
            var box = months < limit * 12 ? ok : no;
            box.querySelector('[data-service-text]').textContent = text;
            box.hidden = false;
        }
        input.addEventListener('input', update);
        update();
    })();
</script>
@endpush
