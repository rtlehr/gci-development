<?php

namespace App\Http\Middleware;

use App\Models\Alert;
use App\Models\Person;
use App\Services\CurrentUserContext;
use App\Support\RoleAbbreviation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly CurrentUserContext $currentUser,
    ) {
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $user = $this->currentUser->user();

        $alertCount = 0;
        $recentAlerts = [];

        if ($user) {
            $unreadAlerts = Alert::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at');

            $alertCount = (clone $unreadAlerts)->count();
            $recentAlerts = $unreadAlerts
                ->latest()
                ->limit(5)
                ->get([
                    'id',
                    'title',
                    'message',
                    'type',
                    'priority',
                    'action_url',
                    'created_at',
                ]);
        }

        return [
            ...parent::share($request),

            'appLabels' => config('app_labels'),

            'headerAlerts' => [
                'count' => $alertCount,
                'recent' => $recentAlerts,
            ],

            'auth' => [
                'user' => $this->currentUser->payload(),
            ],

            'dev' => [
                'debug' => config('app.debug') === true,
                'isImpersonating' => session()->has('dev_person_code'),
                'testUsers' => fn () => config('app.debug') === true
                    ? Person::query()
                        ->whereNotNull('user_id')
                        ->with(['user.roles:id,name,label'])
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->get([
                            'id',
                            'person_code',
                            'first_name',
                            'last_name',
                            'user_id',
                        ])
                        ->map(function (Person $person): array {
                            $roleAbbreviations = RoleAbbreviation::forRoles(
                                $person->user?->roles ?? [],
                            );

                            return [
                                'id' => $person->id,
                                'person_code' => $person->person_code,
                                'name' => trim($person->first_name.' '.$person->last_name),
                                'user_id' => $person->user_id,
                                'role_abbreviations' => $roleAbbreviations,
                                'role_display' => implode(' | ', $roleAbbreviations),
                            ];
                        })
                    : [],
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
