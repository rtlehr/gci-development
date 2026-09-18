<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageBox;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MessageBoxController extends Controller
{
    public function index(Request $request)
    {
        $boxes = MessageBox::query()->when($request->string('search')->toString(), fn($q,$s)=>$q->where(fn($x)=>$x->where('name','like',"%$s%")->orWhere('title','like',"%$s%")))->orderByDesc('priority')->orderByDesc('id')->paginate(25)->withQueryString();
        return Inertia::render('Admin/MessageBoxes/Index', ['boxes'=>$boxes,'filters'=>['search'=>$request->string('search')->toString()]]);
    }

    public function create() { return Inertia::render('Admin/MessageBoxes/Create', $this->options()); }
    public function edit(MessageBox $messageBox) { return Inertia::render('Admin/MessageBoxes/Edit', [...$this->options(),'messageBox'=>$messageBox]); }

    public function store(Request $request)
    {
        $data = $this->validated($request); $data['created_by']=$request->user()->id; $data['updated_by']=$request->user()->id;
        MessageBox::create($data); return redirect()->route('admin.message-boxes.index')->with('success','Message box created.');
    }
    public function update(Request $request, MessageBox $messageBox)
    {
        $data=$this->validated($request); $data['updated_by']=$request->user()->id; $messageBox->update($data);
        return redirect()->route('admin.message-boxes.index')->with('success','Message box updated.');
    }
    public function destroy(MessageBox $messageBox) { $messageBox->delete(); return back()->with('success','Message box deleted.'); }

    private function validated(Request $request): array
    {
        $data=$request->validate([
            'name'=>'required|string|max:150','title'=>'required|string|max:200','content_html'=>'nullable|string','display_type'=>'required|in:modal,top_banner,bottom_banner',
            'audience'=>'required|in:everyone,roles,permissions','audience_values'=>'nullable|array','audience_values.*'=>'string|max:150','page_patterns'=>'required|array|min:1','page_patterns.*'=>'required|string|max:255',
            'trigger_type'=>'required|in:automatic,manual','trigger_key'=>'nullable|string|max:100','show_once'=>'boolean','dismissible'=>'boolean','priority'=>'required|integer|min:0|max:10000',
            'actions'=>'nullable|array|max:3','actions.*.label'=>'required_with:actions|string|max:80','actions.*.url'=>'required_with:actions|string|max:500',
            'form_fields'=>'nullable|array','form_fields.*.name'=>'required_with:form_fields|string|max:80','form_fields.*.label'=>'required_with:form_fields|string|max:120','form_fields.*.type'=>'required_with:form_fields|in:text,email,textarea,checkbox','form_fields.*.required'=>'boolean',
            'is_active'=>'boolean','starts_at'=>'nullable|date','ends_at'=>'nullable|date|after:starts_at',
        ]);
        if ($data['audience']==='everyone') $data['audience_values']=null;
        if ($data['trigger_type']==='automatic') $data['trigger_key']=null;
        return $data;
    }
    private function options(): array
    {
        return ['roles'=>Role::query()->orderBy('label')->get(['name','label']),'permissions'=>Permission::query()->orderBy('group_name')->orderBy('label')->get(['name','label','group_name'])];
    }
}
