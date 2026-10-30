<?php

use App\Core\Modules\ModuleActivator;
use Illuminate\Support\Facades\Route;

it('reports installed modules and a valid disabled-by-default configuration', function () {
    config()->set('insite_modules.enabled', []);

    $this->artisan('insite:modules:status')
        ->expectsOutputToContain('demo')
        ->expectsOutputToContain('Module configuration valid.')
        ->assertSuccessful();
});

it('reports selected modules without activating them', function () {
    config()->set('insite_modules.enabled', ['demo']);

    $this->artisan('insite:modules:status')
        ->expectsOutputToContain('Module configuration valid. 1 selected module(s).')
        ->assertSuccessful();

    expect(Route::has('insite.modules.demo.health'))->toBeFalse();
});

it('returns failure for a missing configured module without activating code', function () {
    config()->set('insite_modules.enabled', ['not-installed']);

    $this->artisan('insite:modules:status')
        ->expectsOutputToContain('Module configuration invalid:')
        ->assertFailed();
});

it('denies a registered module route after module configuration is disabled', function () {
    config()->set('insite_modules.enabled', ['demo']);
    app(ModuleActivator::class)->activate();
    expect(Route::has('insite.modules.demo.health'))->toBeTrue();

    // Do NOT remove the route: this models a stale route cache.
    config()->set('insite_modules.enabled', []);
    $this->get('/_insite/modules/demo/health')->assertNotFound();
    expect(Route::has('insite.modules.demo.health'))->toBeTrue();
});

it('fails closed on a stale route when the enabled selection is invalid', function () {
    config()->set('insite_modules.enabled', ['demo']);
    app(ModuleActivator::class)->activate();

    config()->set('insite_modules.enabled', ['demo', 'not-installed']);
    $this->get('/_insite/modules/demo/health')->assertNotFound();
});
