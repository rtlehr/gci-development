<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPermission;
use App\Models\JobTitle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithIradTestData;
use Tests\TestCase;

class ProjectManagerRoleEnforcementTest extends TestCase
{
    use InteractsWithIradTestData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Keep this test focused on Project Manager eligibility
        |--------------------------------------------------------------------------
        |
        | Permission middleware has its own tests. The authenticated actor still
        | has a valid linked Person record because UserResolver requires one.
        |
        */

        $this->withoutMiddleware(CheckPermission::class);
    }

    public function test_user_without_project_manager_role_cannot_be_assigned_to_position(): void
    {
        $this->actingAsLinkedUser();

        $cotr = $this->createCotr();
        $jobTitle = $this->createJobTitle();

        $response = $this
            ->from('/positions/create')
            ->post('/positions', $this->validPositionPayload(
                jobTitle: $jobTitle,
                projectManagerUserId: $cotr['user']->id,
                positionCode: 'TEST-PM-INVALID-001',
            ));

        $response
            ->assertRedirect('/positions/create')
            ->assertSessionHasErrors('project_manager_user_id');

        $this->assertDatabaseMissing('positions', [
            'position_code' => 'TEST-PM-INVALID-001',
        ]);
    }

    public function test_user_with_project_manager_role_can_be_assigned_to_position(): void
    {
        $this->actingAsLinkedUser();

        $projectManager = $this->createProjectManager();
        $jobTitle = $this->createJobTitle();

        $response = $this->post(
            '/positions',
            $this->validPositionPayload(
                jobTitle: $jobTitle,
                projectManagerUserId: $projectManager['user']->id,
                positionCode: 'TEST-PM-VALID-001',
            ),
        );

        $response
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('positions', [
            'position_code' => 'TEST-PM-VALID-001',
            'job_title_id' => $jobTitle->id,
            'project_manager_user_id' => $projectManager['user']->id,
        ]);

        $this->assertDatabaseHas('position_activities', [
            'action' => 'created',
        ]);
    }

    private function validPositionPayload(
        JobTitle $jobTitle,
        int $projectManagerUserId,
        string $positionCode,
    ): array {
        return [
            'position_code' => $positionCode,
            'status' => 'Open',
            'job_title_id' => $jobTitle->id,
            'project_manager_user_id' => $projectManagerUserId,
            'is_essential' => false,
            'travel_required' => false,
            'high_risk_role' => false,
            'request_to_close' => false,
        ];
    }
}
