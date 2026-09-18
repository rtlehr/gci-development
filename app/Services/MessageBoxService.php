<?php

namespace App\Services;

use App\Models\MessageBox;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MessageBoxService
{
    public function forRequest(Request $request, ?User $user, array $permissions): Collection
    {
        if (! $user) return collect();

        $roleNames = $user->roles()->pluck('name')->all();
        $path = '/'.ltrim($request->path(), '/');
        if ($path === '/') $path = '/';

        $seenIds = $user->id
            ? \DB::table('message_box_user_states')->where('user_id', $user->id)->whereNotNull('seen_at')->pluck('message_box_id')->all()
            : [];

        return MessageBox::query()->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderByDesc('priority')->orderBy('id')
            ->get()
            ->filter(function (MessageBox $box) use ($path, $roleNames, $permissions, $seenIds) {
                $patterns = $box->page_patterns ?: ['*'];
                $pageMatch = collect($patterns)->contains(fn ($pattern) => $pattern === '*' || Str::is('/'.ltrim($pattern, '/'), $path));
                if (! $pageMatch) return false;
                if ($box->show_once && in_array($box->id, $seenIds, true)) return false;
                $values = $box->audience_values ?: [];
                return match ($box->audience) {
                    'roles' => count(array_intersect($values, $roleNames)) > 0,
                    'permissions' => count(array_intersect($values, $permissions)) > 0,
                    default => true,
                };
            })->values()->map(fn (MessageBox $box) => [
                'id'=>$box->id,'title'=>$box->title,'content_html'=>$box->content_html,'display_type'=>$box->display_type,
                'trigger_type'=>$box->trigger_type,'trigger_key'=>$box->trigger_key,'show_once'=>$box->show_once,
                'dismissible'=>$box->dismissible,'actions'=>$box->actions ?: [],'form_fields'=>$box->form_fields ?: [],
            ]);
    }
}
