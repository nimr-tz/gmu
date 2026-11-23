<?php

namespace App\Models\Seed;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class SeedCall extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 'code', 'fiscal_year', 'summary', 'min_amount', 'max_amount', 'envelope',
        'duration_months', 'equipment_cap_percent', 'concept_deadline', 'proposal_deadline', 'status', 'config',
    ];

    public const STATUSES = ['draft' => 'Draft (hidden)', 'open' => 'Open', 'closed' => 'Closed'];

    /** Record the moment a call is first set to Open (any path: settings, seeders, tests). */
    protected static function booted(): void
    {
        static::saving(function (SeedCall $call) {
            if ($call->status === 'open' && ! $call->opened_at) {
                $call->opened_at = now();
            }
        });
    }

    /**
     * How much of the concept-note window has passed, 0–100, from the day the call
     * opened to the end of the deadline day. Null when either date is missing.
     */
    public function conceptWindowElapsed(): ?float
    {
        if (! $this->opened_at || ! $this->concept_deadline) {
            return null;
        }
        $start = $this->opened_at->copy()->startOfDay();
        $end = $this->concept_deadline->copy()->endOfDay();
        $span = max(1, $start->diffInSeconds($end));

        return max(0, min(100, $start->diffInSeconds(now(), false) / $span * 100));
    }

    protected function casts(): array
    {
        return [
            'concept_deadline'  => 'date',
            'opened_at'         => 'datetime',
            'proposal_deadline' => 'date',
            'min_amount'        => 'integer',
            'max_amount'        => 'integer',
            'envelope'          => 'integer',
            'config'            => 'array',
        ];
    }

    public function auditLabel(): string
    {
        return 'Seed grant call ' . $this->fiscal_year;
    }

    public function applications()
    {
        return $this->hasMany(SeedApplication::class);
    }

    /**
     * The call a page shows when none is named: the newest open call, else
     * the newest one people can see, else the newest draft.
     */
    public static function current(): ?self
    {
        return static::where('status', 'open')->latest('id')->first()
            ?? static::where('status', '!=', 'draft')->latest('id')->first()
            ?? static::latest('id')->first();
    }

    /** What this call asks for and how it is judged (its switched-on features and their settings). */
    public function form(): \App\Support\CallForm
    {
        $key = md5(json_encode([$this->config, $this->equipment_cap_percent]));
        if (($this->formCache[0] ?? null) !== $key) {
            $this->formCache = [$key, \App\Support\CallForm::for($this)];
        }

        return $this->formCache[1];
    }

    /** [settings fingerprint, CallForm], so a page builds the form once per call. */
    private array $formCache = [];

    /** Settings lock once a call has opened; only raising word limits stays allowed. */
    public function settingsLocked(): bool
    {
        return $this->exists && $this->getOriginal('status') !== 'draft';
    }

    /**
     * The call's own hue (tint-1 … tint-5), so the same call looks the same in every list.
     * Taken from the call's place in the order calls were created, not its id, so
     * calls next to each other never share a hue (ids can have gaps).
     */
    public function tintClass(): string
    {
        // Looked up once per request (once() is cleared between requests and tests).
        $order = once(fn () => static::orderBy('id')->pluck('id')->flip());
        $position = $order[$this->id] ?? (((int) $this->id) - 1);

        // Full class names, so the Tailwind build sees each one.
        return ['tint-1', 'tint-2', 'tint-3', 'tint-4', 'tint-5'][$position % 5];
    }

    public function isVisible(): bool
    {
        return $this->status !== 'draft';
    }

    public function acceptingConceptNotes(): bool
    {
        return $this->status === 'open'
            && (! $this->concept_deadline || $this->concept_deadline->copy()->endOfDay()->isFuture());
    }

    public function acceptingProposals(): bool
    {
        return ! $this->proposal_deadline || $this->proposal_deadline->copy()->endOfDay()->isFuture();
    }
}
