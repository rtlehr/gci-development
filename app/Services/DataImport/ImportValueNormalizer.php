<?php

namespace App\Services\DataImport;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class ImportValueNormalizer
{
    private const NULL_TOKENS = ['', 'n/a', 'na', 'none', 'null', '-', '—'];

    public function blank(mixed $value): bool
    {
        if ($value === null) return true;
        $text = mb_strtolower(trim((string) $value));
        return in_array($text, self::NULL_TOKENS, true);
    }

    public function text(mixed $value): ?string
    {
        return $this->blank($value) ? null : trim((string) $value);
    }

    public function integer(mixed $value): ?int
    {
        if ($this->blank($value)) return null;
        $text = trim((string) $value);
        return preg_match('/^-?\d+$/', $text) ? (int) $text : null;
    }

    public function decimal(mixed $value): ?string
    {
        if ($this->blank($value)) return null;
        $text = str_replace([',', '$'], '', trim((string) $value));
        return is_numeric($text) ? $text : null;
    }

    public function boolean(mixed $value): ?bool
    {
        if ($this->blank($value)) return null;
        return match (mb_strtolower(trim((string) $value))) {
            '1', 'true', 'yes', 'y', 'on' => true,
            '0', 'false', 'no', 'n', 'off' => false,
            default => null,
        };
    }

    public function date(mixed $value): ?string
    {
        if ($this->blank($value)) return null;
        if ($value instanceof DateTimeInterface) return CarbonImmutable::instance($value)->format('Y-m-d');

        $text = trim((string) $value);
        if (is_numeric($text)) {
            $serial = (float) $text;
            if ($serial > 0 && $serial < 100000) {
                return CarbonImmutable::create(1899, 12, 30)->addDays((int) floor($serial))->format('Y-m-d');
            }
        }

        foreach (['Y-m-d', 'm/d/Y', 'n/j/Y', 'm/d/y', 'n/j/y'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!'.$format, $text);
                if ($date && $date->format($format) === $text) return $date->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        try {
            return CarbonImmutable::parse($text)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function dateTime(mixed $value): ?string
    {
        if ($this->blank($value)) return null;
        $date = $this->date($value);
        if ($date !== null && (is_numeric((string) $value) || ! str_contains((string) $value, ':'))) {
            return $date.' 00:00:00';
        }
        try {
            return CarbonImmutable::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    public function checkboxValues(mixed $value): array
    {
        if ($this->blank($value)) return [];
        return collect(preg_split('/[,;|]/', (string) $value) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }
}
