<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('development user switch routes are unavailable outside local environment', function () {
    config()->set('devuser.enabled', true);
    config()->set('app.debug', true);

    expect(app()->environment())->not->toBe('local');

    $this->post('/dev/switch-user', [
        'person_code' => 'ANY-CODE',
    ])->assertNotFound();

    $this->post('/dev/clear-user')
        ->assertNotFound();
});

test('inertia does not expose development switcher users outside local environment', function () {
    config()->set('devuser.enabled', true);
    config()->set('app.debug', true);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('dev.available', false)
            ->where('dev.isImpersonating', false)
            ->has('dev.testUsers', 0));
});
