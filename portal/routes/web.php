<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CallsController;
use App\Http\Controllers\DonorsController;
use App\Http\Controllers\ProposalApprovalsController;
use App\Http\Controllers\ProposalDocumentsController;
use App\Http\Controllers\ProposalTeamController;
use App\Http\Controllers\ProposalsController;
use App\Http\Controllers\AwardsController;
use App\Http\Controllers\AwardBudgetsController;
use App\Http\Controllers\AwardAmendmentsController;
use App\Http\Controllers\AwardCloseoutController;
use App\Http\Controllers\ExpendituresController;
use App\Http\Controllers\SubAwardsController;
use App\Http\Controllers\ComplianceCalendarController;
use App\Http\Controllers\MonitoringVisitsController;
use App\Http\Controllers\EthicsRenewalsController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\DonorPoliciesController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ResearcherProfileController;
use App\Http\Controllers\LibraryItemsController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\SeedGrant;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard',  [DashboardController::class,  'index'])->name('dashboard');
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationsController::class, 'markRead'])->name('notifications.read');


    // ── Seed grant ─────────────────────────────────────────────────────────
    Route::prefix('seed-grant')->name('seed.')->group(function () {
        // Calls: as many as NIMR runs, each with its own features and settings.
        Route::get('calls',                [SeedGrant\CallController::class, 'index'])->name('calls.index');
        Route::get('calls/new',            [SeedGrant\CallController::class, 'create'])->name('calls.create');
        Route::post('calls',               [SeedGrant\CallController::class, 'store'])->name('calls.store');
        Route::get('calls/{call}',         [SeedGrant\CallController::class, 'show'])->name('calls.show');
        Route::get('calls/{call}/settings',[SeedGrant\CallController::class, 'edit'])->name('calls.edit');
        Route::put('calls/{call}',         [SeedGrant\CallController::class, 'update'])->name('calls.update');
        Route::post('calls/{call}/apply',  [SeedGrant\ApplicationController::class, 'start'])->name('calls.apply');
        // The earlier single-call addresses, kept so old links still land somewhere sensible.
        Route::get('call',      [SeedGrant\CallController::class, 'current'])->name('call');
        Route::get('call/edit', [SeedGrant\CallController::class, 'editCurrent'])->name('call.edit');

        // Applicant
        Route::post('apply',          [SeedGrant\ApplicationController::class, 'start'])->name('apply');   // the default call
        Route::get('my-application',  [SeedGrant\ApplicationController::class, 'mine'])->name('mine');
        Route::scopeBindings()->prefix('applications/{application}')->name('applications.')->group(function () {
            Route::get('/',                      [SeedGrant\ApplicationController::class, 'show'])->name('show');
            Route::get('edit',                   [SeedGrant\ApplicationController::class, 'edit'])->name('edit');
            Route::put('/',                      [SeedGrant\ApplicationController::class, 'update'])->name('update');
            // PUT as well: the editor's Submit button shares the Save form, which spoofs PUT.
            Route::match(['post', 'put'], 'submit', [SeedGrant\ApplicationController::class, 'submit'])->name('submit');
            Route::post('documents',             [SeedGrant\ApplicationController::class, 'storeDocument'])->name('documents.store');
            Route::get('documents/{document}',   [SeedGrant\ApplicationController::class, 'downloadDocument'])->name('documents.download');
            Route::delete('documents/{document}',[SeedGrant\ApplicationController::class, 'destroyDocument'])->name('documents.destroy');
            Route::get('agreement',              [SeedGrant\ApplicationController::class, 'agreement'])->name('agreement');
            Route::post('agreement/accept',      [SeedGrant\ApplicationController::class, 'acceptAgreement'])->name('agreement.accept');
            Route::get('agreement/signed',       [SeedGrant\ApplicationController::class, 'signedAgreement'])->name('agreement.signed');
        });

        // GMU
        Route::get('submissions', [SeedGrant\SubmissionsController::class, 'index'])->name('submissions.index');
        Route::scopeBindings()->prefix('submissions/{application}')->name('submissions.')->group(function () {
            Route::get('/',                             [SeedGrant\SubmissionsController::class, 'show'])->name('show');
            Route::post('screen',                       [SeedGrant\SubmissionsController::class, 'screen'])->name('screen');
            Route::post('reviewers',                    [SeedGrant\SubmissionsController::class, 'assign'])->name('assign');
            Route::delete('reviewers/{assignment}',     [SeedGrant\SubmissionsController::class, 'unassign'])->name('unassign');
            Route::post('start-review',                 [SeedGrant\SubmissionsController::class, 'startReview'])->name('start-review');
            Route::post('decide',                       [SeedGrant\SubmissionsController::class, 'decide'])->name('decide');
            Route::post('issue-agreement',              [SeedGrant\SubmissionsController::class, 'issueAgreement'])->name('issue-agreement');
            Route::post('signed-agreement',             [SeedGrant\SubmissionsController::class, 'uploadSignedAgreement'])->name('signed-agreement');
        });
        Route::get('ranking',        [SeedGrant\RankingController::class, 'index'])->name('ranking');
        Route::post('ranking/send',  [SeedGrant\RankingController::class, 'send'])->name('ranking.send');

        // GMU sign-off, DRCP and the Director General
        Route::get('approvals',                    [SeedGrant\ApprovalsController::class, 'index'])->name('approvals.index');
        Route::get('approvals/{application}',      [SeedGrant\ApprovalsController::class, 'show'])->name('approvals.show');
        Route::post('approvals/{application}',     [SeedGrant\ApprovalsController::class, 'decide'])->name('approvals.decide');

        // Reviewers
        Route::get('reviews',                      [SeedGrant\ReviewsController::class, 'index'])->name('reviews.index');
        Route::get('reviews/{assignment}',         [SeedGrant\ReviewsController::class, 'show'])->name('reviews.show');
        Route::post('reviews/{assignment}/declare',[SeedGrant\ReviewsController::class, 'declare'])->name('reviews.declare');
        Route::put('reviews/{assignment}',         [SeedGrant\ReviewsController::class, 'save'])->name('reviews.save');
    });

    Route::middleware(['role:coordinator'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UsersController::class);
        Route::get('activity-log',       [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('activity-log/{log}', [ActivityLogController::class, 'show'])->name('activity-log.show');
    });

    // External grants modules. Hidden unless GMU_EXTERNAL_GRANTS=true (config/gmu.php);
    // switched off they answer 404 rather than being deleted.
    Route::middleware('feature:external_grants')->group(function () {
        Route::get('/analytics',  [AnalyticsController::class,  'index'])->name('analytics')
            ->middleware('role:coordinator,planning_officer,finance_officer');

        Route::resource('calls', CallsController::class);
        Route::get('calls/{call}/document',       [CallsController::class, 'downloadDocument'])->name('calls.document');
        Route::post('calls/{call}/interest',     [CallsController::class, 'expressInterest'])->name('calls.interest');
        Route::post('calls/{call}/select-team',  [CallsController::class, 'selectTeam'])->name('calls.select-team');
        Route::resource('proposals', ProposalsController::class);
        Route::post('proposals/{proposal}/review', [ProposalsController::class, 'submitReview'])->name('proposals.review');
        Route::post('proposals/{proposal}/approval', [ProposalApprovalsController::class, 'store'])->name('proposals.approval');

        // Proposal artefacts and team — the pre-award records that had models and
        // tables but no way to create them (finding M7).
        Route::scopeBindings()->group(function () {
            Route::post('proposals/{proposal}/documents',                [ProposalDocumentsController::class, 'store'])->name('proposals.documents.store');
            Route::get('proposals/{proposal}/documents/{document}',      [ProposalDocumentsController::class, 'download'])->name('proposals.documents.download');
            Route::delete('proposals/{proposal}/documents/{document}',   [ProposalDocumentsController::class, 'destroy'])->name('proposals.documents.destroy');

            Route::post('proposals/{proposal}/team',                     [ProposalTeamController::class, 'store'])->name('proposals.team.store');
            Route::delete('proposals/{proposal}/team/{member}',          [ProposalTeamController::class, 'destroy'])->name('proposals.team.destroy');
        });
        Route::resource('awards', AwardsController::class);
        Route::get('awards/{award}/contract',     [AwardsController::class, 'downloadContract'])->name('awards.contract');

        // Closeout & lessons learned — GM-BPF workflow D
        Route::get('awards/{award}/closeout',          [AwardCloseoutController::class, 'show'])->name('awards.closeout');
        Route::patch('awards/{award}/closeout',        [AwardCloseoutController::class, 'update'])->name('awards.closeout.update');
        Route::post('awards/{award}/closeout/complete',[AwardCloseoutController::class, 'complete'])->name('awards.closeout.complete');

        // Award sub-resources. scopeBindings() makes Laravel resolve each child
        // through its parent award, so a budget line belonging to another award
        // cannot be reached through this one (finding H5).
        Route::scopeBindings()->group(function () {
            Route::post('awards/{award}/budgets',              [AwardBudgetsController::class, 'store'])->name('awards.budgets.store');
            Route::delete('awards/{award}/budgets/{budget}',   [AwardBudgetsController::class, 'destroy'])->name('awards.budgets.destroy');

            Route::post('awards/{award}/compliance',           [ComplianceCalendarController::class, 'store'])->name('awards.compliance.store');
            Route::patch('awards/{award}/compliance/{item}',   [ComplianceCalendarController::class, 'update'])->name('awards.compliance.update');
            Route::delete('awards/{award}/compliance/{item}',  [ComplianceCalendarController::class, 'destroy'])->name('awards.compliance.destroy');

            Route::post('awards/{award}/monitoring',           [MonitoringVisitsController::class, 'store'])->name('awards.monitoring.store');
            Route::get('awards/{award}/monitoring/{visit}/report', [MonitoringVisitsController::class, 'downloadReport'])->name('awards.monitoring.report');
            Route::delete('awards/{award}/monitoring/{visit}', [MonitoringVisitsController::class, 'destroy'])->name('awards.monitoring.destroy');

            Route::post('awards/{award}/ethics',               [EthicsRenewalsController::class, 'store'])->name('awards.ethics.store');
            Route::patch('awards/{award}/ethics/{renewal}',    [EthicsRenewalsController::class, 'update'])->name('awards.ethics.update');
            Route::get('awards/{award}/ethics/{renewal}/certificate', [EthicsRenewalsController::class, 'downloadCertificate'])->name('awards.ethics.certificate');

            // Sub-awards to partner institutions
            Route::post('awards/{award}/sub-awards',                        [SubAwardsController::class, 'store'])->name('awards.sub-awards.store');
            Route::patch('awards/{award}/sub-awards/{subAward}',            [SubAwardsController::class, 'update'])->name('awards.sub-awards.update');
            Route::get('awards/{award}/sub-awards/{subAward}/agreement',    [SubAwardsController::class, 'downloadAgreement'])->name('awards.sub-awards.agreement');
            Route::delete('awards/{award}/sub-awards/{subAward}',           [SubAwardsController::class, 'destroy'])->name('awards.sub-awards.destroy');

            // Amendments and no-cost extensions
            Route::post('awards/{award}/amendments',                       [AwardAmendmentsController::class, 'store'])->name('awards.amendments.store');
            Route::patch('awards/{award}/amendments/{amendment}',          [AwardAmendmentsController::class, 'update'])->name('awards.amendments.update');
            Route::get('awards/{award}/amendments/{amendment}/approval',   [AwardAmendmentsController::class, 'downloadApproval'])->name('awards.amendments.approval');
            Route::delete('awards/{award}/amendments/{amendment}',         [AwardAmendmentsController::class, 'destroy'])->name('awards.amendments.destroy');

            // Expenditure ledger
            Route::post('awards/{award}/expenditures',                      [ExpendituresController::class, 'store'])->name('awards.expenditures.store');
            Route::delete('awards/{award}/expenditures/{expenditure}',      [ExpendituresController::class, 'destroy'])->name('awards.expenditures.destroy');
            Route::delete('awards/{award}/ethics/{renewal}',   [EthicsRenewalsController::class, 'destroy'])->name('awards.ethics.destroy');
        });

        Route::resource('reports', ReportsController::class);
        Route::get('reports/{report}/file',       [ReportsController::class, 'download'])->name('reports.download');
        Route::resource('documents', DocumentsController::class);
        Route::resource('donor-policies', DonorPoliciesController::class);
        Route::middleware(['role:coordinator,planning_officer'])->group(function () {
            Route::resource('donors', DonorsController::class);
        });

        // AI Intelligence. Finding M14: these endpoints call a paid API, so they
        // are rate limited per user as well as role-gated.
        Route::middleware(['role:coordinator,planning_officer,finance_officer', 'throttle:20,1'])->group(function () {
            Route::get('ai',                        [AiController::class, 'hub'])->name('ai.hub');
            Route::get('ai/matching',               [AiController::class, 'matchingIndex'])->name('ai.matching');
            Route::get('ai/matching/{call}',        [AiController::class, 'matchCall'])->name('ai.match-call');
            Route::get('ai/risk',                   [AiController::class, 'riskIndex'])->name('ai.risk');
            Route::get('ai/risk/{award}',           [AiController::class, 'riskAnalysis'])->name('ai.risk-analysis');
        });
        Route::middleware('throttle:20,1')->group(function () {
            Route::post('ai/report-quality/{report}', [AiController::class, 'reportQuality'])->name('ai.report-quality');
            Route::post('ai/draft-section',           [AiController::class, 'draftSection'])->name('ai.draft-section');
        });

        // Proposal Library
        Route::resource('library', LibraryItemsController::class);
        Route::post('library/{library}/approve', [LibraryItemsController::class, 'toggleApproval'])->name('library.approve');
        Route::get('library/{library}/copy',     [LibraryItemsController::class, 'copy'])->name('library.copy');

        // Researcher profiles & FCOI
        Route::get('researchers',               [ResearcherProfileController::class, 'index'])->name('researchers.index');
        Route::get('researchers/{user}',        [ResearcherProfileController::class, 'show'])->name('researchers.show');
        Route::get('researchers/{user}/edit',   [ResearcherProfileController::class, 'edit'])->name('researchers.edit');
        Route::put('researchers/{user}',        [ResearcherProfileController::class, 'update'])->name('researchers.update');
        Route::post('researchers/{user}/fcoi',  [ResearcherProfileController::class, 'storeFcoi'])->name('researchers.fcoi.store');
        Route::patch('fcoi/{disclosure}/review',[ResearcherProfileController::class, 'reviewFcoi'])->name('fcoi.review');

    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
