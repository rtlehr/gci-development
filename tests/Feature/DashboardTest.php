<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_are_redirected_from_the_legacy_dashboard_route(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertRedirect(route('portal.dashboard'));
    }

    public function test_authenticated_users_can_visit_the_portal_dashboard(): void
    {
        $user = User::factory()->create();

        foreach (['access_portal', 'portal_view_dashboard'] as $name) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $name],
                ['description' => $name],
            );
            $user->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $response = $this
            ->actingAs($user)
            ->get(route('portal.dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Dashboard')
                ->has('alerts')
                ->has('assignedTickets')
            );
    }
}