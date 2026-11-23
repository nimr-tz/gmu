<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('model_type')) {
            // Accept short name like "Award" or full class
            $short = $request->model_type;
            $query->where('model_type', 'like', "%{$short}");
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('model_label', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $logs  = $query->paginate(50)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        // The seed grant's records, as the owner's "GMU Admin" design lists them;
        // the hidden external-grant records join only when that feature is on.
        $modelTypes = ['SeedApplication', 'SeedCall', 'SeedReview', 'SeedReviewerAssignment', 'SeedApproval', 'User'];
        if (config('gmu.external_grants')) {
            $modelTypes = array_merge($modelTypes, ['Award', 'Proposal', 'FundingCall', 'Donor', 'Report',
                'EthicsRenewal', 'ComplianceCalendar', 'MonitoringVisit', 'AwardBudget', 'Document', 'DonorPolicy']);
        }

        $actions = ['created', 'updated', 'deleted', 'restored'];

        return view('admin.activity-log.index', compact('logs', 'users', 'modelTypes', 'actions'));
    }

    public function show(ActivityLog $log)
    {
        return view('admin.activity-log.show', compact('log'));
    }
}
