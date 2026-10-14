<?php

use App\Support\AzureTestIdentity;
use Illuminate\Http\Request;

it('does not enable Azure test identities by default', function () {
    config()->set('azure_test_identity.enabled', false);
    config()->set('identity.driver', 'development');
    config()->set('devuser.enabled', true);

    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_X_MS_CLIENT_PRINCIPAL_ID' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
        'HTTP_X_MS_CLIENT_PRINCIPAL_IDP' => 'aad',
    ]);

    expect(AzureTestIdentity::authorized($request))->toBeFalse();
});

it('does not trust forged Azure headers outside a production Azure instance', function () {
    config()->set('azure_test_identity.enabled', true);
    config()->set('azure_test_identity.app_name', 'app-insite-test');
    config()->set('azure_test_identity.runtime_app_name', 'app-insite-test');
    config()->set('azure_test_identity.entra_object_id', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');
    config()->set('identity.driver', 'development');
    config()->set('devuser.enabled', true);

    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_X_MS_CLIENT_PRINCIPAL_ID' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
        'HTTP_X_MS_CLIENT_PRINCIPAL_IDP' => 'aad',
    ]);

    expect(app()->environment('testing'))->toBeTrue()
        ->and(AzureTestIdentity::authorized($request))->toBeFalse()
        ->and(AzureTestIdentity::switcherAvailable($request))->toBeFalse();
});
