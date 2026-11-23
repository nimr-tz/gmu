{{--
    "What this role can do", from the owner's "GMU Admin" design. Indigo for the DRCP and the DG,
    neutral for the rest, rose when there is no role. Either pass $roleName (a fixed role, or null),
    or alpine => true inside a form whose Alpine data has `role` (a role id) and `roles` (id => info).
--}}
@php use App\Support\RoleLook as R; @endphp
@if($alpine ?? false)
    <section class="overflow-hidden rounded-[14px] border bg-card shadow-pop" :class="roles[role]?.approver ? 'border-indigo-100' : 'border-border'">
        <header class="flex items-center gap-2.5 border-b px-4 py-3" :class="roles[role]?.approver ? 'border-indigo-100 bg-indigo-50/60' : 'border-border bg-muted'">
            <span class="grid size-7 place-items-center rounded-lg" :class="roles[role] ? (roles[role].approver ? 'bg-indigo-500 text-white' : 'bg-foreground text-white') : 'bg-gray-200 text-muted-foreground'"><x-icon name="shield-check" class="size-3.5" /></span>
            <div><h3 class="text-sm font-semibold">What this role can do</h3><p class="text-[12.5px] text-muted-foreground" x-text="roles[role]?.label ?? 'Choose a role to see it'">Choose a role to see it</p></div>
        </header>
        <ul class="flex flex-col gap-2.5 px-4 py-3.5 text-[13.5px] leading-snug">
            <template x-for="item in (roles[role]?.items ?? [])">
                <li class="flex gap-2.5"><span class="mt-px grid size-4 shrink-0 place-items-center rounded-full" :class="roles[role].approver ? 'bg-indigo-50 text-indigo-700' : 'bg-blue-50 text-ok-ink'"><x-icon name="check" class="size-2.5" /></span><span x-text="item"></span></li>
            </template>
        </ul>
        <p class="border-t border-gray-100 px-4 pb-3.5 pt-2.5 text-xs text-muted-foreground">Anyone GMU assigns can review, whatever their role. Reviewers are anonymous to applicants.</p>
    </section>
@else
    @php $approver = R::isApprover($roleName); @endphp
    <section class="overflow-hidden rounded-[14px] border bg-card shadow-pop {{ $approver ? 'border-indigo-100' : 'border-border' }}">
        <header class="flex items-center gap-2.5 border-b px-4 py-3 {{ $approver ? 'border-indigo-100 bg-indigo-50/60' : 'border-border bg-muted' }}">
            <span class="grid size-7 place-items-center rounded-lg {{ $roleName ? ($approver ? 'bg-indigo-500 text-white' : 'bg-foreground text-white') : 'bg-gray-200 text-muted-foreground' }}"><x-icon name="shield-check" class="size-3.5" /></span>
            <div><h3 class="text-sm font-semibold">What this role can do</h3><p class="text-[12.5px] text-muted-foreground">{{ $roleLabel ?? 'No role' }}</p></div>
        </header>
        <ul class="flex flex-col gap-2.5 px-4 py-3.5 text-[13.5px] leading-snug">
            @foreach(R::abilities($roleName) as $item)
                <li class="flex gap-2.5"><span class="mt-px grid size-4 shrink-0 place-items-center rounded-full {{ $roleName ? ($approver ? 'bg-indigo-50 text-indigo-700' : 'bg-blue-50 text-ok-ink') : 'bg-danger-soft text-danger-ink' }}"><x-icon name="check" class="size-2.5" /></span>{{ $item }}</li>
            @endforeach
        </ul>
        <p class="border-t border-gray-100 px-4 pb-3.5 pt-2.5 text-xs text-muted-foreground">Anyone GMU assigns can review, whatever their role. Reviewers are anonymous to applicants.</p>
    </section>
@endif
