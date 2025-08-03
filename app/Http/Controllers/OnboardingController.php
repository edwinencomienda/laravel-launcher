<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index()
    {
        // get ip address
        $ip = request()->ip();

        return Inertia::render('onboarding', [
            'ip' => $ip,
        ]);
    }
}
