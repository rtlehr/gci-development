<?php

namespace App\Http\Middleware;

use App\Support\CurrentUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! CurrentUser::hasPermission($request, $permission)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}