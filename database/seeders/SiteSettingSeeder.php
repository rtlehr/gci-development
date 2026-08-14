<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Branding
            ['key' => 'branding.program_mark', 'group' => 'Branding', 'label' => 'Program mark', 'description' => 'Short program name shown above the portal name in the header.', 'type' => 'text', 'value' => 'ZION', 'sort_order' => 10],
            ['key' => 'branding.portal_name', 'group' => 'Branding', 'label' => 'Portal name', 'description' => 'Main portal name shown in the public and portal header.', 'type' => 'text', 'value' => 'INSIGHT Portal', 'sort_order' => 20],
            ['key' => 'branding.site_name', 'group' => 'Branding', 'label' => 'Full site name', 'description' => 'Full application name used in page titles and other site text.', 'type' => 'text', 'value' => 'ZION INSIGHT Portal', 'sort_order' => 30],
            ['key' => 'branding.primary_color', 'group' => 'Branding', 'label' => 'Primary color', 'description' => 'Primary green used for buttons, icons, links, and emphasis.', 'type' => 'color', 'value' => '#005c43', 'sort_order' => 40],
            ['key' => 'branding.primary_hover_color', 'group' => 'Branding', 'label' => 'Primary hover color', 'description' => 'Darker green used when hovering over primary actions.', 'type' => 'color', 'value' => '#004734', 'sort_order' => 50],
            ['key' => 'branding.surface_color', 'group' => 'Branding', 'label' => 'Surface color', 'description' => 'Background color for headers, cards, and content surfaces.', 'type' => 'color', 'value' => '#ffffff', 'sort_order' => 60],
            ['key' => 'branding.page_background_color', 'group' => 'Branding', 'label' => 'Page background color', 'description' => 'Background color behind public and portal content.', 'type' => 'color', 'value' => '#f7f8f7', 'sort_order' => 70],
            ['key' => 'branding.border_color', 'group' => 'Branding', 'label' => 'Border color', 'description' => 'Default border and divider color.', 'type' => 'color', 'value' => '#e3e3e3', 'sort_order' => 80],
            ['key' => 'branding.text_color', 'group' => 'Branding', 'label' => 'Text color', 'description' => 'Primary text color used throughout public and portal templates.', 'type' => 'color', 'value' => '#3a3a3a', 'sort_order' => 90],

            // Program
            ['key' => 'program.eyebrow', 'group' => 'Program', 'label' => 'Program eyebrow', 'description' => 'Small program label shown above the homepage heading.', 'type' => 'text', 'value' => 'ZION Program', 'sort_order' => 10],
            ['key' => 'program.name', 'group' => 'Program', 'label' => 'Program portal name', 'description' => 'Program name used in the homepage welcome heading.', 'type' => 'text', 'value' => 'ZION INSIGHT Portal', 'sort_order' => 20],
            ['key' => 'program.summary', 'group' => 'Program', 'label' => 'Program summary', 'description' => 'Introductory summary displayed on the homepage.', 'type' => 'textarea', 'value' => 'A unified program portal for resources, requests, collaboration, and operational support.', 'sort_order' => 30],
            ['key' => 'program.contract_year', 'group' => 'Program', 'label' => 'Contract year', 'description' => 'Current contract year displayed in Program details.', 'type' => 'text', 'value' => 'Base Year', 'sort_order' => 40],
            ['key' => 'program.contract_number', 'group' => 'Program', 'label' => 'Contract number', 'description' => 'Contract number displayed in Program details.', 'type' => 'text', 'value' => 'B2026-#########', 'sort_order' => 50],
            ['key' => 'program.period_of_performance', 'group' => 'Program', 'label' => 'Period of performance', 'description' => 'Current period of performance displayed in Program details.', 'type' => 'text', 'value' => 'May 1, 2026 – April 30, 2027', 'sort_order' => 60],

            // Homepage
            ['key' => 'home.primary_action_label', 'group' => 'Homepage', 'label' => 'Primary action label', 'description' => 'Button text used to open the authenticated user portal.', 'type' => 'text', 'value' => 'Open my portal', 'sort_order' => 10],
            ['key' => 'home.secondary_action_label', 'group' => 'Homepage', 'label' => 'Secondary action label', 'description' => 'Button text used to jump to Program details.', 'type' => 'text', 'value' => 'Program details', 'sort_order' => 20],
            ['key' => 'home.program_contacts_title', 'group' => 'Homepage', 'label' => 'Program contacts title', 'description' => 'Title of the Program contacts feature card.', 'type' => 'text', 'value' => 'Program contacts', 'sort_order' => 30],
            ['key' => 'home.program_contacts_description', 'group' => 'Homepage', 'label' => 'Program contacts description', 'description' => 'Description of the Program contacts feature card.', 'type' => 'textarea', 'value' => 'Find program leadership, PMO contacts, and customer points of contact.', 'sort_order' => 40],
            ['key' => 'home.resources_title', 'group' => 'Homepage', 'label' => 'Resources title', 'description' => 'Title of the Resources feature card.', 'type' => 'text', 'value' => 'Resources', 'sort_order' => 50],
            ['key' => 'home.resources_description', 'group' => 'Homepage', 'label' => 'Resources description', 'description' => 'Description of the Resources feature card.', 'type' => 'textarea', 'value' => 'Access approved program guidance, forms, policies, and reference materials.', 'sort_order' => 60],
            ['key' => 'home.requests_title', 'group' => 'Homepage', 'label' => 'Requests title', 'description' => 'Title of the Requests feature card.', 'type' => 'text', 'value' => 'Requests', 'sort_order' => 70],
            ['key' => 'home.requests_description', 'group' => 'Homepage', 'label' => 'Requests description', 'description' => 'Description of the Requests feature card.', 'type' => 'textarea', 'value' => 'Authenticated users will be able to submit and monitor program requests.', 'sort_order' => 80],
            ['key' => 'home.faqs_title', 'group' => 'Homepage', 'label' => 'Frequently asked questions title', 'description' => 'Title of the FAQ feature card.', 'type' => 'text', 'value' => 'Frequently asked questions', 'sort_order' => 90],
            ['key' => 'home.faqs_description', 'group' => 'Homepage', 'label' => 'Frequently asked questions description', 'description' => 'Description of the FAQ feature card.', 'type' => 'textarea', 'value' => 'Get clear answers to common questions about the program and portal.', 'sort_order' => 100],
            ['key' => 'home.support_title', 'group' => 'Homepage', 'label' => 'Support title', 'description' => 'Title of the Support feature card.', 'type' => 'text', 'value' => 'Support', 'sort_order' => 110],
            ['key' => 'home.support_description', 'group' => 'Homepage', 'label' => 'Support description', 'description' => 'Description of the Support feature card.', 'type' => 'textarea', 'value' => 'Report a problem, request assistance, or follow an existing support ticket.', 'sort_order' => 120],
            ['key' => 'home.pmo_title', 'group' => 'Homepage', 'label' => 'PMO title', 'description' => 'Title of the PMO feature card.', 'type' => 'text', 'value' => 'PMO', 'sort_order' => 130],
            ['key' => 'home.pmo_description', 'group' => 'Homepage', 'label' => 'PMO description', 'description' => 'Description of the PMO feature card.', 'type' => 'textarea', 'value' => 'Connect with the program management office and operational support team.', 'sort_order' => 140],

            // Portal Features
            ['key' => 'features.support_tickets', 'group' => 'Portal Features', 'label' => 'Support Tickets', 'description' => 'Allow portal users to submit and review support tickets. Administrative ticket management remains available when disabled.', 'type' => 'boolean', 'value' => '1', 'sort_order' => 10],
            ['key' => 'features.alerts', 'group' => 'Portal Features', 'label' => 'Alerts', 'description' => 'Show alerts, notification counts, and alert history in the Public and Portal experience. Administrative alert access remains available when disabled.', 'type' => 'boolean', 'value' => '1', 'sort_order' => 20],
            ['key' => 'features.help', 'group' => 'Portal Features', 'label' => 'Help', 'description' => 'Show contextual Help buttons and Help content in the Public and Portal experience. Page Help administration remains available when disabled.', 'type' => 'boolean', 'value' => '1', 'sort_order' => 30],
            ['key' => 'features.candidate_opportunities', 'group' => 'Portal Features', 'label' => 'Candidate Opportunities', 'description' => 'Show candidate-facing position opportunities and progress on the Portal dashboard. Candidate administration remains available when disabled.', 'type' => 'boolean', 'value' => '1', 'sort_order' => 40],
            ['key' => 'features.content_pages', 'group' => 'Portal Features', 'label' => 'Public Content Pages', 'description' => 'Show published informational pages in Public and Portal navigation. Content Page administration remains available when disabled.', 'type' => 'boolean', 'value' => '1', 'sort_order' => 50],

            // Footer
            ['key' => 'footer.copyright_name', 'group' => 'Footer', 'label' => 'Copyright name', 'description' => 'Name displayed after the copyright year.', 'type' => 'text', 'value' => 'ZION INSIGHT Portal', 'sort_order' => 10],
            ['key' => 'footer.support_label', 'group' => 'Footer', 'label' => 'Support link label', 'description' => 'Text used for the footer Support link.', 'type' => 'text', 'value' => 'Support', 'sort_order' => 20],
            ['key' => 'footer.faqs_label', 'group' => 'Footer', 'label' => 'FAQ link label', 'description' => 'Text used for the footer FAQ link.', 'type' => 'text', 'value' => 'FAQs', 'sort_order' => 30],
        ];

        foreach ($settings as $setting) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }

        app(SiteSettingsService::class)->forget();
    }
}
