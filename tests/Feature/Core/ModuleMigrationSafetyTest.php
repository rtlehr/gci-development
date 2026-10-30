<?php

use App\Core\Modules\ModuleCatalog;
use App\Core\Modules\ModuleDeploymentPreflight;
use App\Core\Modules\ModuleMigrationPlan;
use Illuminate\Support\Facades\File;

function moduleMigrationFixture(string $root, string $id, string $core = '^1.0.0'): void
{
    File::ensureDirectoryExists($root.'/'.$id.'/database/migrations');
    File::put($root.'/'.$id.'/module.json', json_encode([
        'id' => $id, 'name' => ucfirst($id), 'version' => '1.0.0',
        'core' => $core, 'requires' => [],
    ], JSON_THROW_ON_ERROR));
}

beforeEach(function () {
    $this->migrationFixtures = storage_path('framework/testing/module-migrations-'.uniqid());
    File::ensureDirectoryExists($this->migrationFixtures);
});

afterEach(function () {
    File::deleteDirectory($this->migrationFixtures);
});

it('previews the demo migration without executing SQL', function () {
    config()->set('insite_modules.enabled', ['demo']);
    $this->artisan('insite:modules:migrate', ['module' => 'demo'])
        ->expectsOutputToContain('Preview only')
        ->assertSuccessful();
});

it('refuses migrations of disabled modules', function () {
    config()->set('insite_modules.enabled', []);
    $this->artisan('insite:modules:migrate', ['module' => 'demo', '--apply' => true])
        ->expectsOutputToContain('Module migration preflight failed:')
        ->assertFailed();
});

it('refuses unknown modules without running code', function () {
    config()->set('insite_modules.enabled', ['nonexistent']);
    $this->artisan('insite:modules:migrate', ['module' => 'nonexistent', '--apply' => true])
        ->expectsOutputToContain('Module migration preflight failed:')
        ->assertFailed();
});

it('enumerates only PHP migration files in deterministic order', function () {
    moduleMigrationFixture($this->migrationFixtures, 'travel');
    $directory = $this->migrationFixtures.'/travel/database/migrations';
    File::put($directory.'/2026_02_01_000000_second.php', '<?php throw new RuntimeException("do not execute");');
    File::put($directory.'/2026_01_01_000000_first.php', '<?php throw new RuntimeException("do not execute");');
    File::put($directory.'/notes.txt', 'ignore');
    $catalog = new ModuleCatalog($this->migrationFixtures);
    $plan = (new ModuleMigrationPlan($catalog, new ModuleDeploymentPreflight($catalog)))
        ->inspect('travel', ['travel'], '1.0.0');
    expect($plan['files'])->toBe([
        '2026_01_01_000000_first.php', '2026_02_01_000000_second.php',
    ]);
});

it('blocks incompatible Core versions before migration execution', function () {
    moduleMigrationFixture($this->migrationFixtures, 'travel', '^2.0.0');
    $catalog = new ModuleCatalog($this->migrationFixtures);
    expect(fn () => (new ModuleMigrationPlan($catalog, new ModuleDeploymentPreflight($catalog)))
        ->inspect('travel', ['travel'], '1.0.0'))
        ->toThrow(InvalidArgumentException::class, 'requires Insite Core');
});

it('rejects a linked migration directory instead of following it', function () {
    moduleMigrationFixture($this->migrationFixtures, 'travel');
    $catalog = new ModuleCatalog($this->migrationFixtures);
    $plan = (new ModuleMigrationPlan($catalog, new ModuleDeploymentPreflight($catalog)))
        ->inspect('travel', ['travel'], '1.0.0');
    expect($plan['files'])->toBe([]);
});
