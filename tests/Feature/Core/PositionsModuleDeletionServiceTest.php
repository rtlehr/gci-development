<?php

use App\Models\Position;
use App\Models\PositionActivity;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Services\UserEventLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insite\Modules\Positions\Http\Controllers\PositionsController as LegacyController;
use Insite\Modules\Positions\Http\Controllers\Portal\PositionController as PortalController;
use Insite\Modules\Positions\Services\PositionDeletionService;

uses(RefreshDatabase::class);

it('refuses deletion when any assignment exists without creating a deletion activity', function () {
    $position = Position::factory()->create();
    PositionAssignment::factory()->ended()->create(['position_id' => $position->id]);

    $deleted = app(PositionDeletionService::class)->delete($position, null);

    expect($deleted)->toBeFalse()
        ->and(Position::find($position->id))->not->toBeNull()
        ->and(PositionActivity::where('position_id', $position->id)->where('action', 'deleted')->exists())->toBeFalse();
});

it('deletes unassigned positions while retaining legacy deletion activity behavior', function () {
    $position = Position::factory()->create();
    $user = User::factory()->create();
    $positionId = $position->id;
    $recordedActivity = null;
    PositionActivity::created(function (PositionActivity $activity) use (&$recordedActivity) {
        $recordedActivity = $activity->getAttributes();
    });

    $deleted = app(PositionDeletionService::class)->delete($position, $user->id);

    expect($deleted)->toBeTrue()
        ->and(Position::find($positionId))->toBeNull()
        ->and($recordedActivity['position_id'] ?? null)->toBe($positionId)
        ->and($recordedActivity['user_id'] ?? null)->toBe($user->id)
        ->and($recordedActivity['action'] ?? null)->toBe('deleted')
        ->and($recordedActivity['description'] ?? null)->toBe('Position deleted.');
});

it('preserves Portal-only user-event audit logging and routes both controllers through the module service', function () {
    $position = Position::factory()->create();
    $logger = Mockery::mock(UserEventLogger::class);
    $logger->shouldReceive('recordModelEvent')->once();

    $deleted = (new PositionDeletionService($logger))->delete($position, null, auditPortal: true);
    expect($deleted)->toBeTrue();

    foreach ([LegacyController::class, PortalController::class] as $controller) {
        $source = file_get_contents((new ReflectionClass($controller))->getFileName());
        expect($source)->toContain('PositionDeletionService $deletionService')
            ->not->toContain('PositionActivity::create(')
            ->not->toContain('PositionActivity::create(');
    }

    $portal = file_get_contents((new ReflectionClass(PortalController::class))->getFileName());
    expect($portal)->toContain('auditPortal: true');
});
