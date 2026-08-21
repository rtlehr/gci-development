<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Person;
use App\Services\CurrentUserContext;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function show(Attachment $attachment, CurrentUserContext $currentUser): StreamedResponse
    {
        abort_unless($this->canView($attachment, $currentUser), 403);
        abort_unless(
            $attachment->path && Storage::disk($attachment->disk)->exists($attachment->path),
            404,
        );

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name ?: $attachment->stored_name,
            array_filter([
                'Content-Type' => $attachment->mime_type ?: null,
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
            ]),
            'inline',
        );
    }

    private function canView(Attachment $attachment, CurrentUserContext $currentUser): bool
    {
        $user = $currentUser->user();

        if (! $user) {
            return false;
        }

        if ($attachment->attachable_type !== (new Person())->getMorphClass()) {
            return false;
        }

        $permissions = $currentUser->permissions();

        if (array_intersect($permissions, [
            'access_people',
            'read_people',
            'update_people',
            'portal_view_directory',
        ])) {
            return true;
        }

        return in_array('portal_view_personal_information', $permissions, true)
            && (int) $currentUser->person()?->id === (int) $attachment->attachable_id;
    }
}
