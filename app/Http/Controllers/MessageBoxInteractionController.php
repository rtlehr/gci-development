<?php

namespace App\Http\Controllers;

use App\Models\MessageBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageBoxInteractionController extends Controller
{
    public function seen(Request $request, MessageBox $messageBox)
    {
        DB::table('message_box_user_states')->updateOrInsert(
            ['message_box_id'=>$messageBox->id,'user_id'=>$request->user()->id],
            ['seen_at'=>now(),'updated_at'=>now(),'created_at'=>now()]
        );
        return response()->noContent();
    }

    public function dismiss(Request $request, MessageBox $messageBox)
    {
        abort_unless($messageBox->dismissible, 422);
        DB::table('message_box_user_states')->updateOrInsert(
            ['message_box_id'=>$messageBox->id,'user_id'=>$request->user()->id],
            ['seen_at'=>now(),'dismissed_at'=>now(),'updated_at'=>now(),'created_at'=>now()]
        );
        return response()->noContent();
    }

    public function submit(Request $request, MessageBox $messageBox)
    {
        $allowed = collect($messageBox->form_fields ?: [])->pluck('name')->filter()->all();
        $data = $request->validate(['data'=>'required|array']);
        $clean = collect($data['data'])->only($allowed)->all();
        DB::table('message_box_submissions')->insert(['message_box_id'=>$messageBox->id,'user_id'=>$request->user()?->id,'data'=>json_encode($clean),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success', 'Your response was submitted.');
    }
}
