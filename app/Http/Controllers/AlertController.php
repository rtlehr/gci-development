<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function markRead(Alert $alert): RedirectResponse
    {
        abort_unless($alert->user_id === auth()->id(), 403);

        $alert->update([
            'read_at' => now(),
        ]);

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        Alert::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return back();
    }
}