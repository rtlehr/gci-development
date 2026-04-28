<?php

namespace App\Http\Middleware;

use App\Support\CurrentUser;
use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Person;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'auth' => [
                'user' => CurrentUser::user($request),
            ],

            'dev' => [
                'debug' => config('app.debug') === true,

                'isImpersonating' => session()->has('dev_person_code'),

                'testUsers' => fn () => config('app.debug') === true
                    ? Person::query()
                        ->whereNotNull('user_id')
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->get([
                            'id',
                            'person_code',
                            'first_name',
                            'last_name',
                            'user_id',
                        ])
                        ->map(fn ($person) => [
                            'id' => $person->id,
                            'person_code' => $person->person_code,
                            'name' => trim($person->first_name . ' ' . $person->last_name),
                            'user_id' => $person->user_id,
                        ])
                    : [],
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
