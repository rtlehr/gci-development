<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContentPageController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $pages = ContentPage::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->when($search, fn ($query) => $query->where(fn ($inner) => $inner
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('navigation_label', 'like', "%{$search}%")
                ->orWhere('page_type', 'like', "%{$search}%")))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/ContentPages/Index', [
            'pages' => $pages,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/ContentPages/Create', [
            'pageTypes' => $this->pageTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        $page = DB::transaction(function () use ($data): ContentPage {
            $page = ContentPage::create(Arr::except($data, ['faq_items']));
            $this->syncFaqItems($page, $data['faq_items'] ?? []);

            return $page;
        });

        return redirect()
            ->route('admin.content-pages.edit', $page)
            ->with('success', 'Content page created.');
    }

    public function edit(ContentPage $contentPage): Response
    {
        $contentPage->load('faqItems');

        return Inertia::render('Admin/ContentPages/Edit', [
            'contentPage' => $contentPage,
            'pageTypes' => $this->pageTypes(),
        ]);
    }

    public function update(Request $request, ContentPage $contentPage): RedirectResponse
    {
        $data = $this->validated($request, $contentPage);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['updated_by'] = $request->user()?->id;

        DB::transaction(function () use ($contentPage, $data): void {
            $contentPage->update(Arr::except($data, ['faq_items']));
            $this->syncFaqItems($contentPage, $data['faq_items'] ?? []);
        });

        return redirect()
            ->route('admin.content-pages.edit', $contentPage)
            ->with('success', 'Content page updated.');
    }

    public function destroy(ContentPage $contentPage): RedirectResponse
    {
        $contentPage->delete();

        return redirect()
            ->route('admin.content-pages.index')
            ->with('success', 'Content page deleted.');
    }

    private function validated(Request $request, ?ContentPage $page = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('content_pages', 'slug')->ignore($page?->id),
            ],
            'navigation_label' => ['nullable', 'string', 'max:100'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content_html' => ['nullable', 'string'],
            'page_type' => ['required', Rule::in(array_keys($this->pageTypes()))],
            'visibility' => ['required', Rule::in(['public', 'portal', 'both'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'menu_location' => ['required', Rule::in(['none', 'header', 'footer', 'both'])],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'effective_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:effective_at'],
            'help_key' => ['nullable', 'string', 'max:255'],
            'faq_items' => ['nullable', 'array'],
            'faq_items.*.question' => ['required_if:page_type,faq', 'nullable', 'string', 'max:500'],
            'faq_items.*.answer' => ['required_if:page_type,faq', 'nullable', 'string'],
            'faq_items.*.is_active' => ['nullable', 'boolean'],
            'faq_items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    private function syncFaqItems(ContentPage $page, array $items): void
    {
        if ($page->page_type !== ContentPage::TYPE_FAQ) {
            $page->faqItems()->delete();

            return;
        }

        $page->faqItems()->delete();

        foreach (array_values($items) as $index => $item) {
            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));

            if ($question === '' && $answer === '') {
                continue;
            }

            $page->faqItems()->create([
                'question' => $question,
                'answer' => $answer,
                'is_active' => (bool) ($item['is_active'] ?? true),
                'sort_order' => (int) ($item['sort_order'] ?? (($index + 1) * 10)),
            ]);
        }
    }

    private function pageTypes(): array
    {
        return [
            ContentPage::TYPE_STANDARD => [
                'label' => 'Standard Content',
                'description' => 'General program information with rich text.',
            ],
            ContentPage::TYPE_FAQ => [
                'label' => 'Frequently Asked Questions',
                'description' => 'Structured questions and answers displayed as an accordion.',
            ],
            ContentPage::TYPE_CONTACT_DIRECTORY => [
                'label' => 'Contact Directory',
                'description' => 'Program, PMO, leadership, or operational contact information.',
            ],
            ContentPage::TYPE_RESOURCE_LIBRARY => [
                'label' => 'Resource Library',
                'description' => 'Links to forms, templates, systems, policies, and documents.',
            ],
            ContentPage::TYPE_ANNOUNCEMENT => [
                'label' => 'Announcement',
                'description' => 'Time-sensitive program notices using effective and expiration dates.',
            ],
            ContentPage::TYPE_POLICY => [
                'label' => 'Policy or Documentation',
                'description' => 'Policies, procedures, instructions, and authoritative documentation.',
            ],
        ];
    }
}
