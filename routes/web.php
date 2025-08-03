<?php

use App\Actions\SetupSiteNginxConfigAction;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::redirect('/', '/dashboard');

Route::get('/', function () {
    return 'Hello World';
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

Route::get('/user', function () {
    echo 'PHP-FPM User: '.get_current_user()."\n";
    echo 'Process User: '.posix_getpwuid(posix_geteuid())['name']."\n";
    echo 'Process Group: '.posix_getgrgid(posix_getegid())['name']."\n";
});

Route::get('/test', function (SetupSiteNginxConfigAction $setupSiteNginxConfigAction) {
    // return "elon";
    // defer(function () {
    //     sleep(5);
    //     logger('dsadsadsadsa');
    // });

    // return 'awe3some';

    // return response($setupSiteNginxConfigAction->handle(
    //     siteRootDirectory: '/home/raptor/laravel-demo-deploy/public',
    //     domain: 'demo.raptor.com',
    // ))
    //     ->header('Content-Type', 'text/plain');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
