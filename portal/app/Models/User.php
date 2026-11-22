<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;

    /** Roles that make up the GMU itself, as opposed to the researchers it serves. */
    public const GMU_STAFF = ['coordinator', 'planning_officer', 'finance_officer', 'legal_officer', 'secretary', 'drcp', 'dg'];

    /** Roles that lead or contribute to research. */
    public const RESEARCH_ROLES = ['pi', 'research_scientist'];

    /** Which review track each role owns in the internal review workflow. */
    public const REVIEW_TRACKS = [
        'research_scientist' => 'technical',
        'legal_officer'      => 'compliance',
        'finance_officer'    => 'budget',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'phone', 'department', 'research_center',
        'is_active', 'must_change_password',
        'designation', 'employment_type', 'first_appointment_date',
    ];

    protected $hidden = ['password', 'remember_token'];

    /** Lead applicants must have served at NIMR for less than this many years. */
    public const MAX_SERVICE_YEARS_FOR_SEED_GRANT = 5;

    public const EMPLOYMENT_TYPES = ['permanent' => 'Permanent', 'contract' => 'Contract'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'first_appointment_date' => 'date',
            'profile_completed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** Account changes — role, activation, credentials — are audit-relevant (finding M16). */
    protected function auditableFields(): array
    {
        return ['name', 'email', 'role_id', 'phone', 'department', 'research_center', 'is_active',
            'designation', 'employment_type', 'first_appointment_date', 'nreims_id'];
    }

    /** Signed in through NREIMS at least once. */
    public function isNreimsLinked(): bool
    {
        return $this->nreims_id !== null;
    }

    /** NREIMS accounts confirm their post on first sign-in; local accounts are set up by GMU. */
    public function needsProfileCompletion(): bool
    {
        return $this->isNreimsLinked() && $this->profile_completed_at === null;
    }

    /** Whole years and months since first appointment, or null if not given. */
    public function serviceLength(): ?array
    {
        if (! $this->first_appointment_date) {
            return null;
        }
        $diff = $this->first_appointment_date->diff(now());

        return ['years' => $diff->y, 'months' => $diff->m];
    }

    /** Under the seed grant's default service limit for leading an application. */
    public function withinSeedGrantServiceLimit(): ?bool
    {
        return $this->withinServiceLimit(self::MAX_SERVICE_YEARS_FOR_SEED_GRANT);
    }

    /** Has served at NIMR for fewer than this many years; null when the appointment date is not recorded. */
    public function withinServiceLimit(int $years): ?bool
    {
        if (! $this->first_appointment_date) {
            return null;
        }

        return $this->first_appointment_date->copy()->addYears($years)->isFuture();
    }

    public function auditLabel(): string
    {
        return 'User: ' . ($this->name ?? '#' . $this->getKey()) . ' <' . $this->email . '>';
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role?->name, $roles, true);
    }

    public function isCoordinator(): bool { return $this->hasRole('coordinator'); }
    public function isPI(): bool { return $this->hasRole('pi'); }
    public function isFinanceOfficer(): bool { return $this->hasRole('finance_officer'); }
    public function isLegalOfficer(): bool { return $this->hasRole('legal_officer'); }
    public function isPlanningOfficer(): bool { return $this->hasRole('planning_officer'); }
    public function isResearchScientist(): bool { return $this->hasRole('research_scientist'); }
    public function isSecretary(): bool { return $this->hasRole('secretary'); }

    /** Works for the GMU (any officer role). */
    public function isGmuStaff(): bool
    {
        return $this->hasAnyRole(self::GMU_STAFF);
    }

    /** Leads or contributes to research rather than administering grants. */
    public function isResearcher(): bool
    {
        return $this->hasAnyRole(self::RESEARCH_ROLES);
    }

    /** The review track this user owns, or null if they review nothing. */
    public function reviewTrack(): ?string
    {
        return self::REVIEW_TRACKS[$this->role?->name] ?? null;
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'lead_pi_id');
    }

    public function assignedProposals()
    {
        return $this->hasMany(Proposal::class, 'assigned_officer_id');
    }

    public function callInterests()
    {
        return $this->hasMany(CallInterest::class);
    }

    public function proposalReviews()
    {
        return $this->hasMany(ProposalReview::class, 'reviewer_id');
    }

    public function notifications()
    {
        return $this->hasMany(GmuNotification::class);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }

    public function researcherProfile()
    {
        return $this->hasOne(ResearcherProfile::class);
    }

    public function fcoiDisclosures()
    {
        return $this->hasMany(FcoiDisclosure::class);
    }
}
