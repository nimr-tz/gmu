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

    protected function casts(): array
    {
        return [
            'concept_deadline'  => 'date',
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
