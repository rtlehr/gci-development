<?php

use Illuminate\Support\Facades\Route;
use Insite\Modules\Demo\Http\Controllers\DemoHealthController;

// Controller action, not a closure: route:cache must be able to compile this route.
Route::middleware(['web', 'insite-module:demo'])
    ->get('/_insite/modules/demo/health', DemoHealthController::class)
    ->name('insite.modules.demo.health');
