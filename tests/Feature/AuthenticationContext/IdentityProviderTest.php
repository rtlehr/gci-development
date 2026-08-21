<?php

use App\Contracts\Identity\PersonCodeProvider;
use App\Services\Identity\AdfsPersonCodeProvider;
use App\Services\Identity\DevelopmentPersonCodeProvider;

it('resolves the configured development person code', function () {
    config()->set('identity.driver', 'development');
    config()->set('devuser.enabled', true);
    config()->set('devuser.person_code', 'DEV-PER-001');

    $provider = app(PersonCodeProvider::class);

    expect($provider)->toBeInstanceOf(DevelopmentPersonCodeProvider::class)
        ->and($provider->resolve())->toBe('DEV-PER-001');
});

it('prefers the development session override over the default person code', function () {
    config()->set('identity.driver', 'development');
    config()->set('devuser.enabled', true);
    config()->set('devuser.person_code', 'DEV-PER-001');
    session(['dev_person_code' => 'DEV-PER-002']);

    expect(app(PersonCodeProvider::class)->resolve())->toBe('DEV-PER-002');
});

it('resolves only the explicitly configured adfs person code source', function () {
    config()->set('identity.driver', 'adfs');
    config()->set('identity.drivers.adfs.person_code_source', 'HTTP_ADFS_PERSON_CODE');

    request()->server->set('HTTP_PERSON_CODE', 'WRONG-PERSON');
    request()->server->set('HTTP_ADFS_PERSON_CODE', 'ADFS-PER-001');

    $provider = app(PersonCodeProvider::class);

    expect($provider)->toBeInstanceOf(AdfsPersonCodeProvider::class)
        ->and($provider->resolve())->toBe('ADFS-PER-001');
});

it('does not fall back to alternate adfs server values', function () {
    config()->set('identity.driver', 'adfs');
    config()->set('identity.drivers.adfs.person_code_source', 'HTTP_PERSON_CODE');

    request()->server->remove('HTTP_PERSON_CODE');
    request()->server->set('HTTP_EMPLOYEEID', 'SHOULD-NOT-BE-USED');
    request()->server->set('AUTH_USER', 'DOMAIN\\someone');

    expect(app(PersonCodeProvider::class)->resolve())->toBeNull();
});

it('rejects an unknown identity driver', function () {
    config()->set('identity.driver', 'not-a-driver');

    expect(fn () => app(PersonCodeProvider::class))
        ->toThrow(InvalidArgumentException::class, 'Unsupported IRAD identity driver [not-a-driver].');
});
