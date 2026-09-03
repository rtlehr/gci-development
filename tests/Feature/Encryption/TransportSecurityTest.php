<?php

use App\Http\Middleware\EnforceSecureTransport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('leaves local http requests alone when transport enforcement is disabled', function () {
    config()->set('security.enforce_https', false);

    $request = Request::create('http://localhost/portal', 'GET');
    $response = app(EnforceSecureTransport::class)->handle(
        $request,
        fn () => new Response('ok', 200),
    );

    expect($response->getStatusCode())->toBe(200);
});

it('redirects safe http requests to https when transport enforcement is enabled', function () {
    config()->set('security.enforce_https', true);

    $request = Request::create('http://irad.example.test/portal?tab=people', 'GET');
    $response = app(EnforceSecureTransport::class)->handle(
        $request,
        fn () => new Response('should not run', 200),
    );

    expect($response->getStatusCode())->toBe(301)
        ->and($response->headers->get('Location'))
        ->toBe('https://irad.example.test/portal?tab=people');
});

it('rejects unsafe http requests rather than accepting sensitive data over plaintext transport', function () {
    config()->set('security.enforce_https', true);

    $request = Request::create('http://irad.example.test/portal/people', 'POST');

    expect(fn () => app(EnforceSecureTransport::class)->handle(
        $request,
        fn () => new Response('should not run', 200),
    ))->toThrow(HttpException::class);
});

it('adds hsts to secure responses when enabled', function () {
    config()->set('security.enforce_https', true);
    config()->set('security.hsts.enabled', true);
    config()->set('security.hsts.max_age', 31536000);
    config()->set('security.hsts.include_subdomains', true);
    config()->set('security.hsts.preload', false);

    $request = Request::create('https://irad.example.test/portal', 'GET');
    $response = app(EnforceSecureTransport::class)->handle(
        $request,
        fn () => new Response('ok', 200),
    );

    expect($response->headers->get('Strict-Transport-Security'))
        ->toBe('max-age=31536000; includeSubDomains');
});


it('recognizes gateway forwarded https only from a trusted proxy', function () {
    Request::setTrustedProxies(
        ['127.0.0.1'],
        Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO,
    );

    try {
        $request = Request::create(
            'http://127.0.0.1/portal',
            'GET',
            server: [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_HOST' => 'insite.example.test',
                'HTTP_X_FORWARDED_PORT' => '443',
            ],
        );

        expect($request->isSecure())->toBeTrue()
            ->and($request->getSchemeAndHttpHost())->toBe('https://insite.example.test');
    } finally {
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);
    }
});

it('ignores forwarded https from an untrusted client', function () {
    Request::setTrustedProxies(
        ['127.0.0.1'],
        Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO,
    );

    try {
        $request = Request::create(
            'http://127.0.0.1/portal',
            'GET',
            server: [
                'REMOTE_ADDR' => '192.0.2.25',
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_HOST' => 'attacker.example.test',
            ],
        );

        expect($request->isSecure())->toBeFalse()
            ->and($request->getHost())->toBe('127.0.0.1');
    } finally {
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);
    }
});
