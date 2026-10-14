<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\Storage\PortableFiles;
use App\Services\UserResolver;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketScreenshotController extends Controller
{
    public function portal(Ticket $ticket, UserResolver $users): StreamedResponse
    {
        abort_unless((int) $ticket->submitted_by_user_id === (int) $users->resolveUserId(), 404);
        return $this->serve($ticket);
    }

    public function admin(Ticket $ticket): StreamedResponse
    {
        return $this->serve($ticket);
    }

    private function serve(Ticket $ticket): StreamedResponse
    {
        abort_unless($ticket->screenshot_path, 404);
        [$disk, $path] = PortableFiles::resolve($ticket->screenshot_path, 'public');
        abort_unless(Storage::disk($disk)->exists($path), 404);
        return Storage::disk($disk)->response($path, null, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
