<?php

namespace App\Services\Identity;

use App\Contracts\Identity\PersonCodeProvider;
use RuntimeException;

class DevelopmentPersonCodeProvider implements PersonCodeProvider
{
    public function resolve(): string|int|null
    {
        if (config('devuser.enabled') !== true) {
            return null;
        }

        if (session()->has('dev_person_code')) {
            return session('dev_person_code');
        }

        $personCode = config('devuser.person_code');

        if (blank($personCode)) {
            throw new RuntimeException(
                'DEV_USER_ENABLED is true, but no DEV_PERSON_CODE is configured.'
            );
        }

        return $personCode;
    }
}
