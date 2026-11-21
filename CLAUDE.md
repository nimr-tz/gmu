# NIMR Grants portal: how we build it

This file holds the standards for anyone, human or AI, who changes the portal. The app lives in `portal/` (Laravel 12, Blade views, one hand-written stylesheet). Read this before touching any page, email or status.

## What the portal is

NIMR's Grants Management Unit (GMU) runs it. The work today is the **internal seed grant**: the Early-Career Researchers' Implementation Science Seed Grant. It runs from call → concept note → GMU screening → concept review → full proposal → scored review → approval (GMU → DRCP → DG) → grant agreement.

The older external-grants features are hidden behind `GMU_EXTERNAL_GRANTS` (off by default). Don't delete them, and don't build new work on them.

Everyone signs in through NREIMS, but NREIMS does sign-in **only**. Profiles, roles and everything else live in the portal. Never add a feature that needs a change on the NREIMS side.

`portal/FRONTEND_MIGRATION_PLAN.md` (React/Inertia/Tailwind) is **superseded**. We build with Blade and `public/css/gmu.css`, with no front-end build step.

## Who it is for, and what it should feel like

| Person | What they need from us |
|---|---|
| **Applicant** (early-career researcher, often on a phone or a weak connection) | Where my application stands, what I must do next and by when, and the reassurance that nothing I typed is lost |
| **Reviewer** (any staff member GMU assigns; anonymous to applicants) | The text to judge, the criteria, a place to score, and nothing else |
| **GMU officer** | What needs action today, an audit trail for every decision, and no surprises |
| **DRCP / DG** | A short, ranked, defensible list to decide on |

The point of view, in order of priority:

1. **Fair and trustworthy.** We hand out public research money. Every page should look like it was made by a careful institution: calm, exact and consistent. Never playful about money or decisions.
2. **You always know where you stand.** Every status says what happened, who acts next and by when. Nobody should have to email GMU to ask "what's happening with my application?"
3. **Respect people's time and connection.** Pages are light and work offline from the office network, with no CDNs. Forms forgive mistakes, and work isn't lost when a connection drops.
4. **Plain words.** Use the language of the call document, not system jargon. Say "Revision requested", not "Status: CONCEPT_REVISION".

Being predictable is part of caring for the user here. Pages of the same kind look and behave the same. Put craft into clarity and details, not novelty.

## Visual rules (non-negotiable)

- **Brand blue `#2664EB`** (`--brand`, `--blue-600`), with the blue ramp `--blue-50` … `--blue-950` in `gmu.css`.
- **Never green, anywhere**: not for brand, success, "approved" or charts. This is an institutional rule. Positive and done states use blue.
- Status colour has fixed meanings:
  - **blue** (`ok`): done or positive
  - **amber** (`warn`): waiting on someone
  - **red** (`danger`): stopped, late or blocked
  - **navy**: under review
  - **grey**: in progress or neutral

  Red and amber mean only these things. Never use them for decoration.
- **Navigation lives only in the left side panel** (navy `--panel`). The top bar holds context, notifications and the user menu, never navigation links. On small screens the side panel slides in (the menu button). Don't add a second navigation pattern.
- Fonts: Geist and Geist Mono (`--sans`, `--mono`). Use mono for amounts, references and scores.
- Use tokens only. Never hard-code a hex colour in a view. The only exception is emails, where inline hex is required; copy the values from the tokens.
- Don't use the legacy aliases `--green`, `--navy-700`, `--navy-500`, or the `dot-green` class. They exist for old pages and all resolve to blue. Use `--brand` / `--ok` / `dot-blue`.

## Building blocks

Reuse these before writing new markup:

- **Layout:** `layouts/gmu.blade.php`. It already shows flash messages and validation errors, so pages don't repeat them.
- **Components** (`components/ui/`): `page-head` (title, lede, actions), `card`, `field` (label, help, error), `pill`, `stat`, `meter`, `empty`.
- **Seed grant partials** (`seed/partials/`):
  - `status`: the one way to show a status pill
  - `track`: the 8-step progress line, our signature element
  - `content`: read-only concept note or proposal
  - `feedback`: what the applicant sees from review

New styling goes into `gmu.css` as a named class. **Don't add inline `style=""`.** The seed views already have about 450 of them, which is debt to pay down, not a pattern to copy.

### Page templates

New pages should follow one of these:

| Kind | Shape | Reference |
|---|---|---|
| **Queue / list** | `page-head` → filter row → table or list → `empty` state that says what will appear and when | `seed/submissions-index`, `seed/reviews-index` |
| **Record** | `page-head` with status pill → `track` → "what happens next" banner → content cards → side column of actions and history | `seed/application-show`, `seed/submissions-show` |
| **Form** | Sections in the call document's order and lettering (A, B, C…) → word or page limits shown live → a save that never loses work → one primary action | `seed/application-edit` |
| **Decision** | The thing being judged → the criteria → the decision controls last, with the consequence stated before you press | `seed/review-show`, `seed/approval-show` |
| **Dashboard** | "Needs you now" first, then the state of the call, then everything else | `seed/dashboard-applicant`, `seed/dashboard-gmu` |

## Words, numbers and dates

- Status names come only from `SeedGrant::STATUS_LABELS`, and colours only from `STATUS_TONES`. Never write a status label by hand in a view.
- Money: `SeedGrant::tzs($amount)` gives `TZS 2,500,000`. Budget tables show whole shillings with thousands separators.
- Dates: `j M Y` (`4 Nov 2026`), and `j M Y H:i` when the time matters. Don't use `d M Y` in new work. Use relative time (`diffForHumans`) only beside an absolute date, never alone for a deadline.
- Time is **East Africa Time** (`APP_TIMEZONE`, default `Africa/Dar_es_Salaam`). Never convert to or show UTC.
- A deadline stored as a date means **the end of that day** in Dar es Salaam. Compare with `->copy()->endOfDay()`, as `SeedCall::acceptingConceptNotes()` and `SeedReviewRound::isOverdue()` do. Never use a bare `isPast()` on a date, which treats the due day itself as late.
- Use sentence case for headings and buttons. A button says what it does: "Submit concept note", not "Submit" or "OK".
- Reviewers appear to applicants **by letter only** ("Reviewer B"). Never show a name or the confidential note to GMU. `partials/feedback` enforces this, so use it.
- Every email has an in-portal twin. `Mail\SeedGrantNotice` sends the same headline and body as the portal notification, plus one button to the page where the person acts.
- Write with GMU's voice: polite, direct, specific. Say "GMU has asked for changes to section B3 by 12 Dec 2026", not "Your application has been updated".

## Code rules for the seed grant

- **`App\Services\SeedGrant\Workflow` owns every status change.** Controllers call it; they never set `status` themselves. Notifications go out from there.
- **`App\Support\SeedGrant` holds the call's rules**: statuses, the track, sections, word and page limits, budget caps, criteria and weights, and the thresholds (total ≥ 70, science ≥ 50% of its weight). Change a rule there, never in a view.
- `App\Support\SeedNav` builds the side-panel entries by role, with counts for "needs action".
- Use permissions through gates and roles (`manage-seed-grants`, approval levels). Hiding a button is not access control.
- Uploads are allowed only where the call allows them: mentor letter, CV and support letters at stage 2.

## Commits and deployment

- **Do not include `Co-Authored-By` or any other AI credit in commit messages or pull requests.** The owner does not want Claude credited in this repository.
- `master` deploys automatically (`.github/workflows/build-and-deploy.yml`, to Kubernetes through `nimr-tz/platform-gitops`). John Mduda runs the servers. Never overwrite or rewrite his commits or files (`Dockerfile`, `docker/`, the workflow, his lock-file changes): merge, never force-push. Live settings are his to set in `platform-gitops`, not in this repository.

## Definition of done

Passing tests and a page that looks finished are the start of done, not the end. Before calling a change done:

1. `php artisan test` passes. It runs on SQLite in memory; the seed grant suite is `tests/Feature/SeedGrant`. Any new status path gets a test.
2. **Walk it as the user.** Go through the journey for every role it touches with the demo data (`SeedGrantDemoSeeder`; demo users `@demo.nimr.or.tz`). Look at it as a whole, not page by page. Does each step lead clearly to the next?
3. Check the page at phone width (375px): no sideways scroll, and the side panel opens from the menu button.
4. Check the empty state, the error state and the "not your turn" state, not just the happy path.
5. Read every email and notification it sends, as the person receiving it.
6. Check that there's no green, no hard-coded colour, no new inline style, and no status text written by hand.

## Known gaps

These are things to fix, not patterns to copy.

- Rows written before the switch to East Africa Time (19 Nov 2026) hold UTC clock times, so they show 3 hours early. This affects only local demo data; reseed it to clear.
- `pill-info` looks identical to `pill-ok`, so "Awaiting reviewers" reads the same as "Approved". The `STATUS_TONES` comment says in-progress states are grey; the code disagrees.
- About 450 inline styles across `resources/views/seed`.
- Older emails (`deadline-reminder`, `weekly-digest`) use `d M Y`.
- Legacy token aliases (`--green`, `--navy-*`) and `dot-green` are still in use on external-grant pages.
