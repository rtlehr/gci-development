<?php

namespace App\Rules;

use App\Models\Person;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniquePersonCode implements ValidationRule
{
    public function __construct(private readonly ?int $ignorePersonId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Person::personCodeExists((string) $value, $this->ignorePersonId)) {
            $fail('The person code has already been taken.');
        }
    }
}
