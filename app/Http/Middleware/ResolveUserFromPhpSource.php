<?php

namespace App\Http\Middleware;

use App\Models\Person;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveUserFromPhpSource
{
    public function handle(Request $request, Closure $next): Response
    {
        /*
         * Automated tests must use Laravel's normal authentication behavior.
         *
         * This preserves:
         * - actingAs()
         * - guest assertions
         * - Fortify registration login
         * - password reset/authentication tests
         *
         * It also prevents DEV_PERSON_CODE from leaking into the test suite.
         */
        if (app()->environment('testing')) {
            return $next($request);
        }

        /*
         * Never replace a user who has already authenticated normally.
         */
        if (Auth::check()) {
            return $next($request);
        }

        if (! config('devuser.enabled')) {
            return $next($request);
        }

        $personCode = session('dev_person_code')
            ?? config('devuser.person_code');

        if (blank($personCode)) {
            return $next($request);
        }

        $person = Person::query()
            ->where('person_code', $personCode)
            ->first();

        if (! $person?->user_id) {
            return $next($request);
        }

        $user = User::query()->find($person->user_id);

        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return $next($request);
    }
}