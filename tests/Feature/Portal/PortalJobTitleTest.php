<?php

use App\Models\JobTitle;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function userWithJobTitlePermission(string $permission): User
{
    $user = User::factory()->create();
    foreach (array_unique(['access_portal', 'portal_view_positions', $permission]) as $name) {
        $permissionModel = Permission::query()->firstOrCreate(
            ['name' => $name],
            ['description' => $name],
        );
        $user->permissions()->syncWithoutDetaching([$permissionModel->id]);
    }

    return $user;
}

test('authorized users can view portal job titles', function () {
    $user = userWithJobTitlePermission('portal_view_positions');
    JobTitle::query()->create(['name' => 'Developer', 'is_active' => true, 'sort_order' => 0]);

    $this->actingAs($user)
        ->get(route('portal.job-titles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portal/JobTitles/Index')
            ->has('jobTitles', 1));
});

test('authorized users can manage portal job title requirements', function () {
    $user = userWithJobTitlePermission('update_positions');
    $jobTitle = JobTitle::query()->create(['name' => 'Engineer', 'is_active' => true, 'sort_order' => 0]);

    $this->actingAs($user)
        ->post(route('portal.job-titles.skills.store', $jobTitle), [
            'name' => 'Laravel',
            'requirement_type' => 'required',
            'is_active' => true,
            'sort_order' => 1,
        ])
        ->assertRedirect(route('portal.job-titles.show', $jobTitle));

    expect($jobTitle->skills()->where('name', 'Laravel')->exists())->toBeTrue();
});
