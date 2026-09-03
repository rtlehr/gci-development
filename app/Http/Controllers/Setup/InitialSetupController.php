<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Services\Auth\BootstrapOwnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InitialSetupController extends Controller
{
    public function index(Request $request, BootstrapOwnerService $bootstrap): Response
    {
        abort_unless($bootstrap->hasValidBootstrapSession($request), 404);

        return Inertia::render('setup/InitialSetup', [
            'ownerPersonCode' => $bootstrap->ownerPersonCode(),
        ]);
    }

    public function complete(Request $request, BootstrapOwnerService $bootstrap): RedirectResponse
    {
        abort_unless($bootstrap->hasValidBootstrapSession($request), 404);

        $bootstrap->completeSetup();
        $bootstrap->clearSession($request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', 'Initial setup completed. Future authenticated access now requires the configured enterprise identity provider.');
    }
}
