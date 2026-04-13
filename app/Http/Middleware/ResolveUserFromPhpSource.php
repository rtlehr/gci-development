<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Person;

class ResolveUserFromPhpSource
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            return $next($request);
        }

        $personCode = $this->getPersonCodeFromPhpSource();

        if (!$personCode) {
            return $next($request);
        }

        $person = Person::where('person_code', $personCode)->first();

        if (!$person || !$person->user_id) {
            return $next($request);
        }

        $user = User::find($person->user_id);

        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return $next($request);
    }

    protected function getPersonCodeFromPhpSource(): ?int
    {
        $data = include base_path('config/devuser.php');

        return $data['person_code'] ?? null;
    }
}