<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Return the response after a successful registration.
     */
    public function toResponse($request)
    {
        return redirect()->to(config('fortify.home', '/dashboard'));
    }
}