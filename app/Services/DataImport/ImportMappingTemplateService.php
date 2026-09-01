<?php

namespace App\Services\DataImport;

use App\Models\DataImportMappingTemplate;
use App\Models\Workflow;

class ImportMappingTemplateService
{
    public function __construct(private readonly HeaderNormalizer $normalizer) {}

    public function apply(
        DataImportMappingTemplate $template,
        array $currentHeaders,
        array $destinationLookup,
        ?Workflow $selectedWorkflow,
    ): array {
        return $this->applyPayload($template->mapping ?? [], $currentHeaders, $destinationLookup, $selectedWorkflow);
    }

    public function applyPayload(
        array $payload,
        array $currentHeaders,
        array $destinationLookup,
        ?Workflow $selectedWorkflow,
    ): array {
        $columns = $payload['columns'] ?? [];
        $issues = [];
        $mappings = [];

        $templateWorkflow = $payload['workflow_code'] ?? null;
        if ($templateWorkflow && (! $selectedWorkflow || $selectedWorkflow->code !== $templateWorkflow)) {
            $issues[] = [
                'type' => 'workflow_mismatch',
                'message' => "This template was saved for workflow '{$templateWorkflow}'. Select that workflow or review all workflow mappings before saving.",
            ];
        }

        $currentByHeader = [];
        foreach ($currentHeaders as $index => $header) {
            $key = $this->headerKey($header);
            if ($key !== '') {
                $currentByHeader[$key][] = (int) $index;
            }
        }

        foreach ($columns as $column) {
            $sourceHeader = (string) ($column['source_header'] ?? '');
            $destination = (string) ($column['destination_key'] ?? '');
            $headerKey = $this->headerKey($sourceHeader);

            if ($headerKey === '' || empty($currentByHeader[$headerKey])) {
                $issues[] = [
                    'type' => 'missing_source_header',
                    'source_header' => $sourceHeader,
                    'message' => "The template column '{$sourceHeader}' is not present in this worksheet.",
                ];
                continue;
            }

            if (count($currentByHeader[$headerKey]) > 1) {
                $issues[] = [
                    'type' => 'duplicate_source_header',
                    'source_header' => $sourceHeader,
                    'message' => "The worksheet contains more than one '{$sourceHeader}' column, so the saved mapping cannot be applied automatically.",
                ];
                continue;
            }

            if ($destination !== 'ignore' && ! array_key_exists($destination, $destinationLookup)) {
                $issues[] = [
                    'type' => 'stale_destination',
                    'source_header' => $sourceHeader,
                    'destination_key' => $destination,
                    'message' => "The saved destination for '{$sourceHeader}' is no longer available. Remap this column before continuing.",
                ];
                continue;
            }

            $mappings[$currentByHeader[$headerKey][0]] = $destination;
        }

        return ['mappings' => $mappings, 'issues' => $issues];
    }

    public function workflowCode(DataImportMappingTemplate $template): ?string
    {
        $code = $template->mapping['workflow_code'] ?? null;
        return is_string($code) && $code !== '' ? $code : null;
    }

    private function headerKey(?string $header): string
    {
        return mb_strtolower($this->normalizer->normalize($header));
    }
}
