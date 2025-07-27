<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::get('/install', function () {
    return response(file_get_contents(resource_path('scripts/install.sh')))
        ->header('Content-Type', 'text/plain');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
