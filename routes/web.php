<?php

use App\Models\User;
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

Route::get('/install', function () {
    return response(file_get_contents(resource_path('scripts/install2.sh')))
        ->header('Content-Type', 'text/plain');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
