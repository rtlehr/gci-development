<?php

use Symfony\Component\Process\Process;

/**
 * Exercise a fresh Artisan process, not only late activation in a booted test app.
 * These checks are read-only and do not create or clear any Laravel caches.
 */
function moduleStartupRouteList(string $enabled): array
{
    $process = new Process(
        [PHP_BINARY, 'artisan', 'route:list', '--json'],
        base_path(),
        ['APP_ENV' => 'testing', 'INSITE_ENABLED_MODULES' => $enabled],
    );
    $process->setTimeout(45);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());

    $routes = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
    expect($routes)->toBeArray();

    return $routes;
}

it('does not expose the demo route during fresh startup when no modules are selected', function () {
    if (app()->routesAreCached()) {
        $this->markTestSkipped('Fresh startup route test requires routes to be uncached.');
    }

    $names = array_column(moduleStartupRouteList(''), 'name');
    expect($names)->not->toContain('insite.modules.demo.health');
});

it('registers the demo route during a fresh startup when selected by deployment configuration', function () {
    if (app()->routesAreCached()) {
        $this->markTestSkipped('Fresh startup route test requires routes to be uncached.');
    }

    $names = array_column(moduleStartupRouteList('demo'), 'name');
    expect($names)->toContain('insite.modules.demo.health');
});
