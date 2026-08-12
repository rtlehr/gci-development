<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomFieldController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $entityType = $request->input('entity_type');

        $fields = CustomField::query()
            ->withCount('values')
            ->with('options')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when(in_array($entityType, ['person', 'position'], true), fn ($query) => $query->where('entity_type', $entityType))
            ->orderBy('entity_type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/CustomFields/Index', [
            'fields' => $fields,
            'filters' => ['search' => $search, 'entity_type' => $entityType ?? ''],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/CustomFields/Create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateField($request);

        DB::transaction(function () use ($validated, $request) {
            $field = CustomField::create([
                ...$validated,
                'key' => $this->uniqueKey($validated['entity_type'], $validated['name']),
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $this->syncOptions($field, $validated['options'] ?? []);
        });

        return redirect()->route('admin.custom-fields.index')
            ->with('success', 'Custom field created successfully.');
    }

    public function edit(CustomField $customField)
    {
        $customField->load('options')->loadCount('values');

        return Inertia::render('Admin/CustomFields/Edit', [
            'customField' => $customField,
        ]);
    }

    public function update(Request $request, CustomField $customField)
    {
        $validated = $this->validateField($request, $customField);

        if ($customField->hasValues()) {
            $errors = [];

            if ($validated['field_type'] !== $customField->field_type) {
                $errors['field_type'] = 'Field type cannot be changed after values have been saved.';
            }

            if ($validated['entity_type'] !== $customField->entity_type) {
                $errors['entity_type'] = 'The record type cannot be changed after values have been saved.';
            }

            if ($errors !== []) {
                return back()->withErrors($errors);
            }
        }

        DB::transaction(function () use ($validated, $request, $customField) {
            $customField->update([
                ...$validated,
                'updated_by' => $request->user()?->id,
            ]);

            $this->syncOptions($customField, $validated['options'] ?? []);
        });

        return redirect()->route('admin.custom-fields.index')
            ->with('success', 'Custom field updated successfully.');
    }

    public function destroy(CustomField $customField)
    {
        if ($customField->hasValues()) {
            $customField->update(['is_active' => false]);

            return redirect()->route('admin.custom-fields.index')
                ->with('success', 'The field has existing data, so it was deactivated instead of deleted.');
        }

        $customField->delete();

        return redirect()->route('admin.custom-fields.index')
            ->with('success', 'Custom field deleted successfully.');
    }

    public function export(): StreamedResponse
    {
        $payload = [
            'format' => 'irad-custom-fields',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'custom_fields' => CustomField::query()
                ->with('options')
                ->orderBy('entity_type')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (CustomField $field): array => [
                    'entity_type' => $field->entity_type,
                    'key' => $field->key,
                    'name' => $field->name,
                    'field_type' => $field->field_type,
                    'description' => $field->description,
                    'placeholder' => $field->placeholder,
                    'is_required' => $field->is_required,
                    'is_active' => $field->is_active,
                    'is_list_column' => $field->is_list_column,
                    'is_searchable' => $field->is_searchable,
                    'is_filterable' => $field->is_filterable,
                    'sort_order' => $field->sort_order,
                    'options' => $field->options->map(fn ($option): array => [
                        'value' => $option->value,
                        'label' => $option->label,
                        'sort_order' => $option->sort_order,
                        'is_active' => $option->is_active,
                    ])->values()->all(),
                ])->values()->all(),
        ];

        return response()->streamDownload(
            fn () => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'irad-custom-fields-'.now()->format('Y-m-d-His').'.json',
            ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-store, no-cache']
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'custom_fields_file' => ['required', 'file', 'max:5120'],
        ]);

        $contents = file_get_contents($validated['custom_fields_file']->getRealPath());
        if ($contents === false) {
            throw ValidationException::withMessages(['custom_fields_file' => 'The custom fields file could not be read.']);
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['custom_fields_file' => 'The selected file is not valid JSON.']);
        }

        $items = is_array($decoded) ? ($decoded['custom_fields'] ?? (array_is_list($decoded) ? $decoded : null)) : null;
        if (! is_array($items)) {
            throw ValidationException::withMessages(['custom_fields_file' => 'The selected file does not contain a custom_fields collection.']);
        }

        $validator = Validator::make(['custom_fields' => $items], [
            'custom_fields' => ['array'],
            'custom_fields.*.entity_type' => ['required', Rule::in(['person', 'position'])],
            'custom_fields.*.key' => ['required', 'string', 'max:255'],
            'custom_fields.*.name' => ['required', 'string', 'max:255'],
            'custom_fields.*.field_type' => ['required', Rule::in(['text', 'textarea', 'radio', 'checkbox', 'date'])],
            'custom_fields.*.description' => ['nullable', 'string'],
            'custom_fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'custom_fields.*.is_required' => ['required', 'boolean'],
            'custom_fields.*.is_active' => ['required', 'boolean'],
            'custom_fields.*.is_list_column' => ['nullable', 'boolean'],
            'custom_fields.*.is_searchable' => ['nullable', 'boolean'],
            'custom_fields.*.is_filterable' => ['nullable', 'boolean'],
            'custom_fields.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'custom_fields.*.options' => ['nullable', 'array'],
            'custom_fields.*.options.*.value' => ['required', 'string', 'max:255'],
            'custom_fields.*.options.*.label' => ['required', 'string', 'max:255'],
            'custom_fields.*.options.*.sort_order' => ['required', 'integer', 'min:0'],
            'custom_fields.*.options.*.is_active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(['custom_fields_file' => $validator->errors()->first()]);
        }

        $userId = $request->user()?->id;
        $imported = 0;

        DB::transaction(function () use ($items, $userId, &$imported): void {
            foreach ($items as $item) {
                $field = CustomField::where('entity_type', $item['entity_type'])->where('key', $item['key'])->first();

                if ($field?->hasValues() && ($field->field_type !== $item['field_type'] || $field->entity_type !== $item['entity_type'])) {
                    throw ValidationException::withMessages([
                        'custom_fields_file' => "{$item['name']} already has saved values and its field type/record type cannot be changed by import.",
                    ]);
                }

                $attributes = [
                    'name' => $item['name'],
                    'field_type' => $item['field_type'],
                    'description' => $item['description'] ?? null,
                    'placeholder' => $item['placeholder'] ?? null,
                    'is_required' => (bool) $item['is_required'],
                    'is_active' => (bool) $item['is_active'],
                    'is_list_column' => (bool) ($item['is_list_column'] ?? false),
                    'is_searchable' => (bool) ($item['is_searchable'] ?? false),
                    'is_filterable' => (bool) ($item['is_filterable'] ?? false),
                    'sort_order' => (int) $item['sort_order'],
                    'updated_by' => $userId,
                ];

                if ($field) {
                    $field->update($attributes);
                } else {
                    $field = CustomField::create([
                        ...$attributes,
                        'entity_type' => $item['entity_type'],
                        'key' => $item['key'],
                        'created_by' => $userId,
                    ]);
                }

                $kept = [];
                foreach ($item['options'] ?? [] as $option) {
                    $record = $field->options()->updateOrCreate(
                        ['value' => $option['value']],
                        [
                            'label' => $option['label'],
                            'sort_order' => (int) $option['sort_order'],
                            'is_active' => (bool) $option['is_active'],
                        ]
                    );
                    $kept[] = $record->id;
                }
                if ($kept !== []) {
                    $field->options()->whereNotIn('id', $kept)->update(['is_active' => false]);
                }

                $imported++;
            }
        });

        return redirect()->route('admin.custom-fields.index')
            ->with('success', "Imported {$imported} custom field definition(s) successfully.");
    }

    private function validateField(Request $request, ?CustomField $field = null): array
    {
        $rules = [
            'entity_type' => ['required', Rule::in(['person', 'position'])],
            'name' => ['required', 'string', 'max:255'],
            'field_type' => ['required', Rule::in(['text', 'textarea', 'radio', 'checkbox', 'date'])],
            'description' => ['nullable', 'string'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'is_list_column' => ['boolean'],
            'is_searchable' => ['boolean'],
            'is_filterable' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'options' => ['nullable', 'array'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.label' => ['nullable', 'string', 'max:255'],
            'options.*.is_active' => ['nullable', 'boolean'],
        ];

        $validated = $request->validate($rules);
        foreach (['is_required', 'is_active', 'is_list_column', 'is_searchable', 'is_filterable'] as $flag) {
            $validated[$flag] = (bool) ($validated[$flag] ?? false);
        }
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if (! $validated['is_list_column']) {
            $validated['is_searchable'] = false;
        }

        if (in_array($validated['field_type'], ['radio', 'checkbox'], true)) {
            $options = collect($validated['options'] ?? [])
                ->filter(fn ($option) => trim((string) ($option['label'] ?? '')) !== '')
                ->values();

            if ($options->isEmpty()) {
                throw ValidationException::withMessages(['options' => 'Radio and checkbox fields require at least one option.']);
            }
        } else {
            $validated['options'] = [];
        }

        return $validated;
    }

    private function uniqueKey(string $entityType, string $name): string
    {
        $base = Str::snake(Str::slug($name, '_')) ?: 'custom_field';
        $key = $base;
        $suffix = 2;

        while (CustomField::where('entity_type', $entityType)->where('key', $key)->exists()) {
            $key = "{$base}_{$suffix}";
            $suffix++;
        }

        return $key;
    }

    private function syncOptions(CustomField $field, array $options): void
    {
        if (! in_array($field->field_type, ['radio', 'checkbox'], true)) {
            $field->options()->update(['is_active' => false]);
            return;
        }

        $keptIds = [];
        foreach (array_values($options) as $index => $option) {
            $label = trim((string) ($option['label'] ?? ''));
            if ($label === '') continue;

            $existing = ! empty($option['id']) ? $field->options()->find($option['id']) : null;
            $value = $existing?->value ?: $this->uniqueOptionValue($field, $label);
            $record = $field->options()->updateOrCreate(
                ['id' => $existing?->id],
                ['value' => $value, 'label' => $label, 'sort_order' => $index, 'is_active' => (bool) ($option['is_active'] ?? true)]
            );
            $keptIds[] = $record->id;
        }

        $field->options()->whereNotIn('id', $keptIds)->update(['is_active' => false]);
    }

    private function uniqueOptionValue(CustomField $field, string $label): string
    {
        $base = Str::slug($label, '_') ?: 'option';
        $value = $base;
        $suffix = 2;

        while ($field->options()->where('value', $value)->exists()) {
            $value = "{$base}_{$suffix}";
            $suffix++;
        }

        return $value;
    }
}
