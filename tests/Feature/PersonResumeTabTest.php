<?php

use App\Models\Permission;
use App\Models\Person;
use App\Models\Resume;
use App\Models\ResumeFormat;
use App\Models\User;
use App\Services\PermissionService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->withoutVite();
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->user->permissions()->sync(Permission::whereIn('name', ['access_portal', 'update_people', 'view_resumes', 'manage_resumes'])->pluck('id'));
    $this->actingAs($this->user);
});
function personTabUpload(): UploadedFile
{
    return new UploadedFile(base_path('tests/Fixtures/Resumes/gci-standard.docx'), 'sample.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
}
function personTabSavedResume(Person $person): Resume
{
    return Resume::create(['person_id' => $person->id, 'resume_format_id' => ResumeFormat::firstOrFail()->id, 'uploaded_by' => auth()->id(), 'format_snapshot' => ['name' => 'GCI Standard Resume', 'version' => 1], 'status' => 'saved', 'name' => 'Alex Morgan', 'file_path' => 'resumes/sample.docx', 'original_filename' => 'sample.docx']);
}
it('loads only the current persons resumes and selects their resume tab', function () {
    $person = Person::factory()->create();
    $other = Person::factory()->create();
    $resume = personTabSavedResume($person);
    personTabSavedResume($other);
    $this->get('/portal/people/'.$person->id.'/edit?section=resumes')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Portal/People/Edit')->where('initialSection', 'resumes')->has('resumes', 1)->where('resumes.0.id', $resume->id)->has('resumeFormats', 1));
});
it('links an uploaded draft to the person and keeps the person through review and save', function () {
    $person = Person::factory()->create();
    $this->post('/portal/people/'.$person->id.'/resumes/upload', ['resume_format_id' => ResumeFormat::first()->id, 'file' => personTabUpload()])->assertRedirect();
    $resume = Resume::firstOrFail();
    expect($resume->person_id)->toBe($person->id);
    $this->get('/portal/people/'.$person->id.'/resumes/'.$resume->id.'/edit')->assertInertia(fn (Assert $page) => $page->component('Portal/Resumes/Edit')->where('personContext.id', $person->id)->has('people', 1));
    $this->put('/portal/people/'.$person->id.'/resumes/'.$resume->id, ['person_id' => Person::factory()->create()->id, 'name' => 'Alex Morgan', 'entries' => ['education' => [], 'certifications' => [], 'languages' => [], 'technologies' => [['technology' => 'Java']]]])->assertRedirect('/portal/people/'.$person->id.'/edit?section=resumes');
    expect($resume->fresh()->person_id)->toBe($person->id)->and($resume->fresh()->status)->toBe('saved');
    $this->get('/portal/people/'.$person->id.'/resumes/'.$resume->id)->assertInertia(fn (Assert $page) => $page->component('Portal/Resumes/Show')->where('personContext.id', $person->id));
});
it('deletes the selected persons resume file and returns to the resume tab', function () {
    $person = Person::factory()->create();
    $this->post('/portal/people/'.$person->id.'/resumes/upload', ['resume_format_id' => ResumeFormat::first()->id, 'file' => personTabUpload()]);
    $resume = Resume::firstOrFail();
    $path = $resume->file_path;
    $this->delete('/portal/people/'.$person->id.'/resumes/'.$resume->id)->assertRedirect('/portal/people/'.$person->id.'/edit?section=resumes');
    Storage::disk('local')->assertMissing($path);
    $this->assertDatabaseCount('resumes', 0);
});
it('rejects a resume belonging to another person in person routes', function () {
    $person = Person::factory()->create();
    $other = Person::factory()->create();
    $resume = personTabSavedResume($other);
    $url = '/portal/people/'.$person->id.'/resumes/'.$resume->id;
    $this->get($url)->assertNotFound();
    $this->get($url.'/edit')->assertNotFound();
    $this->delete($url)->assertNotFound();
    $this->assertDatabaseHas('resumes', ['id' => $resume->id, 'person_id' => $other->id]);
});
it('hides resume data without permission and blocks direct uploads', function () {
    $person = Person::factory()->create();
    personTabSavedResume($person);
    $this->user->permissions()->sync(Permission::whereIn('name', ['access_portal', 'update_people'])->pluck('id'));
    app(PermissionService::class)->clearUserPermissionCache($this->user->id);
    $this->app->forgetScopedInstances();
    $this->get('/portal/people/'.$person->id.'/edit?section=resumes')->assertInertia(fn (Assert $page) => $page->has('resumes', 0)->has('resumeFormats', 0)->where('initialSection', 'details'));
    $this->post('/portal/people/'.$person->id.'/resumes/upload', ['resume_format_id' => ResumeFormat::first()->id, 'file' => personTabUpload()])->assertForbidden();
});

it('shows multiple saved resumes on the view page without edit permission and hides other people and drafts', function () {
    $this->user->permissions()->sync(Permission::whereIn('name', ['access_portal', 'portal_view_directory', 'view_resumes'])->pluck('id'));
    app(PermissionService::class)->clearUserPermissionCache($this->user->id);
    $this->app->forgetScopedInstances();
    $person = Person::factory()->create();
    personTabSavedResume($person);
    personTabSavedResume($person);
    personTabSavedResume(Person::factory()->create());
    personTabSavedResume($person)->update(['status' => 'draft']);
    $this->get('/portal/people/'.$person->id.'?section=resumes')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Portal/People/Show')->where('initialSection', 'resumes')->has('resumes', 2));
    $this->user->permissions()->detach(Permission::where('name', 'view_resumes')->value('id'));
    app(PermissionService::class)->clearUserPermissionCache($this->user->id);
    $this->app->forgetScopedInstances();
    $this->get('/portal/people/'.$person->id.'?section=resumes')->assertInertia(fn (Assert $page) => $page->where('initialSection', 'details')->has('resumes', 0));
});
