<?php

namespace Database\Seeders;

use App\Models\ContentPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContentPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Program Overview',
                'slug' => 'program-overview',
                'navigation_label' => 'Program',
                'summary' => 'An overview of the ZION INSIGHT Portal and the information and services available to the program community.',
                'content_html' => '<h2>About the Program</h2><p>The ZION INSIGHT Portal provides a unified location for program resources, requests, collaboration, and operational support.</p><h2>What You Can Find Here</h2><ul><li>Program details and contacts</li><li>Workforce and position information</li><li>Resources, policies, and documentation</li><li>Support and request workflows</li></ul>',
                'page_type' => 'standard',
                'visibility' => 'both',
                'status' => 'published',
                'menu_location' => 'header',
                'is_active' => true,
                'sort_order' => 10,
                'help_key' => 'content.program-overview',
            ],
            [
                'title' => 'Program Contacts',
                'slug' => 'program-contacts',
                'navigation_label' => 'Contacts',
                'summary' => 'Program and PMO points of contact.',
                'content_html' => '<h2>Program Contacts</h2><p>Use this page to publish program leadership, operational, contracting, and PMO contact information.</p><h2>PMO Contacts</h2><p>Contact details can be maintained here without changing application code.</p>',
                'page_type' => 'contact_directory',
                'visibility' => 'both',
                'status' => 'published',
                'menu_location' => 'header',
                'is_active' => true,
                'sort_order' => 20,
                'help_key' => 'content.program-contacts',
            ],
            [
                'title' => 'Resources',
                'slug' => 'resources',
                'navigation_label' => 'Resources',
                'summary' => 'Frequently used program resources, documentation, policies, and help links.',
                'content_html' => '<h2>Program Resources</h2><p>Add links to forms, templates, systems, shared repositories, policies, and documentation here.</p><h2>Documentation</h2><p>Publish reference materials and instructions that should be available to Public or Portal users.</p>',
                'page_type' => 'resource_library',
                'visibility' => 'both',
                'status' => 'published',
                'menu_location' => 'header',
                'is_active' => true,
                'sort_order' => 30,
                'help_key' => 'content.resources',
            ],
            [
                'title' => 'Frequently Asked Questions',
                'slug' => 'faqs',
                'navigation_label' => 'FAQs',
                'summary' => 'Answers to common questions about the program and portal.',
                'content_html' => '<p>Select a question below to view its answer.</p>',
                'page_type' => 'faq',
                'visibility' => 'both',
                'status' => 'published',
                'menu_location' => 'header',
                'is_active' => true,
                'sort_order' => 40,
                'help_key' => 'content.faqs',
            ],
            [
                'title' => 'Policies and Documentation',
                'slug' => 'policies-documentation',
                'navigation_label' => 'Policies',
                'summary' => 'Program policies, procedures, and authoritative documentation.',
                'content_html' => '<h2>Policies</h2><p>Publish approved policies and procedures here.</p><h2>Documentation</h2><p>Use effective and expiration dates when content should only be available during a defined period.</p>',
                'page_type' => 'policy',
                'visibility' => 'portal',
                'status' => 'published',
                'menu_location' => 'none',
                'is_active' => true,
                'sort_order' => 50,
                'help_key' => 'content.policies-documentation',
            ],
            [
                'title' => 'Announcements',
                'slug' => 'announcements',
                'navigation_label' => 'Announcements',
                'summary' => 'Current program announcements and time-sensitive information.',
                'content_html' => '<h2>Program Announcements</h2><p>Use effective and expiration dates to control when time-sensitive notices are available.</p>',
                'page_type' => 'announcement',
                'visibility' => 'portal',
                'status' => 'draft',
                'menu_location' => 'none',
                'is_active' => true,
                'sort_order' => 60,
                'help_key' => 'content.announcements',
            ],
        ];

        foreach ($pages as $pageData) {
            ContentPage::updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData,
            );
        }

        $faqPage = ContentPage::query()->where('slug', 'faqs')->first();

        if (! $faqPage) {
            return;
        }

        $faqItems = [
            [
                'question' => 'What is the INSIGHT Portal?',
                'answer' => 'It is the central location for program information, resources, workforce tools, and operational support.',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'question' => 'Where do I request help?',
                'answer' => 'Use Support in the main navigation to submit and track a support request.',
                'is_active' => true,
                'sort_order' => 20,
            ],
        ];

        foreach ($faqItems as $item) {
            DB::table('content_page_faq_items')->updateOrInsert(
                [
                    'content_page_id' => $faqPage->id,
                    'question' => $item['question'],
                ],
                [
                    'answer' => $item['answer'],
                    'is_active' => $item['is_active'],
                    'sort_order' => $item['sort_order'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
