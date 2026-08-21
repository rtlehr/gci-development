<?php

namespace App\Services\Identity;

use App\Contracts\Identity\PersonCodeProvider;
use RuntimeException;

class AdfsPersonCodeProvider implements PersonCodeProvider
{
    public function resolve(): ?string
    {
        $source = trim((string) config('identity.drivers.adfs.person_code_source'));

        if ($source === '') {
            throw new RuntimeException(
                'IRAD_ADFS_PERSON_CODE_SOURCE is not configured.'
            );
        }

        $value = request()->server($source);

        if (! is_scalar($value)) {
            return null;
        }

        $personCode = trim((string) $value);

        return $personCode !== '' ? $personCode : null;
    }
}
