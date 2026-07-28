<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CurrentUserContext;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(CurrentUserContext $currentUser): Response
    {
        return Inertia::render('Public/Home', [
            'isAuthenticated' => $currentUser->user() !== null,
            'program' => [
                'name' => 'ZION INSIGHT Portal',
                'summary' => 'A unified program portal for resources, requests, collaboration, and operational support.',
                'contractYear' => 'Base Year',
                'contractNumber' => 'B2026-#########',
                'periodOfPerformance' => 'May 1, 2026 – April 30, 2027',
            ],
        ]);
    }
}
