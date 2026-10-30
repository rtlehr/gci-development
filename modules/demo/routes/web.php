<?php

use Illuminate\Support\Facades\Route;

// Public non-sensitive probe for demonstration only. No operational data is exposed.
Route::middleware(['web', 'insite-module:demo'])
    ->get('/_insite/modules/demo/health', fn () => response()->json(['module' => 'demo', 'active' => true]))
    ->name('insite.modules.demo.health');
