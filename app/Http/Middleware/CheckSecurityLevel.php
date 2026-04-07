<?php

namespace App\Http\Middleware;

use App\Support\CurrentUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSecurityLevel
{
    public function handle(Request $request, Closure $next, int $level): Response
    {
        $currentLevel = CurrentUser::securityLevel($request);

        abort_if($currentLevel < $level, 403, 'Unauthorized');

        return $next($request);
    }
}