<?php

namespace App\Services\DataImport;

use App\Models\DataImport;
use App\Models\JobTitle;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;

class ImportValueTranslationService
{
    public function options(DataImport $import, ImportMappingRegistry $registry): array
    {
        $workflowCode = $import->mapping_snapshot['workflow_code'] ?? null;
        $workflow = filled($workflowCode)
            ? Workflow::query()->where('code', $workflowCode)->where('is_active', true)->first()
            : null;
        $destinations = $registry->flat($workflow);

        $options = [
            'position.staffing_state' => ['Vacant', 'Selected', 'Filled', 'Departing', 'On-Hold'],
            'position.job_title' => JobTitle::query()->where('is_active', true)->orderBy('name')->pluck('name')->filter()->values()->all(),
            'position.project_manager' => User::query()->orderBy('name')->get(['name', 'email'])
                ->map(fn (User $user) => filled($user->email) ? $user->email : $user->name)->filter()->unique()->values()->all(),
            'position.position_organization' => $this->organizations(),
            'position.sponsoring_organization' => $this->organizations(),
            'position.funding_organization' => $this->organizations(),
            'candidate.status' => ['submitted', 'selected', 'approved', 'assigned'],
            'assignment.assignment_status' => ['active', 'planned', 'ended'],
        ];

        foreach ($destinations as $key => $definition) {
            $meta = $definition['meta'] ?? [];
            if (str_starts_with($key, 'workflow.') && ($meta['property'] ?? null) === 'status') {
                $options[$key] = collect($meta['value_options'] ?? [])->pluck('value')->filter()->unique()->values()->all();
            }
            if (str_starts_with($key, 'custom.') && ! empty($meta['options']) && ($definition['type'] ?? null) !== 'checkbox') {
                $options[$key] = collect($meta['options'])->pluck('value')->filter(fn ($value) => filled($value))->unique()->values()->all();
            }
        }

        return collect($options)->filter()->all();
    }

    public function save(DataImport $import, string $destination, string $source, string $target): void
    {
        $snapshot = $import->mapping_snapshot ?? [];
        $translations = $snapshot['value_translations'] ?? [];
        $translations[$destination][$this->sourceKey($source)] = $target;
        $snapshot['value_translations'] = $translations;
        $import->update(['mapping_snapshot' => $snapshot]);
    }

    public function apply(array $translations, string $destination, mixed $value): mixed
    {
        if (! is_scalar($value) || $value === '') return $value;
        return $translations[$destination][$this->sourceKey((string) $value)] ?? $value;
    }

    private function organizations(): array
    {
        return Organization::query()->orderBy('name')->pluck('name')->filter()->unique()->values()->all();
    }

    private function sourceKey(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
