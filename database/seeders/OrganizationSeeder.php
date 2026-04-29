<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $root = Organization::updateOrCreate(
            ['id' => 1],
            [
                'parent_id' => null,
                'name' => 'Org Root',
                'status' => 'active',
                'notes' => 'Default root organization.',
            ]
        );

        // 🔥 Build hierarchy fields (full_path, path_ids, depth)
        $root->rebuildHierarchyFields();

        $child = Organization::create([
            'name' => 'Org Child',
            'parent_id' => 1,
            'status' => 'active',
        ]);

        $child->rebuildHierarchyFields();

        $sub = Organization::create([
            'name' => 'Org Sub',
            'parent_id' => $child->id,
            'status' => 'active',
        ]);

        $sub->rebuildHierarchyFields();

    }
}