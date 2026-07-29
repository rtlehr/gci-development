<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        $status = $request->string('status')->toString();

        $query = Alert::query()
            ->where('user_id', $userId)
            ->latest();

        if ($status === 'unread') {
            $query->whereNull('read_at');
        }

        if ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        return Inertia::render('Portal/Alerts/Index', [
            'alerts' => $query
                ->paginate(20)
                ->withQueryString(),

            'filters' => [
                'status' => $status,
            ],

            'counts' => [
                'all' => Alert::query()
                    ->where('user_id', $userId)
                    ->count(),

                'unread' => Alert::query()
                    ->where('user_id', $userId)
                    ->whereNull('read_at')
                    ->count(),

                'read' => Alert::query()
                    ->where('user_id', $userId)
                    ->whereNotNull('read_at')
                    ->count(),
            ],
        ]);
    }

    public function markRead(Request $request, Alert $alert): RedirectResponse
    {
        abort_unless($alert->user_id === $request->user()->id, 403);

        if ($alert->read_at === null) {
            $alert->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        Alert::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return back();
    }
}
