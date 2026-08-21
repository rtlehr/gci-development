<?php

namespace App\Providers;

use App\Contracts\Encryption\DataEncryptionProvider;
use App\Contracts\Identity\PersonCodeProvider;
use App\Services\CurrentUserContext;
use App\Services\Encryption\EncryptionManager;
use InvalidArgumentException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EncryptionManager::class);

        $this->app->bind(DataEncryptionProvider::class, function ($app): DataEncryptionProvider {
            return $app->make(EncryptionManager::class)->provider();
        });

        $this->app->bind(PersonCodeProvider::class, function ($app): PersonCodeProvider {
            $driver = (string) config('identity.driver');
            $provider = config("identity.drivers.{$driver}.provider");

            if (! is_string($provider) || ! class_exists($provider)) {
                throw new InvalidArgumentException(
                    "Unsupported IRAD identity driver [{$driver}]."
                );
            }

            $instance = $app->make($provider);

            if (! $instance instanceof PersonCodeProvider) {
                throw new InvalidArgumentException(
                    "Identity provider [{$provider}] must implement ".PersonCodeProvider::class.'.'
                );
            }

            return $instance;
        });

        /*
         * One context instance per request/job lifecycle. Its memoized User,
         * Person, permissions, and payload must never leak across requests.
         */
        $this->app->scoped(CurrentUserContext::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
