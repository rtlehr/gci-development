<?php

namespace Database\Seeders;

use App\Models\PageHelp;
use Illuminate\Database\Seeder;

class PageHelpSeeder extends Seeder
{
    public function run(): void
    {
        PageHelp::updateOrCreate(
            ['help_key' => 'people.create'],
            [
                'title' => 'Create Person',
                'content_html' => "Use this page to create a new person record.\n\nComplete the required fields first.\nAdd phone numbers and addresses if needed.\nReview the information before saving.",
                'is_active' => true,
            ]
        );

        PageHelp::updateOrCreate(
            ['help_key' => 'people.edit'],
            [
                'title' => 'Edit Person',
                'content_html' => "Use this page to update an existing person.\n\nReview all important information before saving changes.",
                'is_active' => true,
            ]
        );
    }
}