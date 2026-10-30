<?php

use App\Core\Modules\ModuleCatalog;
use App\Core\Modules\ModuleDeploymentPreflight;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->preflightDir = storage_path('framework/testing/preflight-'.uniqid());
    File::ensureDirectoryExists($this->preflightDir);
});

afterEach(function () {
    File::deleteDirectory($this->preflightDir);
});

function preflightFixture(string $root, string $id, ?string $core = null, array $requires = []): void
{
    File::ensureDirectoryExists($root.'/'.$id);
    File::put($root.'/'.$id.'/module.json', json_encode([
        'id' => $id, 'name' => ucfirst($id), 'version' => '1.0.0',
        'core' => $core, 'requires' => $requires,
    ], JSON_THROW_ON_ERROR));
}

it('reports valid default deployment without changing module selection', function () {
    config()->set('insite_modules.enabled', []);
    $this->artisan('insite:modules:preflight')
        ->expectsOutputToContain('Deployment preflight passed')
        ->assertSuccessful();
    expect(config('insite_modules.enabled'))->toBe([]);
});

it('detects incompatible Core versions for selected modules', function () {
    preflightFixture($this->preflightDir, 'travel', '^2.0.0');
    $service = new ModuleDeploymentPreflight(new ModuleCatalog($this->preflightDir));
    expect(fn () => $service->inspect(['travel'], '1.0.0'))
        ->toThrow(InvalidArgumentException::class, 'requires Insite Core');
});

it('accepts a compatible Core version and inspects migrations without running them', function () {
    preflightFixture($this->preflightDir, 'finance', '^1.0.0');
    $dir = $this->preflightDir.'/finance/database/migrations';
    File::ensureDirectoryExists($dir);
    File::put($dir.'/2026_01_01_000000_create_finance_example.php', '<?php throw new RuntimeException("must not execute");');
    $result = (new ModuleDeploymentPreflight(new ModuleCatalog($this->preflightDir)))
        ->inspect(['finance'], '1.2.0');
    expect($result['order'])->toBe(['finance'])
        ->and($result['migration_files']['finance'])->toBe(1);
});

it('rejects malformed Core compatibility declarations', function () {
    preflightFixture($this->preflightDir, 'travel', 'anything');
    expect(fn () => (new ModuleCatalog($this->preflightDir))->all())
        ->toThrow(InvalidArgumentException::class, 'Core version constraint');
});

it('fails preflight for unavailable selected modules', function () {
    config()->set('insite_modules.enabled', ['module-not-installed']);
    $this->artisan('insite:modules:preflight')
        ->expectsOutputToContain('Deployment preflight failed:')
        ->assertFailed();
});

it('rejects invalid Core contract version configuration', function () {
    $service = new ModuleDeploymentPreflight(new ModuleCatalog($this->preflightDir));
    expect(fn () => $service->inspect([], '1.0'))
        ->toThrow(InvalidArgumentException::class, 'major.minor.patch');
});
