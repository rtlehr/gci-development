<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\UserResolver;
use Illuminate\Http\RedirectResponse;

class AlertController extends Controller
{
    public function markRead(Alert $alert, UserResolver $userResolver): RedirectResponse
    {
        $currentUserId = $userResolver->resolveUserId();

        abort_unless((int) $alert->user_id === (int) $currentUserId, 403);

        $alert->update([
            'read_at' => now(),
        ]);

        return back();
    }

    public function markAllRead(UserResolver $userResolver): RedirectResponse
    {
        $currentUserId = $userResolver->resolveUserId();

        Alert::where('user_id', $currentUserId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return back();
    }
}