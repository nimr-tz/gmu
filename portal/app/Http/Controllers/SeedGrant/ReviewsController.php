<?php

namespace App\Http\Controllers\SeedGrant;

use App\Http\Controllers\Controller;
use App\Models\Seed\SeedReview;
use App\Models\Seed\SeedReviewerAssignment;
use App\Services\SeedGrant\Workflow;
use App\Support\SeedGrant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A reviewer's own reviews. Nothing about the application is shown until the
 * reviewer has declared that they have no conflict of interest.
 */
class ReviewsController extends Controller
{
    public function __construct(private Workflow $workflow) {}

    public function index(Request $request)
    {
        $assignments = SeedReviewerAssignment::with(['application.rounds.reviews', 'application.call'])
            ->where('reviewer_id', $request->user()->id)
            ->whereNull('removed_at')
            ->latest()
            ->get();

        [$todo, $done] = $assignments->partition(fn ($a) => $this->needsAction($a));

        // A scoring guide for each call the reviewer has work in (to do first), else the default call.
        $guideCalls = ($todo->isNotEmpty() ? $todo : $assignments)->map(fn ($a) => $a->application->call)->filter()->unique('id')->values();
        if ($guideCalls->isEmpty() && ($current = \App\Models\Seed\SeedCall::current())) {
            $guideCalls = collect([$current]);
        }

        return view('seed.reviews-index', ['todo' => $todo, 'done' => $done, 'guideCalls' => $guideCalls]);
    }

    public function show(Request $request, SeedReviewerAssignment $assignment)
    {
        $this->authorizeReviewer($request, $assignment);
        $assignment->load(['application.call', 'application.budgetItems', 'application.documents', 'application.rounds.reviews.assignment']);

        $application = $assignment->application;
        $round = $application->openRound($assignment->stage) ?? $application->latestRound($assignment->stage);
        $review = $round ? SeedReview::where('seed_review_round_id', $round->id)->where('seed_reviewer_assignment_id', $assignment->id)->first() : null;
        $previousRound = $round && $round->number > 1
            ? $application->stageRounds($assignment->stage)->firstWhere('number', $round->number - 1)
            : null;

        return view('seed.review-show', [
            'assignment'    => $assignment,
            'application'   => $application,
            'round'         => $round,
            'review'        => $review,
            'previousRound' => $previousRound,
            'previousReview'=> $previousRound?->reviews->firstWhere('seed_reviewer_assignment_id', $assignment->id),
        ]);
    }

    public function declare(Request $request, SeedReviewerAssignment $assignment)
    {
        $this->authorizeReviewer($request, $assignment);

        $data = $request->validate([
            'conflict' => ['required', Rule::in(['0', '1'])],
            'coi_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->workflow->declareInterest($assignment, $data['conflict'] === '1', $data['coi_note'] ?? null);

        return $data['conflict'] === '1'
            ? redirect()->route('seed.reviews.index')->with('success', 'Thank you. GMU will reassign this review.')
            : redirect()->route('seed.reviews.show', $assignment)->with('success', 'Declaration recorded. You can now review.');
    }

    public function save(Request $request, SeedReviewerAssignment $assignment)
    {
        $this->authorizeReviewer($request, $assignment);

        $rules = [
            'recommendation'    => ['nullable', Rule::in(array_keys(SeedReview::RECOMMENDATIONS))],
            'comments'          => ['nullable', 'string', 'max:10000'],
            'confidential_note' => ['nullable', 'string', 'max:5000'],
        ];
        foreach ($assignment->application->call->form()->criteria() as $key => [, , $max]) {
            $rules["scores.{$key}"] = ['nullable', 'numeric', 'min:0', "max:{$max}"];
        }
        $data = $request->validate($rules);

        $submit = $request->input('action') === 'submit';
        $this->workflow->saveReview($assignment, $data, $submit);

        return $submit
            ? redirect()->route('seed.reviews.index')->with('success', 'Review submitted. Thank you.')
            : redirect()->route('seed.reviews.show', $assignment)->with('success', 'Draft saved.');
    }

    private function authorizeReviewer(Request $request, SeedReviewerAssignment $assignment): void
    {
        abort_unless($assignment->reviewer_id === $request->user()->id && $assignment->removed_at === null, 403);
    }

    /** Declaration outstanding, or an open round without this reviewer's submitted review. */
    public function needsAction(SeedReviewerAssignment $assignment): bool
    {
        if ($assignment->coi_status === 'pending') {
            return true;
        }
        if ($assignment->coi_status !== 'none') {
            return false;
        }
        $round = $assignment->application->openRound($assignment->stage);

        return $round && ! $round->reviews->where('seed_reviewer_assignment_id', $assignment->id)->whereNotNull('submitted_at')->count();
    }
}
