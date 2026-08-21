<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSecureTransport
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enforce_https', false)) {
            return $next($request);
        }

        if (! $request->isSecure()) {
            if (in_array($request->method(), ['GET', 'HEAD'], true)) {
                $secureUrl = 'https://'.$request->getHttpHost().$request->getRequestUri();

                return redirect()->to($secureUrl, 301);
            }

            abort(400, 'HTTPS is required for this request.');
        }

        $response = $next($request);

        if (config('security.hsts.enabled', false)) {
            $value = 'max-age='.(int) config('security.hsts.max_age', 31536000);

            if (config('security.hsts.include_subdomains', true)) {
                $value .= '; includeSubDomains';
            }

            if (config('security.hsts.preload', false)) {
                $value .= '; preload';
            }

            $response->headers->set('Strict-Transport-Security', $value);
        }

        return $response;
    }
}
