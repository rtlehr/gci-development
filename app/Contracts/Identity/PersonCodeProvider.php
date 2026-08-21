<?php

namespace App\Contracts\Identity;

interface PersonCodeProvider
{
    /**
     * Resolve the current enterprise person_code from the configured identity source.
     */
    public function resolve(): string|int|null;
}
