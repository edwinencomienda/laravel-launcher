<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

Route::get('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'index'])->name('onboarding');
Route::post('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'store'])->name('onboarding.store');
Route::get('/onboarding/data', [\App\Http\Controllers\OnboardingController::class, 'getOnboardingData'])->name('onboarding.data');
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

// Route::get('/test', function () {
//     $bash = <<<'BASH'
//     cd /home/raptor/edwin.portal.raptordeploy.com
//     npm install
//     npm run build
//     BASH;
//     $output = Process::timeout(600)->run($bash)->throw();
//     dd($output);
// });

Route::get('/test', function () {
    executeWithShellExec('cd /home/raptor/edwin.portal.raptordeploy.com && npm install && npm run build');
    // echo '<h3>Current Environment:</h3>';
    // echo '<pre>';
    // echo 'PATH: '.shell_exec('echo $PATH')."\n";
    // echo 'USER: '.shell_exec('whoami')."\n";
    // echo 'PWD: '.shell_exec('pwd')."\n";
    // echo 'Node version: '.shell_exec('node --version 2>&1')."\n";
    // echo 'NPM version: '.shell_exec('npm --version 2>&1')."\n";
    // echo 'Which node: '.shell_exec('which node 2>&1')."\n";
    // echo 'Which npm: '.shell_exec('which npm 2>&1')."\n";
    // echo '</pre>';
});

// Route::get('/github/repos', fn () => \Illuminate\Support\Facades\Http::withToken(trim(Storage::get('github_access_token.txt')))
//     ->get('https://api.github.com/installation/repositories')
//     ->json());

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
