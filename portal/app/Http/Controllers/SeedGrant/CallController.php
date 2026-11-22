<?php

namespace App\Http\Controllers\SeedGrant;

use App\Http\Controllers\Controller;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Services\SeedGrant\Workflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CallController extends Controller
{
    public function show(Request $request, Workflow $workflow)
    {
        $call = SeedCall::current();
        if (! $call && $request->user()->can('manage-seed-grants')) {
            return redirect()->route('seed.call.edit');
        }
        abort_if(! $call || (! $call->isVisible() && $request->user()->cannot('manage-seed-grants')), 404);

        $user = $request->user();

        return view('seed.call', [
            'call'        => $call,
            'application' => SeedApplication::where('seed_call_id', $call->id)->where('pi_id', $user->id)->first(),
            'problems'    => $workflow->eligibilityProblems($user),
        ]);
    }

    public function edit(Request $request)
    {
        $this->authorize('manage-seed-grants');

        return view('seed.call-edit', ['call' => SeedCall::current() ?? new SeedCall(['min_amount' => 2000000, 'max_amount' => 10000000, 'duration_months' => 12, 'equipment_cap_percent' => 20])]);
    }

    public function update(Request $request)
    {
        $this->authorize('manage-seed-grants');
        $call = SeedCall::current() ?? new SeedCall();

        $data = $request->validate([
            'title'                 => ['required', 'string', 'max:255'],
            'code'                  => ['required', 'string', 'max:12', 'alpha_num', Rule::unique('seed_calls', 'code')->ignore($call->id)],
            'fiscal_year'           => ['required', 'string', 'max:12'],
            'summary'               => ['nullable', 'string', 'max:2000'],
            'min_amount'            => ['required', 'integer', 'min:0'],
            'max_amount'            => ['required', 'integer', 'gte:min_amount'],
            'envelope'              => ['nullable', 'integer', 'min:0'],
            'duration_months'       => ['required', 'integer', 'between:1,36'],
            'equipment_cap_percent' => ['required', 'integer', 'between:0,100'],
            'concept_deadline'      => ['nullable', 'date'],
            'proposal_deadline'     => ['nullable', 'date', 'after_or_equal:concept_deadline'],
            'status'                => ['required', Rule::in(array_keys(SeedCall::STATUSES))],
        ]);

        $call->fill($data)->save();

        return redirect()->route('seed.call')->with('success', 'Call settings saved.');
    }
}
