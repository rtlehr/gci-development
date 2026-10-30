<?php

use App\Core\Modules\ModuleCatalog;
use Illuminate\Support\Facades\File;

// These tests exercise Laravel filesystem helpers and the service container.
// Unit tests do not boot the application by default in this project.
uses(\Tests\TestCase::class);

beforeEach(function () {
    $this->moduleCatalogTestDir = storage_path('framework/testing/module-catalog-'.uniqid());
    File::ensureDirectoryExists($this->moduleCatalogTestDir);
});

afterEach(function () {
    File::deleteDirectory($this->moduleCatalogTestDir);
});

it('returns no modules for an empty or missing directory', function () {
    expect((new ModuleCatalog($this->moduleCatalogTestDir))->all())->toBe([]);
    expect((new ModuleCatalog($this->moduleCatalogTestDir.'/missing'))->all())->toBe([]);
});

it('discovers deterministic manifest metadata without executing code', function () {
    foreach (['travel' => 'Travel', 'finance' => 'Finance'] as $id => $name) {
        File::ensureDirectoryExists($this->moduleCatalogTestDir.'/'.$id);
        File::put($this->moduleCatalogTestDir.'/'.$id.'/module.json', json_encode([
            'id' => $id,
            'name' => $name,
            'version' => '0.1.0',
        ], JSON_THROW_ON_ERROR));
    }

    $catalog = new ModuleCatalog($this->moduleCatalogTestDir);

    expect(array_column($catalog->all(), 'id'))->toBe(['finance', 'travel']);
    expect($catalog->find('travel')['name'])->toBe('Travel');
    expect($catalog->find('missing'))->toBeNull();
});

it('rejects invalid manifests rather than silently accepting them', function () {
    File::ensureDirectoryExists($this->moduleCatalogTestDir.'/travel');
    File::put($this->moduleCatalogTestDir.'/travel/module.json', '{invalid json');

    expect(fn () => (new ModuleCatalog($this->moduleCatalogTestDir))->all())
        ->toThrow(InvalidArgumentException::class);
});

it('rejects mismatched module directory names', function () {
    File::ensureDirectoryExists($this->moduleCatalogTestDir.'/travel');
    File::put($this->moduleCatalogTestDir.'/travel/module.json', json_encode([
        'id' => 'finance', 'name' => 'Finance', 'version' => '1.0.0',
    ], JSON_THROW_ON_ERROR));

    expect(fn () => (new ModuleCatalog($this->moduleCatalogTestDir))->all())
        ->toThrow(InvalidArgumentException::class);
});

it('registers the catalog in the Laravel container', function () {
    expect(app(ModuleCatalog::class))->toBeInstanceOf(ModuleCatalog::class);
});
