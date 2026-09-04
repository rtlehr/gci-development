<?php

namespace App\Http\Middleware;

use App\Contracts\Identity\PersonCodeProvider;
use App\Models\ImpersonationLog;
use App\Models\Person;
use App\Models\User;
use App\Services\Auth\BootstrapOwnerService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ResolveUserFromPhpSource
{
    public function __construct(
        private readonly PersonCodeProvider $personCodeProvider,
        private readonly BootstrapOwnerService $bootstrapOwner,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (
            app()->environment('testing')
            && config('identity.middleware_in_testing') !== true
        ) {
            return $next($request);
        }

        return match ((string) config('identity.driver')) {
            'development' => $this->handleDevelopment($request, $next),
            'adfs' => $this->handleAdfs($request, $next),
            default => $next($request),
        };
    }

    private function handleDevelopment(Request $request, Closure $next): Response
    {
        /*
         * Development identity is intentionally unavailable in production.
         * A production deployment must explicitly use the ADFS driver so an
         * .env mistake cannot silently impersonate DEV_PERSON_CODE.
         */
        if (app()->isProduction()) {
            if ($this->requiresAuthentication($request)) {
                Log::error('IRAD identity driver is misconfigured for production.', [
                    'driver' => 'development',
                    'path' => $request->path(),
                ]);

                return $this->identityError(
                    'IRAD identity configuration error',
                    'IRAD is not configured to use the production ADFS identity provider. Please contact the system administrator.',
                    500,
                );
            }

            return $next($request);
        }

        if (
            ! app()->environment('local')
            && ! app()->environment('testing')
        ) {
            return $next($request);
        }

        if (config('devuser.enabled') !== true || Auth::check()) {
            return $next($request);
        }

        try {
            $personCode = $this->personCodeProvider->resolve();
        } catch (Throwable) {
            return $next($request);
        }

        if (blank($personCode)) {
            return $next($request);
        }

        $this->loginPersonCode($request, $personCode);

        return $next($request);
    }

    private function handleAdfs(Request $request, Closure $next): Response
    {
        try {
            $personCode = $this->personCodeProvider->resolve();
        } catch (Throwable $exception) {
            if ($this->requiresAuthentication($request)) {
                Log::error('IRAD could not read the configured ADFS person_code source.', [
                    'source' => config('identity.drivers.adfs.person_code_source'),
                    'path' => $request->path(),
                ]);

                return $this->identityError(
                    'IRAD identity configuration error',
                    'IRAD could not read the configured ADFS person identifier. Please contact the system administrator.',
                    500,
                );
            }

            return $next($request);
        }

        /*
         * In ADFS mode the upstream claim is authoritative. Never allow a
         * Laravel session from an earlier request to satisfy a protected route
         * when ADFS is no longer supplying an identity.
         */
        if (blank($personCode)) {
            if ($this->bootstrapOwner->hasValidBootstrapSession($request)) {
                return $next($request);
            }

            if ($this->hasImpersonationSession($request)) {
                $this->terminateImpersonation($request, 'upstream_identity_missing');
            }

            $this->clearAuthenticatedSession($request);

            if ($this->requiresAuthentication($request)) {
                if ($this->bootstrapOwner->loginAvailable()) {
                    return redirect()->route('login');
                }
                Log::warning('IRAD did not receive a person_code from ADFS.', [
                    'source' => config('identity.drivers.adfs.person_code_source'),
                    'path' => $request->path(),
                ]);

                return $this->identityError(
                    'Unable to identify your network account',
                    'IRAD did not receive your person identifier from ADFS. Please contact the system administrator if the problem continues.',
                    401,
                );
            }

            return $next($request);
        }

        // A real ADFS identity is authoritative even if a bootstrap session existed.
        $this->bootstrapOwner->clearSession($request);

        $person = Person::findByPersonCode($personCode);

        if (! $person) {
            if ($this->hasImpersonationSession($request)) {
                $this->terminateImpersonation($request, 'upstream_identity_unknown');
            }

            $this->clearAuthenticatedSession($request);

            if ($this->requiresAuthentication($request)) {
                Log::warning('ADFS supplied a person_code that is not configured in IRAD.', [
                    'source' => config('identity.drivers.adfs.person_code_source'),
                    'person_code_hash' => hash('sha256', (string) $personCode),
                    'path' => $request->path(),
                ]);

                return $this->identityError(
                    'Your account is not configured in IRAD',
                    'ADFS identified your network account, but IRAD could not find a matching person record. Please contact the system administrator.',
                    403,
                );
            }

            return $next($request);
        }

        if (! $person->user_id) {
            if ($this->hasImpersonationSession($request)) {
                $this->terminateImpersonation($request, 'upstream_identity_unlinked');
            }

            $this->clearAuthenticatedSession($request);

            if ($this->requiresAuthentication($request)) {
                Log::warning('ADFS person_code resolved to a Person without a linked User.', [
                    'person_id' => $person->id,
                    'path' => $request->path(),
                ]);

                return $this->identityError(
                    'Your IRAD account is incomplete',
                    'Your person record was found, but it is not linked to an IRAD user account. Please contact the system administrator.',
                    403,
                );
            }

            return $next($request);
        }

        $user = User::query()->find($person->user_id);

        if (! $user) {
            if ($this->hasImpersonationSession($request)) {
                $this->terminateImpersonation($request, 'upstream_identity_user_missing');
            }

            $this->clearAuthenticatedSession($request);

            if ($this->requiresAuthentication($request)) {
                Log::error('ADFS person_code resolved to a Person with a missing User record.', [
                    'person_id' => $person->id,
                    'user_id' => $person->user_id,
                    'path' => $request->path(),
                ]);

                return $this->identityError(
                    'Your IRAD account is incomplete',
                    'Your person record references an IRAD user account that could not be found. Please contact the system administrator.',
                    403,
                );
            }

            return $next($request);
        }

        /*
         * ADFS remains authoritative while an Insite impersonation session is
         * active, but it must identify the original impersonator rather than
         * the user being impersonated. The browser's ADFS session never
         * changes during Insite impersonation; only Laravel's effective user
         * changes. Validate that relationship on every request before keeping
         * the impersonated Laravel identity.
         */
        if ($this->hasImpersonationSession($request)) {
            $impersonation = $this->activeImpersonation($request);

            if ($impersonation && $impersonation->impersonator_user_id === $user->id) {
                $impersonatedUser = User::query()->find($impersonation->impersonated_user_id);

                if ($impersonatedUser) {
                    if (! Auth::check() || Auth::id() !== $impersonatedUser->id) {
                        Auth::login($impersonatedUser);
                        $request->session()->regenerate();
                    }

                    return $next($request);
                }

                $this->terminateImpersonation($request, 'impersonated_user_missing');
            } elseif ($impersonation) {
                /*
                 * If the upstream ADFS identity changes, the old
                 * impersonation must not survive. End it and continue with
                 * the newly asserted ADFS identity.
                 */
                $this->terminateImpersonation($request, 'upstream_identity_changed');
            } else {
                $this->terminateImpersonation($request, 'invalid_impersonation_session');
            }
        }

        if (! Auth::check() || Auth::id() !== $user->id) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return $next($request);
    }

    private function hasImpersonationSession(Request $request): bool
    {
        return $request->hasSession()
            && $request->session()->has('impersonator_user_id');
    }

    private function activeImpersonation(Request $request): ?ImpersonationLog
    {
        $impersonatorId = $request->session()->get('impersonator_user_id');
        $logId = $request->session()->get('impersonation_log_id');
        $identifier = $request->session()->get('impersonation_session_identifier');

        if (! $impersonatorId || ! $logId || ! $identifier) {
            return null;
        }

        return ImpersonationLog::query()
            ->whereKey($logId)
            ->where('session_identifier', $identifier)
            ->where('impersonator_user_id', $impersonatorId)
            ->whereNull('ended_at')
            ->first();
    }

    private function terminateImpersonation(Request $request, string $reason): void
    {
        $logId = $request->session()->get('impersonation_log_id');
        $identifier = $request->session()->get('impersonation_session_identifier');
        $impersonatorId = $request->session()->get('impersonator_user_id');

        if ($logId && $identifier) {
            ImpersonationLog::query()
                ->whereKey($logId)
                ->where('session_identifier', $identifier)
                ->whereNull('ended_at')
                ->update([
                    'ended_by_user_id' => null,
                    'ended_at' => now(),
                    'termination_reason' => $reason,
                    'updated_at' => now(),
                ]);
        }

        $request->session()->forget([
            'impersonator_user_id',
            'impersonation_log_id',
            'impersonation_session_identifier',
        ]);
    }

    private function loginPersonCode(Request $request, string|int $personCode): void
    {
        $person = Person::findByPersonCode($personCode);

        if (! $person?->user_id) {
            return;
        }

        $user = User::query()->find($person->user_id);

        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();
        }
    }

    private function clearAuthenticatedSession(Request $request): void
    {
        if (Auth::check()) {
            Auth::logout();
        }

        if ($request->hasSession()) {
            $request->session()->regenerateToken();
        }
    }

    private function requiresAuthentication(Request $request): bool
    {
        $route = $request->route();

        if (! $route) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if ($middleware === 'auth' || str_starts_with($middleware, 'auth:')) {
                return true;
            }
        }

        return false;
    }

    private function identityError(
        string $title,
        string $message,
        int $status,
    ): Response {
        return response()->view('errors.identity', [
            'title' => $title,
            'message' => $message,
            'status' => $status,
        ], $status);
    }
}
