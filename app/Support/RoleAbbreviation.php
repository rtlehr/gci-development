<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoleAbbreviation
{
    /**
     * Central display abbreviations for application roles.
     *
     * @var array<string, string>
     */
    private const ABBREVIATIONS = [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'administrator' => 'Admin',
        'cotr' => 'COTR',
        'pmo' => 'PMO',
        'project_manager' => 'PM',
        'project manager' => 'PM',
        'developer' => 'Dev',
        'hiring_manager' => 'HM',
        'hiring manager' => 'HM',
        'recruiter' => 'Rec',
        'team_lead' => 'TL',
        'team lead' => 'TL',
        'human_resources' => 'HR',
        'human resources' => 'HR',
        'candidate' => 'Candidate',
        'user' => 'User',
    ];

    /**
     * Return the display abbreviation for one role.
     */
    public static function for(?string $name, ?string $label = null): string
    {
        $normalizedName = self::normalize($name);
        $normalizedLabel = self::normalize($label);

        return self::ABBREVIATIONS[$normalizedName]
            ?? self::ABBREVIATIONS[$normalizedLabel]
            ?? trim((string) ($label ?: $name));
    }

    /**
     * Convert a role collection into unique display abbreviations.
     *
     * @param  iterable<int, object|array<string, mixed>>  $roles
     * @return array<int, string>
     */
    public static function forRoles(iterable $roles): array
    {
        return Collection::make($roles)
            ->map(function ($role): string {
                $name = is_array($role) ? ($role['name'] ?? null) : ($role->name ?? null);
                $label = is_array($role) ? ($role['label'] ?? null) : ($role->label ?? null);

                return self::for($name, $label);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private static function normalize(?string $value): string
    {
        return Str::of((string) $value)
            ->trim()
            ->lower()
            ->replace('-', '_')
            ->replaceMatches('/\s+/', '_')
            ->toString();
    }
}
