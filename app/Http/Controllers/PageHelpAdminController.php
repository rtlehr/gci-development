<?php

namespace App\Http\Controllers;

use App\Models\PageHelp;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

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
}