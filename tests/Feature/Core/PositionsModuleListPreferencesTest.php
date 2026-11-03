<?php

use App\Models\User;
use App\Models\UserListPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Insite\Modules\Positions\Http\Controllers\PositionsController as LegacyController;
use Insite\Modules\Positions\Http\Controllers\Portal\PositionController as PortalController;
use Insite\Modules\Positions\Services\PositionListPreferences;

uses(RefreshDatabase::class);

it('saves only valid Positions column keys while preserving order and visibility choices', function () {
    $user = User::factory()->create();
    $service = app(PositionListPreferences::class);
    $service->save(Request::create('/positions/preferences', 'POST', [
        'visible_columns' => ['status', 'invalid_column', 'position_code'],
        'column_order' => ['position_code', 'invalid_column', 'status'],
    ]), $user->id);

    $saved = UserListPreference::where('user_id', $user->id)->where('list_key', 'positions')->firstOrFail();
    expect($saved->visible_columns)->toBe(['status', 'position_code'])
        ->and($saved->column_order)->toBe(['position_code', 'status']);

    // Updating the same list must not create a second record.
    $service->save(Request::create('/positions/preferences', 'POST', [
        'visible_columns' => ['id'],
        'column_order' => ['id'],
    ]), $user->id);
    expect(UserListPreference::where('user_id', $user->id)->where('list_key', 'positions')->count())->toBe(1)
        ->and($saved->fresh()->visible_columns)->toBe(['id']);
});

it('resets only the current user Positions list preferences', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    foreach ([[$user->id, 'positions'], [$user->id, 'people'], [$other->id, 'positions']] as [$id, $key]) {
        UserListPreference::create([
            'user_id' => $id, 'list_key' => $key,
            'visible_columns' => ['id'], 'column_order' => ['id'],
        ]);
    }

    app(PositionListPreferences::class)->reset($user->id);

    expect(UserListPreference::where('user_id', $user->id)->where('list_key', 'positions')->exists())->toBeFalse()
        ->and(UserListPreference::where('user_id', $user->id)->where('list_key', 'people')->exists())->toBeTrue()
        ->and(UserListPreference::where('user_id', $other->id)->where('list_key', 'positions')->exists())->toBeTrue();
});

it('preserves array validation and delegates both module controllers to the same preferences service', function () {
    $user = User::factory()->create();
    try {
        app(PositionListPreferences::class)->save(Request::create('/positions/preferences', 'POST', [
            'visible_columns' => 'not-an-array', 'column_order' => ['id'],
        ]), $user->id);
        test()->fail('Expected validation exception.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('visible_columns');
    }

    foreach ([LegacyController::class, PortalController::class] as $controller) {
        $source = file_get_contents((new ReflectionClass($controller))->getFileName());
        expect($source)->toContain('app(PositionListPreferences::class)->save($request, $userResolver->resolveUserId())')
            ->toContain('app(PositionListPreferences::class)->reset($userResolver->resolveUserId())');
    }
});
