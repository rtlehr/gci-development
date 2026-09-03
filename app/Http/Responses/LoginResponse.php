<?php

namespace App\Http\Responses;

use App\Services\Auth\BootstrapOwnerService;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function __construct(
        private readonly BootstrapOwnerService $bootstrap,
    ) {
    }

    public function toResponse($request)
    {
        if ($this->bootstrap->hasValidBootstrapSession($request)) {
            return redirect()->route('setup.index');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
