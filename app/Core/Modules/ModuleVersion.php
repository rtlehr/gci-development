<?php

namespace App\Core\Modules;

/** A deliberately narrow stable SemVer constraint dialect for milestone 4B.1. */
final class ModuleVersion
{
    public static function isValidConstraint(string $constraint): bool
    {
        return (bool) preg_match('/^(?:\^|~|>=|<=|>|<|=)?\d+\.\d+\.\d+$/D', $constraint);
    }

    public static function matches(string $version, string $constraint): bool
    {
        if (! preg_match('/^\d+\.\d+\.\d+$/D', $version) || ! self::isValidConstraint($constraint)) {
            return false;
        }

        preg_match('/^(\^|~|>=|<=|>|<|=)?(\d+\.\d+\.\d+)$/D', $constraint, $parts);
        $operator = $parts[1] ?: '=';
        $minimum = $parts[2];

        if ($operator === '^' || $operator === '~') {
            [$major, $minor, $patch] = array_map('intval', explode('.', $minimum));
            if ($operator === '~') {
                $maximum = "$major.".($minor + 1).'.0';
            } elseif ($major > 0) {
                $maximum = ($major + 1).'.0.0';
            } elseif ($minor > 0) {
                $maximum = "0.".($minor + 1).'.0';
            } else {
                $maximum = "0.0.".($patch + 1);
            }

            return version_compare($version, $minimum, '>=') && version_compare($version, $maximum, '<');
        }

        return version_compare($version, $minimum, $operator);
    }
}
