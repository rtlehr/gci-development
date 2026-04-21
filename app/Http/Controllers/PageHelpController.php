<?php

namespace App\Http\Controllers;

use App\Models\PageHelp;
use Illuminate\Http\JsonResponse;

class PageHelpController extends Controller
{
    public function show(string $helpKey): JsonResponse
    {
        $help = PageHelp::query()
            ->where('help_key', $helpKey)
            ->where('is_active', true)
            ->first();

        if (! $help) {
            return response()->json([
                'title' => 'Page Help',
                'content_html' => '<p>Help content has not been added for this page yet.</p>',
            ]);
        }

        return response()->json([
            'title' => $help->title,
            'content_html' => $help->content_html
                ?: '<p>Help content has not been added for this page yet.</p>',
        ]);
    }
}