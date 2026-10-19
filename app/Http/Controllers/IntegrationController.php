<?php

namespace App\Http\Controllers;

use App\Services\KapsoService;

class IntegrationController extends Controller
{
    public function index(KapsoService $kapso)
    {
        return view('integration.index', [
            'configured' => $kapso->isConfigured(),
        ]);
    }
}
