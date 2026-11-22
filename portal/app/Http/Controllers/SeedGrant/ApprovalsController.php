<?php

namespace App\Http\Controllers\SeedGrant;

use App\Http\Controllers\Controller;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\User;
use App\Services\SeedGrant\Workflow;
use App\Support\SeedGrant;
use Illuminate\Http\Request;

/** GMU sign-off, DRCP clearance and the Director General's decision. */
class ApprovalsController extends Controller
{
    public function __construct(private Workflow $workflow) {}

    /** The approval level this user acts at, if any. */
    public static function levelFor(User $user): ?string
    {
        return match (true) {
            $user->hasRole('dg')                 => 'dg',
            $user->hasRole('drcp')               => 'drcp',
            $user->can('manage-seed-grants')     => 'gmu',
            default                              => null,
        };
    }

    public const QUEUE_STATUS = ['gmu' => SeedGrant::RECOMMENDED, 'drcp' => SeedGrant::WITH_DRCP, 'dg' => SeedGrant::WITH_DG];

    public function index(Request $request)
    {
        $level = self::levelFor($request->user());
        abort_unless($level, 403);
        $call = \App\Support\CallPicker::pick($request, [self::QUEUE_STATUS[$level]], $level === 'gmu');
        abort_unless($call, 404);
        $calls = \App\Support\CallPicker::options($level === 'gmu');

        $base = SeedApplication::with(['pi', 'budgetItems', 'rounds.reviews', 'approvals'])->where('seed_call_id', $call->id);

        $queue = RankingController::rank((clone $base)->where('status', self::QUEUE_STATUS[$level])->get());
        $decided = (clone $base)->whereIn('status', [SeedGrant::APPROVED, SeedGrant::AGREEMENT_ISSUED, SeedGrant::AGREEMENT_ACCEPTED, SeedGrant::AWARDED])->get();
        $notRecommended = RankingController::rank((clone $base)->where('status', SeedGrant::NOT_RECOMMENDED)->get());
        $inProgress = (clone $base)->whereIn('status', [SeedGrant::RECOMMENDED, SeedGrant::WITH_DRCP, SeedGrant::WITH_DG])
            ->where('status', '!=', self::QUEUE_STATUS[$level])->get();

        return view('seed.approvals-index', compact('call', 'calls', 'level', 'queue', 'decided', 'notRecommended', 'inProgress'));
    }

    public function show(Request $request, SeedApplication $application)
    {
        $this->authorize('view', $application);
        abort_unless(self::levelFor($request->user()), 403);

        $application->load(['pi', 'call', 'budgetItems', 'documents', 'approvals.decider', 'rounds.reviews.assignment']);

        return view('seed.approval-show', [
            'application' => $application,
            'level'       => self::levelFor($request->user()),
            'means'       => $application->meanScores(),
        ]);
    }

    public function decide(Request $request, SeedApplication $application)
    {
        $level = self::levelFor($request->user());
        abort_unless($level, 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,conditions,returned'],
            'note'     => ['nullable', 'string', 'max:3000'],
        ]);

        $this->workflow->approve($application, $request->user(), $level, $data['decision'], $data['note'] ?? null);

        $next = $application->call->form()->nextApprovalLevel($level);
        $message = match (true) {
            $data['decision'] === 'returned' => 'Returned to GMU.',
            $next === 'drcp'                 => 'Sent to the DRCP.',
            $next === 'dg'                   => 'Cleared and sent to the Director General.',
            default                          => 'Approved. The PI and GMU have been told.',
        };

        return $level === 'gmu'
            ? back()->with('success', $message)
            : redirect()->route('seed.approvals.index')->with('success', $message);
    }
}
