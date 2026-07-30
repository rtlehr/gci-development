<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationLog;
use App\Models\User;
use App\Services\CurrentUserContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ImpersonationController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with([
                'person:id,user_id,first_name,preferred_name,last_name,person_code',
                'roles:id,name,label',
            ])
            ->orderBy('name')
            ->orderBy('email')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $this->displayName($user),
                'email' => $user->email,
                'person_code' => $user->person?->person_code,
                'roles' => $user->roles->pluck('label')->filter()->values(),
                'role_names' => $user->roles->pluck('name')->values(),
            ]);

        $logs = ImpersonationLog::query()
            ->with([
                'impersonator:id,name,email',
                'impersonatedUser:id,name,email',
                'endedBy:id,name,email',
            ])
            ->latest('started_at')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Impersonation/Index', [
            'users' => $users,
            'logs' => $logs,
            'activeSession' => session()->has('impersonator_user_id'),
        ]);
    }

    public function start(
        Request $request,
        User $user,
        CurrentUserContext $currentUser,
    ): RedirectResponse {
        abort_if(session()->has('impersonator_user_id'), 409, 'Nested impersonation is not allowed.');

        /** @var User $actor */
        $actor = $request->user();

        abort_if($actor->is($user), 422, 'You cannot impersonate yourself.');

        $actor->loadMissing('roles:id,name');
        $user->loadMissing('roles:id,name');

        $actorRoleNames = $actor->roles->pluck('name');
        $targetRoleNames = $user->roles->pluck('name');

        $isOwner = $actorRoleNames->contains('owner');
        $isDeveloper = $actorRoleNames->contains('developer');

        abort_unless($isOwner || $isDeveloper, 403, 'Only Owners and Developers may impersonate users.');

        if ($isDeveloper) {
            abort_if(
                $targetRoleNames->intersect(['owner', 'admin', 'developer'])->isNotEmpty(),
                403,
                'Developers cannot impersonate privileged administrative accounts.',
            );
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $identifier = (string) Str::uuid();

        $log = ImpersonationLog::query()->create([
            'impersonator_user_id' => $actor->id,
            'impersonated_user_id' => $user->id,
            'session_identifier' => $identifier,
            'reason' => $validated['reason'] ?? null,
            'started_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        session([
            'impersonator_user_id' => $actor->id,
            'impersonation_log_id' => $log->id,
            'impersonation_session_identifier' => $identifier,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $currentUser->forget();

        return redirect()
            ->route('portal.dashboard')
            ->with('success', 'Impersonation started.');
    }

    public function stop(
        Request $request,
        CurrentUserContext $currentUser,
    ): RedirectResponse {
        $impersonatorId = session('impersonator_user_id');
        $logId = session('impersonation_log_id');
        $identifier = session('impersonation_session_identifier');

        abort_unless($impersonatorId && $logId && $identifier, 409, 'No impersonation session is active.');

        $impersonator = User::query()->findOrFail($impersonatorId);

        ImpersonationLog::query()
            ->whereKey($logId)
            ->where('session_identifier', $identifier)
            ->whereNull('ended_at')
            ->update([
                'ended_by_user_id' => $impersonator->id,
                'ended_at' => now(),
                'termination_reason' => 'returned_by_impersonator',
                'updated_at' => now(),
            ]);

        session()->forget([
            'impersonator_user_id',
            'impersonation_log_id',
            'impersonation_session_identifier',
        ]);

        Auth::login($impersonator);
        $request->session()->regenerate();
        $currentUser->forget();

        return redirect()
            ->route('admin.impersonation.index')
            ->with('success', 'Returned to your account.');
    }

    private function displayName(User $user): string
    {
        $person = $user->person;

        if ($person) {
            $first = $person->preferred_name ?: $person->first_name;
            $name = trim(($first ?: '').' '.($person->last_name ?: ''));

            if ($name !== '') {
                return $name;
            }
        }

        return $user->name ?: ($user->email ?: "User {$user->id}");
    }
}
