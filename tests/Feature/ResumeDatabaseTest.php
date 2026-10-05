<?php

use App\Models\Candidate;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Resume;
use App\Models\ResumeFormat;
use App\Models\User;
use App\Models\UserEventLog;
use App\Models\Workflow;
use App\Services\PermissionService;
use App\Services\Resumes\DocxResumeParser;
use App\Services\Resumes\ResumeFields;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    Storage::fake('local');
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->user->permissions()->sync(Permission::whereIn('name', ['access_portal', 'view_resumes', 'manage_resumes', 'manage_resume_formats', 'view_admin', 'create_candidates', 'portal_view_positions', 'portal_view_all_positions'])->pluck('id'));
    $this->actingAs($this->user);
});
function resumeUploadFixture(): UploadedFile
{
    return new UploadedFile(base_path('tests/Fixtures/Resumes/gci-standard.docx'), 'resume.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
}
function reviewedResumeData(int $personId): array
{
    return ['person_id' => $personId, 'name' => 'Alex Morgan', 'location' => 'Winchester, VA', 'labor_category' => 'Engineer', 'requirement_id' => 'REQ-101', 'tickets' => 'Security+', 'other_information' => 'Cloud systems', 'entries' => ['education' => [['degree' => 'BS', 'major' => 'Computer Science', 'institution' => 'Example University']], 'certifications' => [['certification' => 'Security+']], 'languages' => [['language' => 'Spanish', 'reading_score' => '3', 'writing_score' => '2']], 'technologies' => [['technology' => 'Java'], ['technology' => 'SQL']]]];
}
function savedResumeForTest(int $personId): Resume
{
    return Resume::create(['person_id' => $personId, 'resume_format_id' => ResumeFormat::firstOrFail()->id, 'uploaded_by' => auth()->id(), 'format_snapshot' => ['name' => 'GCI Standard Resume', 'version' => 1], 'status' => 'saved', 'name' => 'Alex Morgan', 'original_filename' => 'resume.docx', 'file_path' => 'resumes/example.docx', 'search_text' => 'Alex Morgan Java Security+ Spanish']);
}
it('parses the supplied table layout including language dates and combined requirement id', function () {
    $result = app(DocxResumeParser::class)->parse(base_path('tests/Fixtures/Resumes/gci-standard.docx'), ResumeFields::defaults());
    expect($result['data']['name'])->toBe('Alex Morgan')
        ->and($result['data']['labor_category'])->toBe('Software Engineer')
        ->and($result['data']['requirement_id'])->toBe('REQ-101')
        ->and($result['entries']['education'][0]['degree'])->toBe('BS')
        ->and($result['entries']['languages'][0]['evaluation_date'])->toBe('07/25')
        ->and($result['entries']['technologies'])->toHaveCount(3);
});
it('rejects invalid archives and XML entity declarations', function () {
    $path = tempnam(sys_get_temp_dir(), 'resume-test');
    try {
        file_put_contents($path, 'not a zip');
        expect(fn () => app(DocxResumeParser::class)->parse($path, ResumeFields::defaults()))->toThrow(ValidationException::class);
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<!DOCTYPE x [<!ENTITY y SYSTEM "file:///etc/passwd">]><x/>');
        $zip->close();
        expect(fn () => app(DocxResumeParser::class)->parse($path, ResumeFields::defaults()))->toThrow(ValidationException::class);
    } finally {
        unlink($path);
    }
});
it('imports as a private draft and only indexes after review', function () {
    $person = Person::factory()->create();
    $this->post('/portal/resumes/upload', ['resume_format_id' => ResumeFormat::first()->id, 'file' => resumeUploadFixture()])->assertRedirect();
    $resume = Resume::firstOrFail();
    expect($resume->status)->toBe('draft');
    Storage::disk('local')->assertExists($resume->file_path);
    $this->get('/portal/resumes?q=Java')->assertInertia(fn (Assert $page) => $page->component('Portal/Resumes/Index')->has('resumes.data', 0));
    $this->put('/portal/resumes/'.$resume->id, reviewedResumeData($person->id))->assertRedirect('/portal/resumes/'.$resume->id);
    $this->get('/portal/resumes?q=Java%20Spanish&certifications=Security%2B')->assertInertia(fn (Assert $page) => $page->has('resumes.data', 1));
    $this->get('/portal/resumes?q=Java%20COBOL')->assertInertia(fn (Assert $page) => $page->has('resumes.data', 0));
});
it('requires resume permissions and keeps other users drafts private', function () {
    $this->post('/portal/resumes/upload', ['resume_format_id' => ResumeFormat::first()->id, 'file' => resumeUploadFixture()])->assertRedirect();
    $resume = Resume::firstOrFail();
    $other = User::factory()->create();
    $other->permissions()->sync(Permission::whereIn('name', ['access_portal', 'view_resumes', 'manage_resumes'])->pluck('id'));
    $this->app->forgetScopedInstances();
    $this->actingAs($other)->get('/portal/resumes/'.$resume->id.'/edit')->assertForbidden();
    $this->get('/portal/resumes/'.$resume->id.'/download')->assertForbidden();
    $this->app->forgetScopedInstances();
    $this->actingAs(User::factory()->create())->get('/portal/resumes')->assertForbidden();
});
it('rejects inactive formats and missing person associations', function () {
    $format = ResumeFormat::first();
    $format->update(['is_active' => false]);
    $this->post('/portal/resumes/upload', ['resume_format_id' => $format->id, 'file' => resumeUploadFixture()])->assertSessionHasErrors('resume_format_id');
    $format->update(['is_active' => true]);
    $this->post('/portal/resumes/upload', ['resume_format_id' => $format->id, 'file' => resumeUploadFixture()])->assertRedirect();
    $this->put('/portal/resumes/'.Resume::first()->id, ['person_id' => '', 'name' => 'Alex', 'entries' => array_fill_keys(array_keys(ResumeFields::REPEATING), [])])->assertSessionHasErrors('person_id');
});
it('creates versioned formats without changing previous imports', function () {
    $format = ResumeFormat::first();
    $mappings = ResumeFields::defaults();
    $mappings['technologies'] = 'Skills|Technologies';
    $this->put('/admin/resume-formats/'.$format->id, ['name' => $format->name, 'description' => 'Second format', 'is_active' => true, 'mappings' => $mappings])->assertRedirect();
    expect($format->fresh()->mappings['technologies'])->toBe('Technologies')
        ->and(ResumeFormat::where('version', 2)->first()->mappings['technologies'])->toBe('Skills|Technologies');
});
it('adds multiple candidates to an open position with an active assignment and rejects duplicates and closed positions', function () {
    $person = Person::factory()->create();
    $resume = savedResumeForTest($person->id);
    $position = Position::factory()->create(['status' => 'Open']);
    $workflow = Workflow::create(['name' => 'Resume workflow', 'code' => 'resume-workflow', 'is_active' => true, 'is_primary' => true]);
    $url = '/portal/resumes/'.$resume->id.'/candidate';
    $this->post($url, ['position_id' => $position->id, 'workflow_id' => $workflow->id])->assertRedirect();
    $this->assertDatabaseHas('candidates', ['person_id' => $person->id, 'position_id' => $position->id, 'workflow_id' => $workflow->id, 'status' => 'submitted']);
    $this->post($url, ['position_id' => $position->id])->assertSessionHasErrors('person_id');
    expect(Candidate::where('position_id', $position->id)->count())->toBe(1);
    PositionAssignment::factory()->active()->create(['position_id' => $position->id]);
    $another = savedResumeForTest(Person::factory()->create()->id);
    $this->get('/portal/resumes?position_id='.$position->id)->assertInertia(fn (Assert $page) => $page->where('position.accepting_candidates', true));
    $this->post('/portal/resumes/'.$another->id.'/candidate', ['position_id' => $position->id, 'workflow_id' => $workflow->id])->assertRedirect()->assertSessionHasNoErrors();
    expect(Candidate::where('position_id', $position->id)->count())->toBe(2);
    $this->assertDatabaseHas('candidates', ['person_id' => $another->person_id, 'position_id' => $position->id]);
    $position->update(['status' => 'Closed']);
    $third = savedResumeForTest(Person::factory()->create()->id);
    $this->post('/portal/resumes/'.$third->id.'/candidate', ['position_id' => $position->id])->assertSessionHasErrors('position_id');
    expect(Candidate::where('position_id', $position->id)->count())->toBe(2);
});
it('rejects candidate assignments to positions outside the users scope', function () {
    $resume = savedResumeForTest(Person::factory()->create()->id);
    $position = Position::factory()->create();
    $this->user->permissions()->detach(Permission::where('name', 'portal_view_all_positions')->value('id'));
    app(PermissionService::class)->clearUserPermissionCache($this->user->id);
    $this->app->forgetScopedInstances();
    $this->post('/portal/resumes/'.$resume->id.'/candidate', ['position_id' => $position->id])->assertForbidden();
    $this->assertDatabaseCount('candidates', 0);
});
it('deletes the original file and related structured entries', function () {
    $this->post('/portal/resumes/upload', ['resume_format_id' => ResumeFormat::first()->id, 'file' => resumeUploadFixture()]);
    $resume = Resume::firstOrFail();
    $path = $resume->file_path;
    $this->delete('/portal/resumes/'.$resume->id)->assertRedirect('/portal/resumes');
    Storage::disk('local')->assertMissing($path);
    $this->assertDatabaseCount('resume_entries', 0);
});
it('records resume upload save and download events', function () {
    config(['user-event-log.enabled' => true]);
    $this->post('/portal/resumes/upload', ['resume_format_id' => ResumeFormat::first()->id, 'file' => resumeUploadFixture()])->assertRedirect();
    $resume = Resume::firstOrFail();
    $this->put('/portal/resumes/'.$resume->id, reviewedResumeData(Person::factory()->create()->id))->assertRedirect();
    $this->get('/portal/resumes/'.$resume->id.'/download')->assertOk();
    expect(UserEventLog::where('module', 'resumes')->whereIn('action', ['upload', 'save', 'download'])->count())->toBe(3);
});
it('requires an active workflow before adding a candidate', function () {
    $resume = savedResumeForTest(Person::factory()->create()->id);
    $position = Position::factory()->create(['status' => 'Open']);
    $this->post('/portal/resumes/'.$resume->id.'/candidate', ['position_id' => $position->id])->assertSessionHasErrors('workflow_id');
    $this->assertDatabaseCount('candidates', 0);
});
it('rejects ambiguous format labels and unsupported document layouts', function () {
    $mappings = ResumeFields::defaults();
    $mappings['technologies'] = 'Name';
    $this->post('/admin/resume-formats', ['name' => 'Ambiguous', 'description' => '', 'is_active' => true, 'mappings' => $mappings])->assertSessionHasErrors('mappings.technologies');
    expect(fn () => app(DocxResumeParser::class)->parse(base_path('tests/Fixtures/Resumes/gci-standard.docx'), ['name' => 'Nonexistent label']))->toThrow(ValidationException::class);
});
it('supports custom column orders while preserving standard searchable fields', function () {
    $orders = ResumeFields::REPEATING;
    $orders['education'] = ['date', 'degree', 'institution', 'major', 'city', 'state'];
    $input = array_map(fn ($columns) => implode(', ', $columns), $orders);
    $this->post('/admin/resume-formats', ['name' => 'Different Column Order', 'is_active' => true, 'mappings' => ResumeFields::defaults(), 'column_orders' => $input])->assertRedirect();
    $format = ResumeFormat::where('name', 'Different Column Order')->firstOrFail();
    expect($format->column_orders['education'])->toBe($orders['education']);
    $result = app(DocxResumeParser::class)->parse(base_path('tests/Fixtures/Resumes/gci-standard.docx'), $format->mappings, $format->column_orders);
    expect($result['entries']['education'][0]['institution'])->toBe('Computer Science')
        ->and($result['entries']['education'][0]['major'])->toBe('Example University');
    $input['education'] = 'date,degree,degree';
    $this->post('/admin/resume-formats', ['name' => 'Invalid Column Order', 'is_active' => true, 'mappings' => ResumeFields::defaults(), 'column_orders' => $input])->assertSessionHasErrors('column_orders.education');
});
it('excludes closed positions and removes a closed selection from search context', function () {
    $open = Position::factory()->create(['status' => 'Open']);
    $inProcess = Position::factory()->create(['status' => 'In Process']);
    $closed = Position::factory()->create(['status' => 'Closed']);
    $this->get('/portal/resumes')->assertInertia(fn (Assert $page) => $page->has('positions', 2)->where('positions', fn ($positions) => collect($positions)->pluck('id')->sort()->values()->all() === collect([$open->id, $inProcess->id])->sort()->values()->all()));
    $this->get('/portal/resumes?position_id='.$closed->id)->assertInertia(fn (Assert $page) => $page->where('position', null)->missing('filters.position_id'));
});
it('adds to the selected open position even when the previous search was for a closed position', function () {
    $resume = savedResumeForTest(Person::factory()->create()->id);
    $closed = Position::factory()->create(['status' => 'Closed']);
    $open = Position::factory()->create(['status' => 'Open']);
    Workflow::create(['name' => 'Switch position workflow', 'code' => 'switch-position', 'is_active' => true, 'is_primary' => true]);
    $this->from('/portal/resumes?position_id='.$closed->id)->post('/portal/resumes/'.$resume->id.'/candidate', ['position_id' => $open->id])->assertRedirect();
    $this->assertDatabaseHas('candidates', ['person_id' => $resume->person_id, 'position_id' => $open->id]);
    $this->assertDatabaseMissing('candidates', ['person_id' => $resume->person_id, 'position_id' => $closed->id]);
});

it('ranks partial skill matches and allows turning skill matching off', function () {
    $position = Position::factory()->create(['status' => 'Open']);
    $position->customSkills()->create(['name' => 'Java', 'requirement_type' => 'required', 'is_active' => true]);
    $position->customSkills()->create(['name' => 'SQL', 'requirement_type' => 'desired', 'is_active' => true]);
    $best = savedResumeForTest(Person::factory()->create()->id);
    $best->update(['search_text' => 'Java SQL']);
    $partial = savedResumeForTest(Person::factory()->create()->id);
    $partial->update(['search_text' => 'Java']);
    $none = savedResumeForTest(Person::factory()->create()->id);
    $none->update(['search_text' => 'COBOL']);
    $this->get('/portal/resumes?position_id='.$position->id.'&use_position_skills=1')->assertInertia(fn (Assert $page) => $page->has('resumes.data', 2)->where('resumes.data.0.id', $best->id)->where('resumes.data.0.matched_skill_count', 2)->where('resumes.data.1.id', $partial->id));
    $this->get('/portal/resumes?position_id='.$position->id.'&use_position_skills=0')->assertInertia(fn (Assert $page) => $page->has('resumes.data', 3));
    $this->get('/portal/resumes?position_id='.$position->id.'&use_position_skills=1&q=SQL')->assertInertia(fn (Assert $page) => $page->has('resumes.data', 1)->where('resumes.data.0.id', $best->id));
});
it('falls back to normal search when a position has no skills', function () {
    $position = Position::factory()->create(['status' => 'Open', 'job_title_id' => null]);
    savedResumeForTest(Person::factory()->create()->id);
    $this->get('/portal/resumes?position_id='.$position->id.'&use_position_skills=1')->assertInertia(fn (Assert $page) => $page->has('positionSkills', 0)->has('resumes.data', 1));
});
