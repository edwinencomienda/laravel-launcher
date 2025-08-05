<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class InstallScriptController extends Controller
{
    public function index(): Response
    {
        return response(file_get_contents(resource_path('scripts/install.sh')))
            ->header('Content-Type', 'text/plain');
    }
}
