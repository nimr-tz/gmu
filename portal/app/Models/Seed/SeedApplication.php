<?php

namespace App\Models\Seed;

use App\Models\User;
use App\Support\SeedGrant;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class SeedApplication extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 'mentor_name', 'mentor_designation', 'mentor_centre', 'mentor_suitability',
        'concept', 'proposal', 'declared_no_prior_large_grant',
    ];

    protected function casts(): array
    {
        return [
            'concept'                       => 'array',
            'proposal'                      => 'array',
            'screening_checks'              => 'array',
            'declared_no_prior_large_grant' => 'boolean',
            'concept_submitted_at'          => 'datetime',
            'proposal_submitted_at'         => 'datetime',
            'screened_at'                   => 'datetime',
            'agreement_issued_at'           => 'datetime',
            'agreement_accepted_at'         => 'datetime',
            'signed_agreement_uploaded_at'  => 'datetime',
            'approved_amount'               => 'integer',
        ];
    }

    /** The status is changed only through the workflow service, and is always worth a line in the audit log. */
    protected function auditableFields(): array
    {
        return ['title', 'status', 'mentor_name', 'screened_at', 'approved_amount', 'agreement_issued_at',
            'agreement_accepted_at', 'signed_agreement_uploaded_at'];
    }

    public function auditLabel(): string
    {
        return 'Seed grant ' . $this->reference;
    }

    // ── Relations ───────────────────────────────────────────────────────────

    public function call()
    {
        return $this->belongsTo(SeedCall::class, 'seed_call_id');
    }

    public function pi()
    {
        return $this->belongsTo(User::class, 'pi_id');
    }

    public function budgetItems()
    {
        return $this->hasMany(SeedBudgetItem::class)->orderBy('position');
    }

    public function documents()
    {
        return $this->hasMany(SeedDocument::class);
    }

    public function assignments()
    {
        return $this->hasMany(SeedReviewerAssignment::class)->orderBy('label');
    }

    public function rounds()
    {
        return $this->hasMany(SeedReviewRound::class)->orderBy('number');
    }

    public function approvals()
    {
        return $this->hasMany(SeedApproval::class)->latest('id');
    }

    public function screener()
    {
        return $this->belongsTo(User::class, 'screened_by');
    }

    // ── Stage and status ────────────────────────────────────────────────────

    /** concept or proposal: which half of the call the application is in. */
    public function stage(): string
    {
        return in_array($this->status, SeedGrant::CONCEPT_STATUSES, true) ? 'concept' : 'proposal';
    }

    public function statusLabel(): string
    {
        return SeedGrant::statusLabel($this->status);
    }

    public function statusTone(): string
    {
        return SeedGrant::STATUS_TONES[$this->status] ?? '';
    }

    public function isEditableByApplicant(): bool
    {
        return in_array($this->status, SeedGrant::APPLICANT_EDITABLE, true);
    }

    public function isRevising(): bool
    {
        return in_array($this->status, [SeedGrant::CONCEPT_REVISION, SeedGrant::PROPOSAL_REVISION], true);
    }

    /** Index into SeedGrant::TRACK of the step the application is on. */
    public function trackIndex(): int
    {
        foreach (SeedGrant::TRACK as $i => [, , $statuses]) {
            if (in_array($this->status, $statuses, true)) {
                return $i;
            }
        }

        return 0;
    }

    // ── Content ─────────────────────────────────────────────────────────────

    public function sectionText(string $stage, string $key): string
    {
        return (string) (($stage === 'proposal' ? $this->proposal : $this->concept)[$key] ?? '');
    }

    /** The applicant's references for a stage: optional, outside the word and page limits. */
    public function references(string $stage): string
    {
        return $this->sectionText($stage, 'references');
    }

    /** Any other concept-note answer kept in the concept JSON (Section A extras, tick-boxes, declarations). */
    public function conceptField(string $key): mixed
    {
        return ($this->concept ?? [])[$key] ?? null;
    }

    /** The tick-box answer to question 7 or 8 (SeedGrant::CONCEPT_CHOICES). */
    public function choice(string $key): ?string
    {
        $answer = $this->conceptField($key . '_answer');

        return is_string($answer) && $answer !== '' ? $answer : null;
    }

    /** Section E boxes the applicant has ticked. */
    public function declarations(): array
    {
        return array_values(array_intersect(array_keys(SeedGrant::DECLARATIONS), (array) ($this->conceptField('declarations') ?? [])));
    }

    /** "yes", "no" or null: has the applicant held a research grant above USD 10,000 as PI? */
    public function priorLargeGrant(): ?string
    {
        $answer = $this->conceptField('prior_grant');

        return in_array($answer, ['yes', 'no'], true) ? $answer : null;
    }

    public function wordCount(string $stage): int
    {
        $words = 0;
        foreach (array_keys($this->call->form()->sections($stage)) as $key) {
            $words += SeedGrant::wordCount($this->sectionText($stage, $key));
        }

        return $words;
    }

    public function pageEstimate(string $stage): float
    {
        return round($this->wordCount($stage) / SeedGrant::WORDS_PER_PAGE, 1);
    }

    public function budgetLines(string $stage)
    {
        return $this->budgetItems->where('stage', $stage)->values();
    }

    public function budgetTotal(string $stage): int
    {
        return (int) $this->budgetLines($stage)->sum(fn (SeedBudgetItem $i) => $i->total());
    }

    public function budgetByCategory(): array
    {
        $out = array_fill_keys(array_keys(SeedGrant::BUDGET_CATEGORIES), 0);
        foreach ($this->budgetLines('proposal') as $line) {
            $out[$line->category] = ($out[$line->category] ?? 0) + $line->total();
        }

        return $out;
    }

    public function budgetByQuarter(): array
    {
        $lines = $this->budgetLines('proposal');

        return ['Q1' => $lines->sum('q1'), 'Q2' => $lines->sum('q2'), 'Q3' => $lines->sum('q3'), 'Q4' => $lines->sum('q4')];
    }

    /** The amount the application is asking for at its current stage. */
    public function requestedAmount(): int
    {
        $proposal = $this->budgetTotal('proposal');

        return $this->stage() === 'proposal' && $proposal > 0 ? $proposal : $this->budgetTotal('concept');
    }

    // ── Review ──────────────────────────────────────────────────────────────

    public function stageAssignments(?string $stage = null)
    {
        $stage ??= $this->stage();

        return $this->assignments->where('stage', $stage)->values();
    }

    public function activeAssignments(?string $stage = null)
    {
        return $this->stageAssignments($stage)->filter->isActive()->values();
    }

    public function stageRounds(?string $stage = null)
    {
        $stage ??= $this->stage();

        return $this->rounds->where('stage', $stage)->values();
    }

    public function latestRound(?string $stage = null): ?SeedReviewRound
    {
        return $this->stageRounds($stage)->last();
    }

    public function openRound(?string $stage = null): ?SeedReviewRound
    {
        $round = $this->latestRound($stage);

        return $round && $round->isOpen() ? $round : null;
    }

    /** The round whose reviews the applicant is answering, while a revision is requested. */
    public function roundAwaitingResponse(): ?SeedReviewRound
    {
        $round = $this->latestRound();

        return $this->isRevising() && $round && $round->decision === 'revise' && ! $round->responded_at ? $round : null;
    }

    /**
     * Mean scores of the submitted reviews in the latest full-proposal round,
     * keyed by criterion plus 'total', or null if nothing is scored yet.
     */
    public function meanScores(): ?array
    {
        $round = $this->latestRound('proposal');
        $reviews = $round ? $round->reviews->filter->isSubmitted() : collect();

        if ($reviews->isEmpty()) {
            return null;
        }

        $means = [];
        foreach (array_keys($this->call->form()->criteria()) as $key) {
            $means[$key] = round($reviews->avg(fn (SeedReview $r) => $r->score($key) ?? 0), 1);
        }
        $means['total'] = round($reviews->avg('total'), 1);
        $means['count'] = $reviews->count();

        return $means;
    }

    public function meetsThreshold(): bool
    {
        $m = $this->meanScores();

        return $this->call->form()->meetsThreshold($m);
    }

    public function approvalAt(string $level): ?SeedApproval
    {
        return $this->approvals->firstWhere('level', $level);
    }
}
