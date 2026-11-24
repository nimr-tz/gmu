{{--
    The frame of the administrator's password-reset pages (the owner's "Password Reset"
    design): the NIMR seal, "Administrator", a white card holding `$slot`, and the way back
    to sign in. Used as @component('auth.partials.reset-card', ['back' => true]).
--}}
<div class="flex w-full max-w-[440px] flex-col items-center gap-7">
    <div class="flex flex-col items-center gap-3.5 text-center text-white">
        <span class="grid size-[72px] place-items-center rounded-full bg-white shadow-brand ring-8 ring-white/10"><img src="{{ asset('images/nimr-logo.png') }}" alt="National Institute for Medical Research" class="size-16 rounded-full object-contain"></span>
        <p class="flex items-center gap-3 text-[12.5px] font-medium uppercase tracking-[0.12em] text-blue-100"><span class="h-px w-6 bg-white/40"></span>Administrator<span class="h-px w-6 bg-white/40"></span></p>
    </div>
    <section class="w-full rounded-[18px] bg-card p-8 text-foreground shadow-brand">
        {{ $slot }}
    </section>
    @if($back ?? true)
        <a href="{{ route('login', ['admin' => 1]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-white hover:text-blue-50"><x-icon name="arrow-left" class="size-3.5" />Back to sign in</a>
    @endif
</div>
