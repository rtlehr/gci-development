<?php

namespace App\Http\Controllers;

use App\Models\ResumeFormat;
use App\Services\Resumes\ResumeFields;
use App\Services\UserEventLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ResumeFormatController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/ResumeFormats/Index', ['formats' => ResumeFormat::orderBy('name')->orderByDesc('version')->get()]);
    }

    public function create()
    {
        return Inertia::render('Admin/ResumeFormats/Edit', ['format' => null, 'defaults' => ResumeFields::defaults(), 'columnDefaults' => ResumeFields::REPEATING]);
    }

    public function edit(ResumeFormat $format)
    {
        return Inertia::render('Admin/ResumeFormats/Edit', ['format' => $format, 'defaults' => ResumeFields::defaults(), 'columnDefaults' => ResumeFields::REPEATING]);
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, ResumeFormat $format)
    {
        return $this->save($request, $format);
    }

    private function save(Request $request, ?ResumeFormat $format = null)
    {
        $rules = ['name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string', 'max:3000'], 'is_active' => ['required', 'boolean'], 'mappings' => ['required', 'array:'.implode(',', array_keys(ResumeFields::defaults()))]];
        foreach (ResumeFields::defaults() as $key => $_) {
            $rules['mappings.'.$key] = ['present', 'nullable', 'string', 'max:500'];
        }
        $rules['column_orders'] = ['sometimes', 'array:'.implode(',', array_keys(ResumeFields::REPEATING))];
        foreach (ResumeFields::REPEATING as $section => $_) {
            $rules['column_orders.'.$section] = ['sometimes', 'string', 'max:500'];
        }
        $data = $request->validate($rules);
        $orders = [];
        foreach (ResumeFields::REPEATING as $section => $columns) {
            $order = isset($data['column_orders'][$section]) ? array_map('trim', explode(',', $data['column_orders'][$section])) : ($format?->column_orders[$section] ?? $columns);
            if (count($order) !== count($columns) || count(array_unique($order)) !== count($columns) || array_diff($order, $columns)) {
                throw ValidationException::withMessages(['column_orders.'.$section => 'List each supported column exactly once, separated by commas: '.implode(', ', $columns)]);
            }
            $orders[$section] = $order;
        }
        $data['column_orders'] = $orders;
        $data['mappings'] = array_map(fn ($v) => trim($v ?? ''), $data['mappings']);
        $seen = [];
        foreach ($data['mappings'] as $field => $aliases) {
            foreach (explode('|', $aliases) as $alias) {
                $alias = mb_strtolower(trim(preg_replace('/[\s:"\'“”]+/u', ' ', $alias)));
                if ($alias === '') {
                    continue;
                }
                if (isset($seen[$alias]) && $seen[$alias] !== $field) {
                    throw ValidationException::withMessages(['mappings.'.$field => 'A document label cannot map to two different fields.']);
                }
                $seen[$alias] = $field;
            }
        }
        if (! $seen) {
            throw ValidationException::withMessages(['mappings' => 'Configure at least one document label.']);
        }
        // A mapping/name change creates a new version; existing imports retain their snapshot.
        if ($format && ($format->mappings !== $data['mappings'] || $format->name !== $data['name'] || ($format->column_orders ?? ResumeFields::REPEATING) !== $data['column_orders'])) {
            $data['version'] = (int) ResumeFormat::where('name', $data['name'])->max('version') + 1;
            $format = ResumeFormat::create($data);
        } elseif ($format) {
            $format->update($data);
        } else {
            $data['version'] = (int) ResumeFormat::where('name', $data['name'])->max('version') + 1;
            $format = ResumeFormat::create($data);
        }
        app(UserEventLogger::class)->recordModelEvent(eventType: 'update', module: 'resume_formats', action: 'save', subject: $format, description: 'Saved resume format configuration.', subjectLabel: $format->name.' v'.$format->version);

        return redirect()->route('resume-formats.index')->with('success', 'Resume format saved. Existing imported resumes retain their original format snapshot.');
    }
}
