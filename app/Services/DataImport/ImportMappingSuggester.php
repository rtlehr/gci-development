<?php

namespace App\Services\DataImport;

class ImportMappingSuggester
{
    public function __construct(private readonly HeaderNormalizer $normalizer) {}

    /** @return array<int, string> */
    public function suggest(array $headers, array $destinationGroups): array
    {
        $aliasMap = [];

        foreach ($destinationGroups as $group) {
            foreach ($group['items'] ?? [] as $item) {
                if (($item['key'] ?? '') === 'ignore') {
                    continue;
                }

                foreach ($item['aliases'] ?? [] as $alias) {
                    $normalized = $this->key($alias);
                    if ($normalized !== '') {
                        $aliasMap[$normalized][] = $item['key'];
                    }
                }
            }
        }

        $suggestions = [];
        foreach ($headers as $index => $header) {
            $headerKey = $this->key($header);
            $matches = array_values(array_unique($aliasMap[$headerKey] ?? []));

            // Only suggest exact, unambiguous matches. Stage 2 suggestions are
            // conveniences for an administrator to review, never import rules.
            if (count($matches) === 1) {
                $suggestions[(int) $index] = $matches[0];
            }
        }

        return $suggestions;
    }

    private function key(?string $value): string
    {
        $normalized = mb_strtolower($this->normalizer->normalize($value));
        return trim((string) preg_replace('/[^a-z0-9]+/u', ' ', $normalized));
    }
}
