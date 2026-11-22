<?php

namespace App\Support;

use App\Models\Seed\SeedCall;

/**
 * What one call asks for and how it is judged: the features GMU switched on
 * for it, each with its own settings. Anything a call does not set falls back
 * to the seed grant's rules in App\Support\SeedGrant, so a call with no
 * settings behaves exactly as the FY 2026/27 seed grant.
 *
 * Features (keys in seed_calls.config):
 *   questions.concept.<key> / questions.proposal.<key>  on, limit, heading, question
 *   title_words                                         number
 *   mentor            on, words       co_investigators   on, words
 *   prior_grant       on, usd, words  declarations       on
 *   service_limit     on, years       equipment_cap      on (percent is the call's own column)
 *   documents.<kind>  off | optional | required
 *   criteria.<key>    on, max, min    pass_mark          number
 *   approval          drcp, dg        (GMU sign-off is always on)
 */
final class CallForm
{
    public const DOCUMENT_STATES = ['off' => 'Off', 'optional' => 'Optional', 'required' => 'Required'];

    /** The order of the approval chain; GMU is always first. */
    public const APPROVAL_LEVELS = ['gmu', 'drcp', 'dg'];

    private function __construct(private array $config, private int $equipmentPercent) {}

    public static function for(?SeedCall $call): self
    {
        return new self(array_replace_recursive(self::defaults(), (array) ($call?->config ?? [])), (int) ($call?->equipment_cap_percent ?? 20));
    }

    /** The seed grant's rules, as a full set of settings. */
    public static function defaults(): array
    {
        $questions = [];
        foreach (['concept' => SeedGrant::CONCEPT_SECTIONS, 'proposal' => SeedGrant::PROPOSAL_SECTIONS] as $stage => $sections) {
            foreach ($sections as $key => [$heading, $question, $limit]) {
                $questions[$stage][$key] = ['on' => true, 'limit' => $limit, 'heading' => $heading, 'question' => $question];
            }
        }

        $criteria = [];
        foreach (SeedGrant::CRITERIA as $key => [, , $max]) {
            $criteria[$key] = ['on' => true, 'max' => $max, 'min' => $key === 'science' ? $max * SeedGrant::MIN_SCIENCE_SHARE : null];
        }

        return [
            'questions'        => $questions,
            'title_words'      => SeedGrant::TITLE_MAX_WORDS,
            'mentor'           => ['on' => true, 'words' => SeedGrant::MENTOR_EXPERTISE_WORDS],
            'co_investigators' => ['on' => true, 'words' => SeedGrant::CO_INVESTIGATORS_WORDS],
            'prior_grant'      => ['on' => true, 'usd' => 10000, 'words' => SeedGrant::PRIOR_GRANT_WORDS],
            'declarations'     => ['on' => true],
            'service_limit'    => ['on' => true, 'years' => 5],
            'equipment_cap'    => ['on' => true],
            'documents'        => array_map(fn ($d) => $d[1] ? 'required' : 'optional', SeedGrant::DOCUMENT_KINDS),
            'criteria'         => $criteria,
            'pass_mark'        => SeedGrant::PASS_TOTAL,
            'approval'         => ['drcp' => true, 'dg' => true],
        ];
    }

    public function config(): array
    {
        return $this->config;
    }

    // ── Questions ───────────────────────────────────────────────────────────

    /** Switched-on sections of a stage, in the form's order: key => [heading, question, word limit]. */
    public function sections(string $stage): array
    {
        $out = [];
        foreach ($this->config['questions'][$stage] ?? [] as $key => $q) {
            if (! empty($q['on'])) {
                $out[$key] = [(string) $q['heading'], (string) ($q['question'] ?? ''), (int) $q['limit']];
            }
        }

        return $out;
    }

    /** The tick-box questions (7 and 8) this call asks. */
    public function choices(): array
    {
        return array_intersect_key(SeedGrant::CONCEPT_CHOICES, $this->sections('concept'));
    }

    public function sectionRequired(string $key, ?string $answer): bool
    {
        return SeedGrant::sectionRequired($key, $answer);
    }

    public function titleWords(): int
    {
        return (int) $this->config['title_words'];
    }

    // ── Section A ───────────────────────────────────────────────────────────

    /** Word limit for the mentor's expertise, or null when the call names no mentor. */
    public function mentorWords(): ?int
    {
        return $this->on('mentor') ? (int) $this->config['mentor']['words'] : null;
    }

    public function coInvestigatorWords(): ?int
    {
        return $this->on('co_investigators') ? (int) $this->config['co_investigators']['words'] : null;
    }

    /** ['usd' => threshold, 'words' => limit], or null when the call does not ask. */
    public function priorGrant(): ?array
    {
        return $this->on('prior_grant')
            ? ['usd' => (int) $this->config['prior_grant']['usd'], 'words' => (int) $this->config['prior_grant']['words']]
            : null;
    }

    public function declarations(): array
    {
        return $this->on('declarations') ? SeedGrant::DECLARATIONS : [];
    }

    // ── Eligibility and budget ──────────────────────────────────────────────

    /** Lead applicants must have served at NIMR for fewer than this many years; null when the call sets no limit. */
    public function serviceYears(): ?int
    {
        return $this->on('service_limit') ? (int) $this->config['service_limit']['years'] : null;
    }

    public function equipmentCap(): ?int
    {
        return $this->on('equipment_cap') ? $this->equipmentPercent : null;
    }

    /** Documents the full proposal takes: kind => [label, required]. */
    public function documents(): array
    {
        $out = [];
        foreach (SeedGrant::DOCUMENT_KINDS as $kind => [$label]) {
            $state = $this->config['documents'][$kind] ?? 'off';
            if ($state !== 'off') {
                $out[$kind] = [$label, $state === 'required'];
            }
        }

        return $out;
    }

    // ── Scored review ───────────────────────────────────────────────────────

    /** Switched-on criteria: key => [name, what reviewers assess, maximum score]. */
    public function criteria(): array
    {
        $out = [];
        foreach (SeedGrant::CRITERIA as $key => [$name, $what]) {
            $c = $this->config['criteria'][$key] ?? null;
            if ($c && ! empty($c['on'])) {
                $out[$key] = [$name, $what, (float) $c['max']];
            }
        }

        return $out;
    }

    public function scored(): bool
    {
        return $this->criteria() !== [];
    }

    public function maxTotal(): float
    {
        return array_sum(array_column($this->criteria(), 2));
    }

    public function passMark(): float
    {
        return (float) $this->config['pass_mark'];
    }

    /** Criteria with a minimum mean score of their own: key => minimum. */
    public function minimums(): array
    {
        $out = [];
        foreach (array_keys($this->criteria()) as $key) {
            $min = $this->config['criteria'][$key]['min'] ?? null;
            if ($min !== null && $min !== '') {
                $out[$key] = (float) $min;
            }
        }

        return $out;
    }

    /** Does a set of mean scores reach the call's threshold? A call without scoring has no threshold. */
    public function meetsThreshold(?array $means): bool
    {
        if (! $this->scored()) {
            return true;
        }
        if ($means === null || ($means['total'] ?? null) === null || $means['total'] < $this->passMark()) {
            return false;
        }
        foreach ($this->minimums() as $key => $min) {
            if (($means[$key] ?? 0) < $min) {
                return false;
            }
        }

        return true;
    }

    /** "and at least 12.5 on scientific quality and methods", or "" when no criterion has a minimum. */
    public function minimumsPhrase(string $joiner = ' and at least ', string $on = ' on '): string
    {
        return collect($this->minimums())
            ->map(fn ($min, $key) => $joiner . SeedGrant::trimNumber($min) . $on . mb_strtolower($this->criteria()[$key][0]))
            ->implode('');
    }

    /** Criteria that break ties, strongest first, from those switched on. */
    public function tieBreak(): array
    {
        return array_values(array_intersect(SeedGrant::TIE_BREAK, array_keys($this->criteria())));
    }

    // ── Approval ────────────────────────────────────────────────────────────

    /** The approval chain for this call, in order: always GMU, then DRCP and the DG if switched on. */
    public function approvalLevels(): array
    {
        return array_values(array_filter(self::APPROVAL_LEVELS, fn ($l) => $l === 'gmu' || ! empty($this->config['approval'][$l])));
    }

    /** The level after this one, or null when this is the last. */
    public function nextApprovalLevel(string $level): ?string
    {
        $levels = $this->approvalLevels();
        $i = array_search($level, $levels, true);

        return $i === false ? null : ($levels[$i + 1] ?? null);
    }

    private function on(string $feature): bool
    {
        return ! empty($this->config[$feature]['on']);
    }
}
