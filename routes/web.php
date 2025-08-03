<?php

use App\Actions\CreateNginxSiteAction;
use App\Actions\RemoveNginxSiteAction;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::redirect('/', '/dashboard');

Route::get('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'index'])->name('onboarding');

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

Route::get('/user', function () {
    echo 'PHP-FPM User: '.get_current_user()."\n";
    echo 'Process User: '.posix_getpwuid(posix_geteuid())['name']."\n";
    echo 'Process Group: '.posix_getgrgid(posix_getegid())['name']."\n";
});

Route::get('/test', function (
    CreateNginxSiteAction $createNginxSiteAction,
    RemoveNginxSiteAction $removeNginxSiteAction,
) {
    // $nginx = view('templates.nginx', [
    //     'port' => 80,
    //     'server_name' => '_',
    //     'root' => '/home/raptor/elon/public',
    //     'client_max_body_size' => '25M',
    // ]);

    $output = $createNginxSiteAction->handle(
        rootPath: '/home/raptor/elon/public',
        domain: 'elon.heyedwin.dev',
        port: 8082,
    );

    // return $removeNginxSiteAction->handle('elon.heyedwin.dev');

    return response($output)->header('Content-Type', 'text/plain');

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
