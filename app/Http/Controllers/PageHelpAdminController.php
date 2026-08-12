<?php

namespace App\Http\Controllers;

use App\Models\PageHelp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PageHelpAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $helpPages = PageHelp::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('help_key', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->orderBy('help_key')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/PageHelp/Index', [
            'helpPages' => $helpPages,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/PageHelp/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'help_key' => ['required', 'string', 'max:255', 'unique:page_help,help_key'],
            'title' => ['required', 'string', 'max:255'],
            'content_html' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        PageHelp::create($validated);

        return redirect()
            ->route('page-help-admin.index')
            ->with('success', 'Help page created successfully.');
    }

    public function edit(PageHelp $pageHelp): Response
    {
        return Inertia::render('Admin/PageHelp/Edit', [
            'helpPage' => $pageHelp,
        ]);
    }

    public function update(Request $request, PageHelp $pageHelp): RedirectResponse
    {
        $validated = $request->validate([
            'help_key' => ['required', 'string', 'max:255', 'unique:page_help,help_key,' . $pageHelp->id],
            'title' => ['required', 'string', 'max:255'],
            'content_html' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $pageHelp->update($validated);

        return redirect()
            ->route('page-help-admin.index')
            ->with('success', 'Help page updated successfully.');
    }

    public function destroy(PageHelp $pageHelp): RedirectResponse
    {
        $pageHelp->delete();

        return redirect()
            ->route('page-help-admin.index')
            ->with('success', 'Help page deleted successfully.');
    }

    public function export(): StreamedResponse
    {
        $payload = [
            'format' => 'irad-page-help',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'help_pages' => PageHelp::query()
                ->orderBy('help_key')
                ->get(['help_key', 'title', 'content_html', 'is_active'])
                ->map(fn (PageHelp $pageHelp): array => [
                    'help_key' => $pageHelp->help_key,
                    'title' => $pageHelp->title,
                    'content_html' => $pageHelp->content_html,
                    'is_active' => $pageHelp->is_active,
                ])
                ->values()
                ->all(),
        ];

        $filename = 'irad-page-help-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(
            function () use ($payload): void {
                echo json_encode(
                    $payload,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );
            },
            $filename,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'help_file' => ['required', 'file', 'max:5120'],
        ]);

        $contents = file_get_contents($validated['help_file']->getRealPath());

        if ($contents === false) {
            throw ValidationException::withMessages([
                'help_file' => 'The help file could not be read.',
            ]);
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                'help_file' => 'The selected file is not valid JSON.',
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'help_file' => 'The selected file does not contain a valid help page export.',
            ]);
        }

        $helpPages = array_is_list($decoded)
            ? $decoded
            : ($decoded['help_pages'] ?? null);

        if (! is_array($helpPages)) {
            throw ValidationException::withMessages([
                'help_file' => 'The selected file does not contain a help_pages collection.',
            ]);
        }

        $validator = Validator::make(
            ['help_pages' => $helpPages],
            [
                'help_pages' => ['array'],
                'help_pages.*.help_key' => ['required', 'string', 'max:255', 'distinct'],
                'help_pages.*.title' => ['required', 'string', 'max:255'],
                'help_pages.*.content_html' => ['nullable', 'string'],
                'help_pages.*.is_active' => ['required', 'boolean'],
            ],
            [],
            [
                'help_pages.*.help_key' => 'help key',
                'help_pages.*.title' => 'title',
                'help_pages.*.content_html' => 'content',
                'help_pages.*.is_active' => 'status',
            ]
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'help_file' => $validator->errors()->first(),
            ]);
        }

        $records = $validator->validated()['help_pages'];

        DB::transaction(function () use ($records): void {
            foreach ($records as $record) {
                PageHelp::updateOrCreate(
                    ['help_key' => $record['help_key']],
                    [
                        'title' => $record['title'],
                        'content_html' => $record['content_html'] ?? null,
                        'is_active' => $record['is_active'],
                    ]
                );
            }
        });

        $count = count($records);

        return redirect()
            ->route('page-help-admin.index')
            ->with(
                'success',
                $count === 1
                    ? '1 help page imported successfully.'
                    : "{$count} help pages imported successfully."
            );
    }
}
