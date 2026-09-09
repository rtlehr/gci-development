<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OwnerRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OwnerRecoveryController extends Controller
{
    public function show(Request $request, OwnerRecoveryService $recovery): Response|RedirectResponse
    {
        if ($recovery->hasValidSession($request)) {
            return redirect()->route('admin.users.index');
        }

        if (Auth::check()) {
            return redirect()->route('home');
        }

        return Inertia::render('auth/OwnerRecovery', [
            'enabled' => $recovery->enabled(),
            'message' => $this->messageForReason((string) $request->query('reason', 'unknown')),
            'sessionMinutes' => $recovery->sessionMinutes(),
        ]);
    }

    public function store(Request $request, OwnerRecoveryService $recovery): RedirectResponse
    {
        abort_unless($recovery->enabled(), 404);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $owner = $recovery->authenticate(
            (string) $validated['email'],
            (string) $validated['password'],
        );

        if (! $owner) {
            Log::warning('Owner recovery login failed.', [
                'identifier_hash' => hash('sha256', mb_strtolower(trim((string) $validated['email']))),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'The Owner recovery credentials are invalid.',
            ]);
        }

        Auth::login($owner);
        $request->session()->regenerate();
        $recovery->markSession($request);

        Log::warning('Owner recovery login succeeded.', [
            'owner_user_id' => $owner->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Owner recovery access started. Review and correct user access, then exit the recovery session.');
    }

    public function destroy(Request $request, OwnerRecoveryService $recovery): RedirectResponse
    {
        $ownerId = Auth::id();

        $recovery->clearSession($request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::notice('Owner recovery session ended.', [
            'owner_user_id' => $ownerId,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('owner-recovery.show', ['reason' => 'signed-out']);
    }

    private function messageForReason(string $reason): string
    {
        return match ($reason) {
            'missing' => 'Your network account could not be identified by the enterprise sign-in service.',
            'unlinked' => 'Your network identity exists, but it is not linked to an active Insite Portal user.',
            'identity' => 'Insite Portal could not validate the identity supplied by the enterprise sign-in service.',
            'signed-out' => 'The Owner recovery session has ended.',
            default => 'Your authenticated network account is not currently authorized to use Insite Portal.',
        };
    }
}
