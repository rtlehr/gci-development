<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\RegisterResponse;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use App\Services\Auth\BootstrapOwnerService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            RegisterResponseContract::class,
            RegisterResponse::class,
        );

        $this->app->singleton(
            LoginResponseContract::class,
            LoginResponse::class,
        );
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }


    /**
     * Password authentication is reserved for first-install bootstrap Owner
     * access. It is never an ADFS fallback. Existing auth scaffolding tests
     * retain Laravel's stock behavior unless bootstrap enforcement is enabled.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            if (app()->environment('testing') && config('bootstrap_login.enforce_in_testing') !== true) {
                $user = User::query()->where('email', $request->input('email'))->first();

                return $user && Hash::check((string) $request->input('password'), $user->password)
                    ? $user
                    : null;
            }

            /** @var BootstrapOwnerService $bootstrap */
            $bootstrap = app(BootstrapOwnerService::class);

            if (! $bootstrap->loginAvailable()) {
                return null;
            }

            $user = User::query()->where('email', $request->input('email'))->first();

            if (! $bootstrap->isBootstrapOwner($user)) {
                return null;
            }

            if (! Hash::check((string) $request->input('password'), (string) $user->password)) {
                return null;
            }

            $bootstrap->markSession($request);

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function (Request $request) {
            if (app()->environment('testing') && config('bootstrap_login.enforce_in_testing') !== true) {
                return Inertia::render('auth/Login', [
                    'canResetPassword' => Features::enabled(Features::resetPasswords()),
                    'canRegister' => Features::enabled(Features::registration()),
                    'status' => $request->session()->get('status'),
                ]);
            }

            $bootstrap = app(BootstrapOwnerService::class);
            abort_unless($bootstrap->loginAvailable(), 404);

            return Inertia::render('auth/BootstrapLogin', [
                'status' => $request->session()->get('status'),
            ]);
        });

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register'));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
