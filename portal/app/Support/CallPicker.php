<?php

namespace App\Support;

use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Which call a per-call page (submissions, ranking, approvals) shows.
 * ?call=<id> picks one; otherwise the page opens on the call where work is
 * waiting, else the default call.
 */
final class CallPicker
{
    /** @param  array<string>|null  $waiting  statuses that count as "work waiting" for this page */
    public static function pick(Request $request, ?array $waiting = null, bool $includeDrafts = true): ?SeedCall
    {
        if ($id = $request->query('call')) {
            $call = SeedCall::find($id);
            abort_unless($call && ($includeDrafts || $call->isVisible()), 404);

            return $call;
        }

        if ($waiting) {
            $callId = SeedApplication::whereIn('status', $waiting)->latest('updated_at')->value('seed_call_id');
            if ($callId) {
                return SeedCall::find($callId);
            }
        }

        return SeedCall::current();
    }

    /** The calls offered in the picker. */
    public static function options(bool $includeDrafts = true): Collection
    {
        return SeedCall::when(! $includeDrafts, fn ($q) => $q->where('status', '!=', 'draft'))->latest('id')->get(['id', 'title', 'code', 'status', 'fiscal_year']);
    }
}
