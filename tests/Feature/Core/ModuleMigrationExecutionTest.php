<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Integration test against the existing isolated IRAD test database.
 * Never modify an arbitrary configured database, even if APP_ENV=testing.
 * The dedicated demo table must not already exist: this test refuses to overwrite it.
 */
function requireIsolatedModuleExecutionDatabase(): void
{
    $connection = DB::connection();
    $name = $connection->getDatabaseName();

    if (! app()->environment('testing') || ! is_string($name) || $name !== 'irad_testing') {
        throw new RuntimeException(
            'Refusing module migration integration test: APP_ENV must be testing and actual database name must be irad_testing; current database is '.var_export($name, true)
        );
    }
}

it('applies module migrations once and preserves data after disabling the module', function () {
    requireIsolatedModuleExecutionDatabase();

    $migration = '2026_10_30_000000_create_insite_demo_records_table';
    $table = 'insite_demo_records';

    // Never destroy a table or migration entry left by someone else's test/deployment.
    expect(Schema::hasTable($table))->toBeFalse('Demo table already exists; no changes made.');
    expect(Schema::hasTable('migrations'))->toBeTrue('Laravel migration repository is missing; no changes made.');
    expect(DB::table('migrations')->where('migration', $migration)->exists())
        ->toBeFalse('Demo migration is already recorded; no changes made.');

    $oldEnabled = config('insite_modules.enabled');

    try {
        config()->set('insite_modules.enabled', ['demo']);

        // --force avoids an interactive test prompt, not the preflight checks.
        $this->artisan('insite:modules:migrate', [
            'module' => 'demo', '--apply' => true, '--force' => true,
        ])->assertSuccessful();

        expect(Schema::hasTable($table))->toBeTrue();
        expect(DB::table('migrations')->where('migration', $migration)->count())->toBe(1);

        DB::table($table)->insert([
            'label' => '4C5 preservation verification',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // A second application must respect Laravel's migration repository.
        $this->artisan('insite:modules:migrate', [
            'module' => 'demo', '--apply' => true, '--force' => true,
        ])->assertSuccessful();

        expect(DB::table('migrations')->where('migration', $migration)->count())->toBe(1);
        expect(DB::table($table)->where('label', '4C5 preservation verification')->count())->toBe(1);

        // Disable the module: neither its table nor its records may be erased.
        config()->set('insite_modules.enabled', []);
        expect(Schema::hasTable($table))->toBeTrue();
        expect(DB::table($table)->where('label', '4C5 preservation verification')->count())->toBe(1);
        expect(DB::table('migrations')->where('migration', $migration)->count())->toBe(1);

        // Disabled modules must not be able to run migrations again.
        $this->artisan('insite:modules:migrate', [
            'module' => 'demo', '--apply' => true, '--force' => true,
        ])->expectsOutputToContain('Module migration preflight failed:')->assertFailed();

        expect(DB::table($table)->where('label', '4C5 preservation verification')->count())->toBe(1);
    } finally {
        // Only reach here after confirming both artifacts were absent at test entry.
        // Clean up the exact demo artifacts created for this test; no global rollback.
        if (Schema::hasTable($table)) {
            Schema::drop($table);
        }
        DB::table('migrations')->where('migration', $migration)->delete();
        config()->set('insite_modules.enabled', $oldEnabled);
    }

    expect(Schema::hasTable($table))->toBeFalse();
    expect(DB::table('migrations')->where('migration', $migration)->exists())->toBeFalse();
});

it('does not execute module migrations through a default preview', function () {
    requireIsolatedModuleExecutionDatabase();

    $table = 'insite_demo_records';
    expect(Schema::hasTable($table))->toBeFalse('Demo table must not preexist for this test.');

    $previous = config('insite_modules.enabled');
    try {
        config()->set('insite_modules.enabled', ['demo']);
        $this->artisan('insite:modules:migrate', ['module' => 'demo'])
            ->expectsOutputToContain('Preview only.')
            ->assertSuccessful();
        expect(Schema::hasTable($table))->toBeFalse();
    } finally {
        config()->set('insite_modules.enabled', $previous);
    }
});
