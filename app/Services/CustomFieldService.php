<?php

namespace App\Services;

use App\Models\CustomField;
use App\Services\Encryption\EncryptionManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class CustomFieldService
{
    public function definitions(string $entityType, bool $activeOnly = true): Collection
    {
        return CustomField::query()
            ->with(['activeOptions', 'options'])
            ->where('entity_type', $entityType)
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function rules(string $entityType): array
    {
        $rules = ['custom_fields' => ['nullable', 'array']];

        foreach ($this->definitions($entityType) as $field) {
            $key = "custom_fields.{$field->id}";
            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            if (in_array($field->field_type, [CustomField::TYPE_TEXT, CustomField::TYPE_TEXTAREA], true)) {
                $fieldRules[] = 'string';
                if ($field->field_type === CustomField::TYPE_TEXT) {
                    $fieldRules[] = 'max:1000';
                }
            } elseif ($field->field_type === CustomField::TYPE_DATE) {
                $fieldRules[] = 'date';
            } elseif ($field->field_type === CustomField::TYPE_RADIO) {
                $fieldRules[] = 'string';
                $fieldRules[] = Rule::in($field->options->pluck('value')->all());
            } elseif ($field->field_type === CustomField::TYPE_CHECKBOX) {
                $fieldRules[] = 'array';
                if ($field->is_required) {
                    $fieldRules[] = 'min:1';
                }
                $rules[$key.'.*'] = [Rule::in($field->options->pluck('value')->all())];
            }

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    public function valuesForForm(Model $model, string $entityType): array
    {
        $stored = $model->customFieldValues()
            ->whereHas('customField', fn ($query) => $query->where('entity_type', $entityType))
            ->get()
            ->keyBy('custom_field_id');

        $result = [];
        foreach ($this->definitions($entityType) as $field) {
            $value = $stored->get($field->id);
            $result[(string) $field->id] = match ($field->field_type) {
                CustomField::TYPE_DATE => $value?->value_date?->format('Y-m-d'),
                CustomField::TYPE_CHECKBOX => $value?->value_json ?? [],
                default => $this->textValue($field, $value?->value_text),
            };
        }

        return $result;
    }

    public function displayValues(Model $model, string $entityType): array
    {
        $definitions = $this->definitions($entityType);
        $stored = $model->customFieldValues()->get()->keyBy('custom_field_id');

        return $definitions->map(function (CustomField $field) use ($stored) {
            $value = $stored->get($field->id);
            $display = null;

            if ($value) {
                if ($field->field_type === CustomField::TYPE_DATE) {
                    $display = $value->value_date?->format('Y-m-d');
                } elseif ($field->field_type === CustomField::TYPE_CHECKBOX) {
                    $selected = collect($value->value_json ?? []);
                    $display = $field->options
                        ->whereIn('value', $selected)
                        ->pluck('label')
                        ->values()
                        ->all();
                } elseif ($field->field_type === CustomField::TYPE_RADIO) {
                    $display = $field->options->firstWhere('value', $value->value_text)?->label ?? $value->value_text;
                } else {
                    $display = $this->textValue($field, $value->value_text);
                }
            }

            return [
                'id' => $field->id,
                'name' => $field->name,
                'field_type' => $field->field_type,
                'description' => $field->description,
                'value' => $display,
            ];
        })->all();
    }

    public function syncValues(Model $model, string $entityType, array $input): void
    {
        foreach ($this->definitions($entityType) as $field) {
            $raw = $input[$field->id] ?? $input[(string) $field->id] ?? null;
            $isEmpty = $field->field_type === CustomField::TYPE_CHECKBOX
                ? empty($raw)
                : ($raw === null || $raw === '');

            if ($isEmpty) {
                $model->customFieldValues()->where('custom_field_id', $field->id)->delete();
                continue;
            }

            $attributes = [
                'value_text' => null,
                'value_date' => null,
                'value_json' => null,
            ];

            if ($field->field_type === CustomField::TYPE_DATE) {
                $attributes['value_date'] = $raw;
            } elseif ($field->field_type === CustomField::TYPE_CHECKBOX) {
                $attributes['value_json'] = array_values((array) $raw);
            } else {
                $attributes['value_text'] = $field->is_sensitive
                    ? app(EncryptionManager::class)->encrypt((string) $raw)
                    : (string) $raw;
            }

            $model->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $field->id],
                $attributes,
            );
        }
    }
    private function textValue(CustomField $field, ?string $value): string
    {
        if ($value === null) {
            return '';
        }

        if (! $field->is_sensitive) {
            return $value;
        }

        return (string) app(EncryptionManager::class)->decrypt($value);
    }

}
