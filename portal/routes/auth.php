<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\NreimsLoginController;
use App\Http\Controllers\Auth\ProfileCompletionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Self-registration is deliberately disabled: GMU accounts are provisioned
    // by the Coordinator through admin/users. See audit finding C3.

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // NREIMS single sign-on: the front door for everyone (see NreimsLoginController).
    Route::middleware('throttle:20,1')->group(function () {
        Route::get('auth/nreims', [NreimsLoginController::class, 'redirect'])->name('nreims.redirect');
        Route::get('auth/nreims/callback', [NreimsLoginController::class, 'callback'])->name('nreims.callback');
    });

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    // First sign-in through NREIMS, in four steps: welcome, what came from
    // NREIMS, the person's post at NIMR (the only step that saves), ready.
    Route::get('welcome',         [ProfileCompletionController::class, 'welcome'])->name('profile.complete');
    Route::get('welcome/nreims',  [ProfileCompletionController::class, 'nreims'])->name('profile.complete.nreims');
    Route::get('welcome/details', [ProfileCompletionController::class, 'edit'])->name('profile.complete.details');
    Route::post('welcome',        [ProfileCompletionController::class, 'update'])->name('profile.complete.store');
    Route::get('welcome/ready',   [ProfileCompletionController::class, 'ready'])->name('profile.complete.ready');

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
