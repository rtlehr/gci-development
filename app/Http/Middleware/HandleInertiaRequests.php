<?php

namespace App\Http\Middleware;

use App\Models\Alert;
use App\Models\Person;
use App\Services\CurrentUserContext;
use App\Services\SiteSettingsService;
use App\Services\ContentPageNavigationService;
use App\Support\RoleAbbreviation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly CurrentUserContext $currentUser,
        private readonly SiteSettingsService $siteSettings,
        private readonly ContentPageNavigationService $contentPages,
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

        $devSwitcherAvailable = app()->environment('local')
            && config('devuser.enabled') === true;

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

            'siteSettings' => fn () => $this->siteSettings->all(),

            'contentNavigation' => fn () => $this->contentPages->forHeader($user !== null, $this->currentUser->permissions()),

            'headerAlerts' => [
                'count' => $alertCount,
                'recent' => $recentAlerts,
            ],

            'auth' => [
                'user' => $this->currentUser->payload(),
            ],

            'impersonation' => [
                'active' => session()->has('impersonator_user_id'),
                'impersonator_user_id' => session('impersonator_user_id'),
                'log_id' => session('impersonation_log_id'),
            ],

            'dev' => [
                'available' => $devSwitcherAvailable,
                'isImpersonating' => $devSwitcherAvailable
                    && session()->has('dev_person_code'),
                'testUsers' => fn () => $devSwitcherAvailable
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
