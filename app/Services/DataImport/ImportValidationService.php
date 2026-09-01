<?php

namespace App\Services\DataImport;

use App\Models\CustomField;
use App\Models\DataImport;
use App\Models\DataImportRow;
use App\Models\JobTitle;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Position;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ImportValidationService
{
    public function __construct(
        private readonly XlsxWorkbookReader $reader,
        private readonly ImportMappingRegistry $registry,
        private readonly ImportRecordMatcher $matcher,
        private readonly ImportValueNormalizer $normalizer,
        private readonly ImportValueTranslationService $translations,
    ) {}

    public function validate(DataImport $import): array
    {
        $payload = $import->mapping_snapshot ?? [];
        $columns = collect($payload['columns'] ?? []);
        $workflow = $this->workflow($payload['workflow_code'] ?? null);
        $destinations = $this->registry->flat($workflow);

        $sheet = $this->reader->readWorksheet(
            Storage::disk('local')->path($import->stored_path),
            (int) $import->worksheet_index,
        );

        $import->rows()->delete();
        $seenPersonPosition = [];
        $summary = ['total' => 0, 'ready' => 0, 'review' => 0, 'error' => 0, 'ignored' => 0];
        $errorCounts = [];

        foreach ($sheet['rows'] as $offset => $rawRow) {
            $rowNumber = $offset + 2;
            if ($this->rowBlank($rawRow)) continue;

            $result = $this->validateRow($rawRow, $columns, $destinations, $workflow, $seenPersonPosition, $rowNumber, $payload['value_translations'] ?? []);
            $summary['total']++;
            $summary[$result['status']]++;

            foreach ($result['issues'] as $issue) {
                $errorCounts[$issue['code']] = ($errorCounts[$issue['code']] ?? 0) + 1;
            }

            DataImportRow::query()->create([
                'data_import_id' => $import->id,
                'source_row_number' => $rowNumber,
                'source_identifier' => $result['source_identifier'],
                'status' => $result['status'],
                'action' => $result['action'],
                'issues' => $result['issues'],
                'person_id' => $result['person_id'],
                'position_id' => $result['position_id'],
                'candidate_id' => $result['candidate_id'],
                'result' => [
                    'mapped_values' => $result['mapped_values'],
                    'matches' => $result['matches'],
                ],
            ]);
        }

        $import->update([
            'status' => $summary['error'] > 0 || $summary['review'] > 0 ? 'validated_with_issues' : 'validated',
            'validation_summary' => $summary,
            'error_summary' => $errorCounts,
            'failed_count' => $summary['error'],
            'skipped_count' => $summary['ignored'],
        ]);

        return $summary;
    }

    private function validateRow(array $rawRow, Collection $columns, array $destinations, ?Workflow $workflow, array &$seenPersonPosition, int $rowNumber, array $valueTranslations): array
    {
        $issues = [];
        $mapped = [];

        foreach ($columns as $mapping) {
            $destination = $mapping['destination_key'] ?? 'ignore';
            if ($destination === 'ignore') continue;
            $index = (int) ($mapping['source_index'] ?? -1);
            $raw = $rawRow[$index] ?? null;
            $raw = $this->translations->apply($valueTranslations, $destination, $raw);
            $definition = $destinations[$destination] ?? null;
            if (! $definition) {
                $issues[] = $this->issue('stale_destination', 'error', "Mapped destination {$destination} is no longer available.", $destination);
                continue;
            }
            if ($this->normalizer->blank($raw)) continue;

            $normalized = $this->normalizeByType($raw, $definition['type'] ?? 'text');
            if ($normalized['valid'] === false) {
                $issues[] = $this->issue('invalid_type', 'error', "Value '{$raw}' is not a valid {$definition['type']} for {$definition['label']}.", $destination);
                continue;
            }
            $mapped[$destination] = $normalized['value'];
            $this->validateDestinationValue($destination, $normalized['value'], $definition, $issues);
        }

        $personCode = Arr::get($mapped, 'person.person_code');
        $email = Arr::get($mapped, 'person.email');
        $positionCode = Arr::get($mapped, 'position.position_code');

        $personMatch = $this->matcher->person($personCode, $email);
        if ($personMatch['ambiguous']) {
            $issues[] = $this->issue('ambiguous_person_email', 'error', "More than one Person matches email '{$email}'.", 'person.email');
        }
        /** @var Person|null $person */
        $person = $personMatch['record'];
        /** @var Position|null $position */
        $position = $this->matcher->position($positionCode);
        $candidate = $this->matcher->candidate($person, $position);

        $hasPersonFields = collect(array_keys($mapped))->contains(fn ($key) => str_starts_with($key, 'person.') || str_starts_with($key, 'custom.person.'));
        $hasPositionFields = collect(array_keys($mapped))->contains(fn ($key) => str_starts_with($key, 'position.') || str_starts_with($key, 'custom.position.'));
        $hasCandidateContext = collect(array_keys($mapped))->contains(fn ($key) => str_starts_with($key, 'candidate.') || str_starts_with($key, 'workflow.') || str_starts_with($key, 'assignment.'));

        if ($hasPersonFields && ! $person) {
            if (blank($mapped['person.first_name'] ?? null)) $issues[] = $this->issue('missing_person_first_name', 'error', 'A new Person requires First Name.', 'person.first_name');
            if (blank($mapped['person.last_name'] ?? null)) $issues[] = $this->issue('missing_person_last_name', 'error', 'A new Person requires Last Name.', 'person.last_name');
        }
        if ($hasPositionFields && ! $position) {
            if (blank($positionCode)) $issues[] = $this->issue('missing_position_code', 'error', 'A new Position requires Position Code.', 'position.position_code');
            if (blank($mapped['position.job_title'] ?? null)) $issues[] = $this->issue('missing_job_title', 'error', 'A new Position requires Job Title.', 'position.job_title');
        }
        if ($hasPersonFields && ! $person) {
            $this->validateRequiredCustomFields(CustomField::ENTITY_PERSON, $mapped, $issues);
        }
        if ($hasPositionFields && ! $position) {
            $this->validateRequiredCustomFields(CustomField::ENTITY_POSITION, $mapped, $issues);
        }
        if ($hasCandidateContext) {
            if (! $person && ! $hasPersonFields) $issues[] = $this->issue('missing_candidate_person', 'error', 'Candidate/workflow data requires a mapped or existing Person.');
            if (! $position && ! $hasPositionFields) $issues[] = $this->issue('missing_candidate_position', 'error', 'Candidate/workflow data requires a mapped or existing Position.');
            if ($workflow === null && collect(array_keys($mapped))->contains(fn ($key) => str_starts_with($key, 'workflow.'))) {
                $issues[] = $this->issue('missing_workflow', 'error', 'Workflow data is mapped but no active Candidate Workflow is selected.');
            }
        }

        $hasAssignmentData = collect(array_keys($mapped))->contains(fn ($key) => str_starts_with($key, 'assignment.'));
        if ($hasAssignmentData && blank($mapped['assignment.start_date'] ?? null)) {
            $existingAssignment = $person && $position
                ? $person->assignments()->where('position_id', $position->id)->exists()
                : false;

            if (! $existingAssignment) {
                $issues[] = $this->issue(
                    'missing_assignment_start_date',
                    'error',
                    'A new Position Assignment requires Start Date.',
                    'assignment.start_date',
                );
            }
        }

        $pairKey = null;
        if (filled($personCode) && filled($positionCode)) $pairKey = mb_strtolower(trim($personCode)).'|'.mb_strtolower(trim($positionCode));
        elseif (filled($email) && filled($positionCode)) $pairKey = 'email:'.mb_strtolower(trim($email)).'|'.mb_strtolower(trim($positionCode));
        elseif ($person && filled($positionCode)) $pairKey = 'person:'.$person->id.'|'.mb_strtolower(trim($positionCode));
        if ($pairKey !== null) {
            if (isset($seenPersonPosition[$pairKey])) {
                $issues[] = $this->issue('duplicate_person_position', 'error', "This Person/Position combination also appears on spreadsheet row {$seenPersonPosition[$pairKey]}.");
            } else {
                $seenPersonPosition[$pairKey] = $rowNumber;
            }
        }

        if ($person) {
            $issues[] = $this->issue('existing_person', 'review', 'Existing Person matched by '.str_replace('_', ' ', (string) $personMatch['matched_by']).'.');
        }
        if ($position) $issues[] = $this->issue('existing_position', 'review', 'Existing Position matched by Position Code.');
        if ($candidate) $issues[] = $this->issue('existing_candidate', 'review', 'An existing Candidate already links this Person and Position.');

        $status = $this->status($issues, $mapped);
        $sourceIdentifier = $positionCode ?: $personCode ?: $email;

        return [
            'status' => $status,
            'action' => $status === 'ignored' ? 'skip' : ($person || $position || $candidate ? 'review_existing' : 'create'),
            'issues' => $issues,
            'mapped_values' => $mapped,
            'source_identifier' => $sourceIdentifier,
            'person_id' => $person?->id,
            'position_id' => $position?->id,
            'candidate_id' => $candidate?->id,
            'matches' => [
                'person' => $person ? ['id' => $person->id, 'matched_by' => $personMatch['matched_by']] : null,
                'position' => $position ? ['id' => $position->id] : null,
                'candidate' => $candidate ? ['id' => $candidate->id] : null,
            ],
        ];
    }

    private function validateDestinationValue(string $destination, mixed $value, array $definition, array &$issues): void
    {
        if ($destination === 'position.staffing_state') {
            $allowed = ['vacant', 'selected', 'filled', 'departing', 'on-hold', 'on hold'];
            if (! in_array(mb_strtolower((string) $value), $allowed, true)) {
                $issues[] = $this->issue('invalid_staffing_state', 'error', "Unknown Staffing State '{$value}'.", $destination, $value);
            }
        }

        if ($destination === 'position.job_title') {
            if (! JobTitle::query()->where('is_active', true)->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $value)])->exists()) {
                $issues[] = $this->issue('unknown_job_title', 'error', "Unknown Job Title '{$value}'.", $destination, $value);
            }
        }

        if ($destination === 'position.project_manager') {
            $matches = User::query()
                ->leftJoin('people', 'people.user_id', '=', 'users.id')
                ->where(function ($query) use ($value) {
                    $needle = mb_strtolower(trim((string) $value));
                    $query->whereRaw('LOWER(users.name) = ?', [$needle])
                        ->orWhereRaw('LOWER(users.email) = ?', [$needle])
                        ->orWhereRaw('LOWER(people.last_name) = ?', [$needle]);
                })->distinct()->limit(2)->pluck('users.id');
            if ($matches->count() === 0) $issues[] = $this->issue('unknown_project_manager', 'error', "Unknown Project Manager '{$value}'.", $destination, $value);
            elseif ($matches->count() > 1) $issues[] = $this->issue('ambiguous_project_manager', 'error', "Multiple Project Managers match '{$value}'.", $destination, $value);
        }

        if (in_array($destination, ['position.position_organization', 'position.sponsoring_organization', 'position.funding_organization'], true)) {
            if (! Organization::query()->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $value)])->exists()) {
                $issues[] = $this->issue('unknown_organization', 'error', "Unknown Organization '{$value}'.", $destination, $value);
            }
        }

        if ($destination === 'candidate.status' && ! in_array(mb_strtolower((string) $value), ['submitted', 'selected', 'approved', 'assigned'], true)) {
            $issues[] = $this->issue('invalid_candidate_status', 'error', "Candidate Status '{$value}' needs a value mapping before import.", $destination, $value);
        }

        if ($destination === 'assignment.assignment_status' && ! in_array(mb_strtolower((string) $value), ['active', 'ended', 'planned'], true)) {
            $issues[] = $this->issue('invalid_assignment_status', 'error', "Unknown Assignment Status '{$value}'.", $destination, $value);
        }

        if (str_starts_with($destination, 'workflow.') && ($definition['meta']['property'] ?? null) === 'status') {
            $allowed = collect($definition['meta']['value_options'] ?? [])->pluck('value')->map(fn ($v) => mb_strtolower((string) $v));
            if (! $allowed->contains(mb_strtolower((string) $value))) {
                $issues[] = $this->issue('invalid_workflow_status', 'error', "Workflow status '{$value}' is not configured for {$definition['meta']['step_name']}.", $destination, $value);
            }
        }

        if (str_starts_with($destination, 'custom.')) {
            $options = collect($definition['meta']['options'] ?? []);
            $type = $definition['type'] ?? 'text';
            if ($type === CustomField::TYPE_RADIO) {
                $valid = $options->contains(fn ($option) => mb_strtolower((string) $option['value']) === mb_strtolower((string) $value) || mb_strtolower((string) $option['label']) === mb_strtolower((string) $value));
                if (! $valid) $issues[] = $this->issue('invalid_custom_field_option', 'error', "'{$value}' is not a valid option for {$definition['label']}.", $destination, $value);
            }
            if ($type === CustomField::TYPE_CHECKBOX) {
                foreach ((array) $value as $selected) {
                    $valid = $options->contains(fn ($option) => mb_strtolower((string) $option['value']) === mb_strtolower((string) $selected) || mb_strtolower((string) $option['label']) === mb_strtolower((string) $selected));
                    if (! $valid) $issues[] = $this->issue('invalid_custom_field_option', 'error', "'{$selected}' is not a valid option for {$definition['label']}.", $destination, $selected);
                }
            }
        }
    }

    private function normalizeByType(mixed $raw, string $type): array
    {
        $value = match ($type) {
            'integer' => $this->normalizer->integer($raw),
            'decimal' => $this->normalizer->decimal($raw),
            'boolean' => $this->normalizer->boolean($raw),
            'date' => $this->normalizer->date($raw),
            'datetime' => $this->normalizer->dateTime($raw),
            CustomField::TYPE_CHECKBOX => $this->normalizer->checkboxValues($raw),
            default => $this->normalizer->text($raw),
        };

        $valid = match ($type) {
            'integer', 'decimal', 'boolean', 'date', 'datetime' => $value !== null,
            default => true,
        };

        return ['valid' => $valid, 'value' => $value];
    }

    private function validateRequiredCustomFields(string $entityType, array $mapped, array &$issues): void
    {
        $required = CustomField::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->where('is_required', true)
            ->get(['key', 'name']);

        foreach ($required as $field) {
            $key = "custom.{$entityType}.{$field->key}";
            $value = $mapped[$key] ?? null;
            $empty = is_array($value) ? $value === [] : blank($value);
            if ($empty) {
                $issues[] = $this->issue('missing_required_custom_field', 'error', "A new {$entityType} requires custom field '{$field->name}'.", $key);
            }
        }
    }

    private function workflow(?string $code): ?Workflow
    {
        if (blank($code)) return null;
        return Workflow::query()->where('code', $code)->where('is_active', true)->first();
    }

    private function issue(string $code, string $severity, string $message, ?string $destination = null, mixed $sourceValue = null): array
    {
        return array_filter([
            'code' => $code,
            'severity' => $severity,
            'message' => $message,
            'destination_key' => $destination,
            'source_value' => $sourceValue,
        ], fn ($v) => $v !== null);
    }

    private function status(array $issues, array $mapped): string
    {
        if ($mapped === []) return 'ignored';
        if (collect($issues)->contains(fn ($issue) => $issue['severity'] === 'error')) return 'error';
        if (collect($issues)->contains(fn ($issue) => $issue['severity'] === 'review')) return 'review';
        return 'ready';
    }

    private function rowBlank(array $row): bool
    {
        return collect($row)->every(fn ($value) => $this->normalizer->blank($value));
    }
}
