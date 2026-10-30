<?php

use App\Core\Modules\ModuleCatalog;
use App\Core\Modules\ModuleDependencyResolver;
use App\Core\Modules\ModuleVersion;
use Illuminate\Support\Facades\File;

uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->resolverTestDir = storage_path('framework/testing/module-resolver-'.uniqid());
    File::ensureDirectoryExists($this->resolverTestDir);
});

afterEach(function () {
    File::deleteDirectory($this->resolverTestDir);
});

function createResolverModule(string $root, string $id, string $version = '1.0.0', array $requires = []): void
{
    File::ensureDirectoryExists($root.'/'.$id);
    File::put($root.'/'.$id.'/module.json', json_encode(compact('id', 'version', 'requires') + ['name' => ucfirst($id)], JSON_THROW_ON_ERROR));
}

function resolverFor(string $root, array $enabled): ModuleDependencyResolver
{
    return new ModuleDependencyResolver(new ModuleCatalog($root), $enabled);
}

it('accepts empty enabled selection with no installed modules', function () {
    expect(resolverFor($this->resolverTestDir, [])->resolve())->toBe([]);
});

it('lists independent modules in deterministic order', function () {
    createResolverModule($this->resolverTestDir, 'travel');
    createResolverModule($this->resolverTestDir, 'finance');
    expect(resolverFor($this->resolverTestDir, ['travel', 'finance'])->resolve())->toBe(['finance', 'travel']);
});

it('returns dependencies before dependents', function () {
    createResolverModule($this->resolverTestDir, 'positions', '1.2.0');
    createResolverModule($this->resolverTestDir, 'finance', '1.0.0', ['positions' => '^1.0.0']);
    createResolverModule($this->resolverTestDir, 'travel', '1.0.0', ['finance' => '>=1.0.0']);
    expect(resolverFor($this->resolverTestDir, ['travel', 'positions', 'finance'])->resolve())
        ->toBe(['positions', 'finance', 'travel']);
});

it('rejects an enabled module that is not installed', function () {
    expect(fn () => resolverFor($this->resolverTestDir, ['travel'])->resolve())->toThrow(InvalidArgumentException::class, 'not installed');
});

it('rejects missing required modules', function () {
    createResolverModule($this->resolverTestDir, 'finance', '1.0.0', ['positions' => '^1.0.0']);
    expect(fn () => resolverFor($this->resolverTestDir, ['finance'])->resolve())->toThrow(InvalidArgumentException::class, 'missing module');
});

it('rejects required modules which are present but disabled', function () {
    createResolverModule($this->resolverTestDir, 'positions');
    createResolverModule($this->resolverTestDir, 'finance', '1.0.0', ['positions' => '^1.0.0']);
    expect(fn () => resolverFor($this->resolverTestDir, ['finance'])->resolve())->toThrow(InvalidArgumentException::class, 'disabled module');
});

it('rejects incompatible required versions', function () {
    createResolverModule($this->resolverTestDir, 'positions', '2.0.0');
    createResolverModule($this->resolverTestDir, 'finance', '1.0.0', ['positions' => '^1.0.0']);
    expect(fn () => resolverFor($this->resolverTestDir, ['positions', 'finance'])->resolve())->toThrow(InvalidArgumentException::class, 'version');
});

it('detects circular dependencies', function () {
    createResolverModule($this->resolverTestDir, 'positions', '1.0.0', ['finance' => '^1.0.0']);
    createResolverModule($this->resolverTestDir, 'finance', '1.0.0', ['positions' => '^1.0.0']);
    expect(fn () => resolverFor($this->resolverTestDir, ['finance', 'positions'])->resolve())->toThrow(InvalidArgumentException::class, 'Circular');
});

it('rejects duplicate enabled module identifiers', function () {
    createResolverModule($this->resolverTestDir, 'travel');
    expect(fn () => resolverFor($this->resolverTestDir, ['travel', 'travel'])->resolve())->toThrow(InvalidArgumentException::class, 'more than once');
});

it('rejects invalid dependency manifests', function () {
    createResolverModule($this->resolverTestDir, 'travel', '1.0.0', ['finance' => 'banana']);
    expect(fn () => (new ModuleCatalog($this->resolverTestDir))->all())->toThrow(InvalidArgumentException::class);
});

it('rejects self dependencies and invalid module versions', function () {
    createResolverModule($this->resolverTestDir, 'travel', '1.0.0', ['travel' => '^1.0.0']);
    expect(fn () => (new ModuleCatalog($this->resolverTestDir))->all())->toThrow(InvalidArgumentException::class);
    createResolverModule($this->resolverTestDir, 'travel', '1.0', []);
    expect(fn () => (new ModuleCatalog($this->resolverTestDir))->all())->toThrow(InvalidArgumentException::class);
});

it('implements stable version operators and zero-major caret correctly', function () {
    expect(ModuleVersion::matches('1.4.0', '^1.2.0'))->toBeTrue()
        ->and(ModuleVersion::matches('2.0.0', '^1.2.0'))->toBeFalse()
        ->and(ModuleVersion::matches('1.3.0', '~1.2.0'))->toBeFalse()
        ->and(ModuleVersion::matches('0.2.5', '^0.2.3'))->toBeTrue()
        ->and(ModuleVersion::matches('0.3.0', '^0.2.3'))->toBeFalse()
        ->and(ModuleVersion::matches('1.0.1', '>1.0.0'))->toBeTrue();
});

it('resolves the registered service without executing module code', function () {
    expect(app(ModuleDependencyResolver::class))->toBeInstanceOf(ModuleDependencyResolver::class)
        ->and(app(ModuleDependencyResolver::class)->resolve())->toBe([]);
});
