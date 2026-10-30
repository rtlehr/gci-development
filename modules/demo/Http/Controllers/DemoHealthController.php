<?php

namespace Insite\Modules\Demo\Http\Controllers;

use Illuminate\Http\JsonResponse;

/** Non-sensitive demo response used to validate module routing. */
final class DemoHealthController
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['module' => 'demo', 'active' => true]);
    }
}
