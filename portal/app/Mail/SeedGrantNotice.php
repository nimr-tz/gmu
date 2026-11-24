<?php

namespace App\Mail;

use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\User;
use App\Support\NotificationLook;
use App\Support\SeedGrant as S;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The email copy of a seed grant notification, from the owner's "Email" design: the same
 * headline and message the person sees in the portal, a band in the colour of who it is for
 * (staff blue, reviewers emerald, approvers indigo, decisions and GMU ink), the key facts,
 * one button to the page where they act, and why they got it.
 */
class SeedGrantNotice extends Mailable
{
    /** Inline colours for each kind (emails need hex; copied from the tokens). */
    public const KINDS = [
        'action'   => ['band' => '#1D4FC4', 'muted' => '#BCD0FB', 'chip' => '#F59E0B', 'chipInk' => '#451A03', 'kicker' => 'Action needed'],
        'deadline' => ['band' => '#1D4FC4', 'muted' => '#BCD0FB', 'chip' => 'rgba(255,255,255,0.16)', 'chipInk' => '#FFFFFF', 'kicker' => 'Reminder'],
        'call'     => ['band' => '#1D4FC4', 'muted' => '#BCD0FB', 'chip' => 'rgba(255,255,255,0.16)', 'chipInk' => '#FFFFFF', 'kicker' => 'New call'],
        'review'   => ['band' => '#047857', 'muted' => '#A7F3D0', 'chip' => 'rgba(255,255,255,0.16)', 'chipInk' => '#FFFFFF', 'kicker' => 'Review'],
        'approval' => ['band' => '#4338CA', 'muted' => '#C7D2FE', 'chip' => 'rgba(255,255,255,0.16)', 'chipInk' => '#FFFFFF', 'kicker' => 'Awaiting decision'],
        'outcome'  => ['band' => '#18181B', 'muted' => '#A1A1AA', 'chip' => 'rgba(255,255,255,0.16)', 'chipInk' => '#FFFFFF', 'kicker' => 'Decision'],
        'gmu'      => ['band' => '#18181B', 'muted' => '#A1A1AA', 'chip' => '#F59E0B', 'chipInk' => '#451A03', 'kicker' => 'Action needed'],
        'update'   => ['band' => '#18181B', 'muted' => '#A1A1AA', 'chip' => 'rgba(255,255,255,0.16)', 'chipInk' => '#FFFFFF', 'kicker' => 'Update'],
    ];

    /**
     * @param  array<string, string>  $style  one of KINDS
     * @param  array<int, array{0:string,1:string}>  $facts
     */
    public function __construct(
        public string $recipientName,
        public string $headline,
        public string $body,
        public string $actionUrl,
        public string $reference,
        public string $button,
        public array $style,
        public array $facts,
        public string $why,
        // How they sign in, for the line under the button; and whether the portal holds a copy.
        public string $signIn = 'with NREIMS',
        public bool $inPortal = true,
    ) {}

    /** The email for one person about one application or call. */
    public static function for(User $user, string $type, string $headline, string $body, string $url, SeedApplication|SeedCall $subject): self
    {
        $application = $subject instanceof SeedApplication ? $subject : null;
        $isPi = $application && $user->id === $application->pi_id;
        $gmu = $user->can('manage-seed-grants');

        $kind = match (true) {
            $subject instanceof SeedCall                          => 'call',
            $type === 'seed_reminder'                             => 'deadline',
            $type === 'seed_approval'                             => 'approval',
            $type === 'seed_review' && ! $isPi && ! $gmu          => 'review',
            $isPi && $type === 'seed_agreement' && $application->status === S::AGREEMENT_ISSUED => 'action',
            $isPi && in_array($type, ['seed_decision', 'seed_screened', 'seed_approved'], true) => 'outcome',
            $isPi                                                 => 'update',
            $gmu && in_array($type, ['seed_approved'], true)     => 'outcome',
            $gmu                                                  => 'gmu',
            default                                               => 'update',
        };

        return new self(
            $user->name, $headline, $body, $url,
            $application ? $application->reference : 'Call ' . $subject->code,
            self::button($kind, $type, $isPi, $application, $subject),
            self::KINDS[$kind],
            self::facts($kind, $type, $application, $subject),
            NotificationLook::why($type, $user, $application),
        );
    }

    private static function button(string $kind, string $type, bool $isPi, ?SeedApplication $application, SeedApplication|SeedCall $subject): string
    {
        return match (true) {
            $subject instanceof SeedCall => 'Read the call',
            $kind === 'action'           => 'Read the agreement',
            $kind === 'review'           => 'Open my reviews',
            $kind === 'approval'         => 'Open awaiting decision',
            $isPi && $application?->isEditableByApplicant() => 'Continue the application',
            $isPi && $type === 'seed_decision' => 'Read their comments',
            $isPi                        => 'Open the application',
            default                      => 'Open the submission',
        };
    }

    /** @return array<int, array{0:string,1:string}> */
    private static function facts(string $kind, string $type, ?SeedApplication $application, SeedApplication|SeedCall $subject): array
    {
        if ($subject instanceof SeedCall) {
            return array_values(array_filter([
                $subject->concept_deadline ? ['Concept notes close', $subject->concept_deadline->format('j M Y') . ', end of day EAT'] : null,
                ['Award', 'TZS ' . number_format($subject->min_amount / 1e6, 1) . 'M – ' . number_format($subject->max_amount / 1e6, 1) . 'M'],
            ]));
        }

        $round = $application->openRound();
        $means = $application->meanScores();

        return array_values(array_filter(match (true) {
            $kind === 'action'   => [['Award', S::tzs($application->approved_amount)], ['Project', $application->title]],
            $kind === 'review'   => [$round?->due_at ? ['Review due', $round->due_at->format('j M Y')] : null],
            $kind === 'approval' => [[$application->reference, ($means ? 'Mean ' . $means['total'] . ' · ' : '') . 'TZS ' . number_format($application->requestedAmount() / 1e6, 1) . 'M']],
            $type === 'seed_decision' && ($due = $application->latestRound()?->response_due_at) => [['Response due', $due->format('j M Y')]],
            default              => [],
        }));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->headline);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.seed-grant-notice');
    }
}
