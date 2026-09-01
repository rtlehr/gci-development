<?php

namespace App\Services\DataImport;

class HeaderNormalizer
{
    public function normalize(?string $value): string
    {
        $value = trim((string) $value);
        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
}
