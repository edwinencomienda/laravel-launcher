<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index()
    {
        // get ip address
        $ip = exec('hostname -I | awk \'{print $1}\'');
        $sshPublicKey = file_exists('/home/raptor/.ssh/id_rsa.pub')
            ? file_get_contents('/home/raptor/.ssh/id_rsa.pub')
            : null;

        return Inertia::render('onboarding', [
            'ip' => $ip,
            'sshPublicKey' => $sshPublicKey,
        ]);
    }
}
