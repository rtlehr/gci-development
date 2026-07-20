<?php

namespace App\Http\Middleware;

use App\Services\CurrentUserContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        private readonly CurrentUserContext $currentUser,
    ) {
    }

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless(
            $this->currentUser->hasPermission($permission),
            403,
            'Unauthorized',
        );

        return $next($request);
    }
}
