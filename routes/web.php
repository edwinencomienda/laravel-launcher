<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'index'])->name('onboarding');
Route::post('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'store'])->name('onboarding.store');
Route::get('/api/verify-dns', [\App\Http\Controllers\DnsVerificationController::class, 'verify'])->name('verify-dns');

Route::get('/', function () {
    if (! User::count()) {
        return redirect()->route('onboarding');
    }

    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::get('/install', [\App\Http\Controllers\InstallScriptController::class, 'index']);

Route::get('/github/redirect', [\App\Http\Controllers\GitHubAppController::class, 'redirect'])->name('github.redirect');
Route::get('/github/callback', [\App\Http\Controllers\GitHubAppController::class, 'callback'])->name('github.callback');
Route::get('/github/install', [\App\Http\Controllers\GitHubAppController::class, 'install'])->name('github.install');
Route::get('/github/setup', [\App\Http\Controllers\GitHubAppController::class, 'setup'])->name('github.setup');
Route::post('/github/webhook', [\App\Http\Controllers\GitHubAppController::class, 'handleWebhook'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('github.webhook');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
