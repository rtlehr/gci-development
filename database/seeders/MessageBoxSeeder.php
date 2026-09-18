<?php

namespace Database\Seeders;

use App\Models\MessageBox;
use Illuminate\Database\Seeder;

class MessageBoxSeeder extends Seeder
{
    public function run(): void
    {
        MessageBox::updateOrCreate(['name' => 'Example Portal Announcement'], [
            'title' => 'Example Portal Announcement',
            'content_html' => '<p>This disabled example demonstrates the Insite Portal message box feature. Enable or edit it from Admin → Message Boxes.</p>',
            'display_type' => 'modal', 'audience' => 'everyone', 'page_patterns' => ['/portal/*'],
            'trigger_type' => 'automatic', 'show_once' => true, 'dismissible' => true,
            'priority' => 100, 'actions' => [['label' => 'Open My Portal', 'url' => '/portal']],
            'form_fields' => [], 'is_active' => false,
        ]);
    }
}
