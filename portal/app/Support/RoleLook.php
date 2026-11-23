<?php

namespace App\Support;

/**
 * How each role looks and what it may do, from the owner's "GMU Admin" design:
 * the chip on People and roles, the dot on its tile, and "What this role can do".
 * Role names shown come from the roles table (the applicant role, key "pi", shows as Staff).
 */
class RoleLook
{
    /** The roles the design always shows a tile for, in its order. Others get one when someone holds them. */
    public const TILE_ORDER = ['coordinator', 'planning_officer', 'drcp', 'dg', 'pi'];

    /** The role's name as the design shows it: "DRCP" rather than its full title. */
    public static function label(?string $role, ?string $displayName): string
    {
        return $role === 'drcp' ? 'DRCP' : ($displayName ?? 'No role');
    }

    public static function chip(?string $role): string
    {
        return match ($role) {
            'coordinator'      => 'bg-foreground text-white',
            'drcp', 'dg'       => 'bg-indigo-50 text-indigo-700',
            null, ''           => 'bg-danger-soft text-danger-ink',
            'planning_officer' => 'bg-gray-100 text-foreground ring-1 ring-inset ring-input',
            default            => 'bg-card text-gray-700 ring-1 ring-inset ring-input',
        };
    }

    public static function dot(?string $role): string
    {
        return match ($role) {
            'coordinator'      => 'bg-foreground',
            'planning_officer' => 'bg-gray-600',
            'drcp', 'dg'       => 'bg-indigo-500',
            'pi'               => 'bg-primary',
            null, ''           => 'bg-danger',
            default            => 'bg-gray-400',
        };
    }

    public static function isApprover(?string $role): bool
    {
        return in_array($role, ['drcp', 'dg'], true);
    }

    /** @return string[] */
    public static function abilities(?string $role): array
    {
        return match ($role) {
            'coordinator'        => ['Runs every call: screening, reviewers, decisions and agreements', 'GMU sign-off on recommended proposals', 'Adds people and sets their roles', 'Reads the audit register'],
            'planning_officer'   => ['Runs every call: screening, reviewers, decisions and agreements', 'GMU sign-off on recommended proposals'],
            'drcp'               => ['Clears recommended proposals for the Director General'],
            'dg'                 => ['Final approval of recommended proposals, with or without conditions'],
            'pi'                 => ['Applies to open calls as lead applicant', 'Reviews concept notes and proposals when GMU assigns them'],
            'research_scientist' => ['Applies to open calls as lead applicant', 'Reviews concept notes and proposals when GMU assigns them', 'Works in the external-grant pages, which are switched off'],
            'finance_officer'    => ['Works in the external-grant pages, which are switched off', 'Will sign off proposal budgets once that step is added'],
            'legal_officer'      => ['Works in the external-grant pages, which are switched off', 'Will sign off legal references on proposals once that step is added'],
            'secretary'          => ['Works in the external-grant pages, which are switched off'],
            null, ''             => ['Nothing yet: an account without a role sees no grant data'],
            default              => ['Reviews when GMU assigns them'],
        };
    }

    public static function initials(string $name): string
    {
        return mb_strtoupper(collect(explode(' ', preg_replace('/^(Dr|Prof|Mr|Ms|Mrs|Miss)\.?\s+/i', '', $name)))
            ->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    }

    /** An avatar's tint, fixed per person. */
    public static function avatar(int $id): string
    {
        return ['bg-blue-50 text-ok-ink', 'bg-violet-50 text-violet-700', 'bg-sky-100 text-sky-700', 'bg-pink-100 text-pink-700', 'bg-warn-soft text-warn-ink'][$id % 5];
    }
}
