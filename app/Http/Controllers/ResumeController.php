<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Person;
use App\Models\Position;
use App\Models\Resume;
use App\Models\ResumeFormat;
use App\Models\Workflow;
use App\Services\CurrentUserContext;
use App\Services\PortalWorkforceAccessService;
use App\Services\Resumes\DocxResumeParser;
use App\Services\Resumes\ResumeFields;
use App\Services\UserEventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ResumeController extends Controller
{
    public function __construct(private CurrentUserContext $context, private PortalWorkforceAccessService $access) {}

    public function uploadForPerson(Request $request, Person $person, DocxResumeParser $parser)
    {
        return $this->upload($request, $parser);
    }

    private function ensurePersonResume(Person $person, Resume $resume): void
    {
        abort_unless($resume->person_id === $person->id, 404);
    }

    public function editForPerson(Request $request, Person $person, Resume $resume)
    {
        $this->ensurePersonResume($person, $resume);

        return $this->edit($request, $resume);
    }

    public function showForPerson(Person $person, Resume $resume)
    {
        $this->ensurePersonResume($person, $resume);

        return $this->show($resume);
    }

    public function updateForPerson(Request $request, Person $person, Resume $resume)
    {
        $this->ensurePersonResume($person, $resume);
        $request->merge(['person_id' => $person->id]);

        return $this->update($request, $resume);
    }

    public function destroyForPerson(Request $request, Person $person, Resume $resume)
    {
        $this->ensurePersonResume($person, $resume);

        return $this->destroy($request, $resume);
    }

    private function personContext(): ?Person
    {
        $person = request()->route('person');

        return $person instanceof Person ? $person : null;
    }

    public function index(Request $request)
    {
        $filters = $request->validate(array_merge(
            ['use_position_skills' => ['nullable', 'boolean'], 'q' => ['nullable', 'string', 'max:200'], 'person_id' => ['nullable', 'integer', 'exists:people,id'], 'position_id' => ['nullable', 'integer', 'exists:positions,id']],
            array_fill_keys(array_merge(ResumeFields::SCALARS, array_keys(ResumeFields::REPEATING)), ['nullable', 'string', 'max:200'])
        ));
        $position = ! empty($filters['position_id']) ? Position::findOrFail($filters['position_id']) : null;
        if ($position) {
            $this->access->authorizePositionView($position);
            if (strtolower(trim($position->status)) === 'closed') {
                $position = null;
                unset($filters['position_id']);
            }
        }
        $query = Resume::with('person:id,first_name,last_name')->where('status', 'saved');
        if (! empty($filters['person_id'])) {
            $query->where('person_id', $filters['person_id']);
        }
        // Each space-separated keyword must match. Filters combine with AND.
        foreach (preg_split('/\s+/', trim($filters['q'] ?? '')) as $term) {
            if ($term !== '') {
                $query->where('search_text', 'like', '%'.$this->escapeLike($term).'%');
            }
        }
        foreach (ResumeFields::SCALARS as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, 'like', '%'.$this->escapeLike($filters[$field]).'%');
            }
        }
        foreach (ResumeFields::REPEATING as $section => $_) {
            if (! empty($filters[$section])) {
                $query->whereHas('entries', fn ($q) => $q->where('section', $section)->where('search_text', 'like', '%'.$this->escapeLike($filters[$section]).'%'));
            }
        }
        $query->select(['id', 'person_id', 'name', 'location', 'labor_category', 'created_at', 'original_filename']);
        $positionSkills = $position ? $position->allSkills()->pluck('name')->map(fn ($name) => trim($name))->filter()->unique()->values() : collect();
        if ($position && $request->boolean('use_position_skills') && $positionSkills->isNotEmpty()) {
            $score = $positionSkills->map(fn () => 'CASE WHEN LOWER(search_text) LIKE ? THEN 1 ELSE 0 END')->implode(' + ');
            $bindings = $positionSkills->map(fn ($name) => '%'.$this->escapeLike(mb_strtolower($name)).'%')->all();
            $query->selectRaw('('.$score.') AS matched_skill_count', $bindings)
                ->whereRaw('('.$score.') > 0', $bindings)->orderByDesc('matched_skill_count');
        }
        $resumes = $query->latest()->paginate(20)->withQueryString();
        $assigned = $position ? Candidate::where('position_id', $position->id)->pluck('person_id') : [];

        return Inertia::render('Portal/Resumes/Index', [
            'positionSkills' => $positionSkills, 'resumes' => $resumes, 'filters' => $filters, 'position' => $position ? array_merge($position->only(['id', 'position_code', 'job_title', 'status']), ['accepting_candidates' => $this->acceptingCandidates($position)]) : null,
            'positions' => $this->access->scopePositions(Position::query())->whereRaw('LOWER(TRIM(status)) <> ?', ['closed'])->orderBy('position_code')->get(['id', 'position_code', 'job_title']),
            'assignedPersonIds' => $assigned,
            'workflows' => Workflow::where('is_active', true)->orderByDesc('is_primary')->get(['id', 'name', 'is_primary']),
            'drafts' => Resume::where('status', 'draft')->where('uploaded_by', $this->context->user()->id)->latest()->get(['id', 'original_filename', 'created_at']),
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Portal/Resumes/Upload', ['formats' => ResumeFormat::where('is_active', true)->orderBy('name')->get(['id', 'name', 'version', 'description']), 'personId' => $request->integer('person_id') ?: null]);
    }

    public function upload(Request $request, DocxResumeParser $parser)
    {
        $data = $request->validate(['resume_format_id' => ['required', 'integer', Rule::exists('resume_formats', 'id')->where('is_active', true)], 'file' => ['required', 'file', 'extensions:docx', 'max:20480']]);
        $format = ResumeFormat::findOrFail($data['resume_format_id']);
        $result = $parser->parse($request->file('file')->getRealPath(), $format->mappings, $format->column_orders ?? []);
        $path = $request->file('file')->store('resumes', 'local');
        abort_unless($path, 500, 'The resume could not be stored.');
        try {
            $resume = DB::transaction(function () use ($format, $result, $path, $request) {
                $resume = Resume::create(array_merge($result['data'], ['resume_format_id' => $format->id, 'format_snapshot' => $format->only(['name', 'version', 'mappings', 'column_orders']), 'uploaded_by' => $this->context->user()->id, 'person_id' => $this->personContext()?->id, 'original_filename' => basename($request->file('file')->getClientOriginalName()), 'file_path' => $path, 'warnings' => $result['warnings']]));
                $this->saveEntries($resume, $result['entries']);

                return $resume;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        $this->log($resume, 'upload', 'Uploaded a resume for review.');

        if ($person = $this->personContext()) {
            return redirect()->route('portal.people.resumes.edit', ['person' => $person->id, 'resume' => $resume->id]);
        }

        return redirect()->route('portal.resumes.edit', ['resume' => $resume->id, 'person_id' => $request->integer('person_id') ?: null]);
    }

    private function authorizeDraft(Resume $resume): void
    {
        if ($resume->status === 'draft') {
            abort_unless($resume->uploaded_by === $this->context->user()->id, 403);
        }
    }

    public function edit(Request $request, Resume $resume)
    {
        $this->authorizeDraft($resume);

        return Inertia::render('Portal/Resumes/Edit', ['resume' => $resume->load('entries'), 'columns' => ResumeFields::REPEATING,
            'personContext' => $this->personContext()?->only(['id', 'first_name', 'last_name']),
            'people' => Person::when($this->personContext(), fn ($query, $person) => $query->whereKey($person->id))->orderBy('last_name')->get(['id', 'person_code', 'first_name', 'last_name']), 'personId' => $request->integer('person_id') ?: null]);
    }

    public function show(Resume $resume)
    {
        abort_unless($resume->status === 'saved', 404);

        return Inertia::render('Portal/Resumes/Show', ['resume' => $resume->load(['entries', 'person']), 'columns' => ResumeFields::REPEATING, 'personContext' => $this->personContext()?->only(['id', 'first_name', 'last_name'])]);
    }

    public function update(Request $request, Resume $resume)
    {
        $this->authorizeDraft($resume);
        $rules = ['person_id' => ['required', 'integer', 'exists:people,id'], 'name' => ['required', 'string', 'max:1000'], 'entries' => ['required', 'array']];
        foreach (ResumeFields::SCALARS as $field) {
            if ($field !== 'name') {
                $rules[$field] = ['nullable', 'string', 'max:10000'];
            }
        }
        foreach (ResumeFields::REPEATING as $section => $columns) {
            $rules['entries.'.$section] = ['present', 'array', 'max:200'];
            $rules['entries.'.$section.'.*'] = ['array:'.implode(',', $columns)];
            foreach ($columns as $column) {
                $rules['entries.'.$section.'.*.'.$column] = ['nullable', 'string', 'max:2000'];
            }
        }
        $data = $request->validate($rules);
        DB::transaction(function () use ($resume, $data) {
            $resume->update(array_merge(collect($data)->except('entries')->all(), ['status' => 'saved', 'warnings' => []]));
            $this->saveEntries($resume, $data['entries']);
            $resume->update(['search_text' => implode(' ', array_map(fn ($v) => $v ?? '', $resume->only(ResumeFields::SCALARS))).' '.$resume->entries()->pluck('search_text')->implode(' ')]);
        });
        $this->log($resume, 'save', 'Saved reviewed resume data.');

        if ($person = $this->personContext()) {
            return redirect()->route('portal.people.edit', ['id' => $person->id, 'section' => 'resumes'])->with('success', 'Resume saved and available for candidate searches.');
        }

        return redirect()->route('portal.resumes.show', $resume)->with('success', 'Resume saved and available for candidate searches.');
    }

    private function saveEntries(Resume $resume, array $entries): void
    {
        $resume->entries()->delete();
        foreach (ResumeFields::REPEATING as $section => $columns) {
            foreach ($entries[$section] ?? [] as $i => $entry) {
                $entry = array_intersect_key($entry, array_flip($columns));
                if (trim(implode(' ', $entry)) === '') {
                    continue;
                }
                $resume->entries()->create(['section' => $section, 'sort_order' => $i, 'data' => $entry, 'search_text' => implode(' ', $entry)]);
            }
        }
    }

    public function download(Resume $resume)
    {
        $this->authorizeDraft($resume);
        abort_unless(Storage::disk('local')->exists($resume->file_path), 404);
        $this->log($resume, 'download', 'Downloaded the original resume document.');

        return Storage::disk('local')->download($resume->file_path, $resume->original_filename, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function destroy(Request $request, Resume $resume)
    {
        $this->authorizeDraft($resume);
        $this->log($resume, 'delete', 'Deleted a resume.');
        $path = $resume->file_path;
        $resume->delete();
        Storage::disk('local')->delete($path);

        if ($person = $this->personContext()) {
            return redirect()->route('portal.people.edit', ['id' => $person->id, 'section' => 'resumes'])->with('success', 'Resume deleted.');
        }

        return redirect()->route('portal.resumes.index')->with('success', 'Resume deleted.');
    }

    public function addCandidate(Request $request, Resume $resume)
    {
        abort_unless($resume->status === 'saved' && $resume->person_id, 404);
        $data = $request->validate(['position_id' => ['required', 'integer', 'exists:positions,id'], 'workflow_id' => ['nullable', 'integer', 'exists:workflows,id']]);

        return DB::transaction(function () use ($request, $resume, $data) {
            $position = Position::whereKey($data['position_id'])->lockForUpdate()->firstOrFail();
            $this->access->authorizePositionView($position);
            if (! $this->acceptingCandidates($position)) {
                throw ValidationException::withMessages(['position_id' => 'This position is closed and cannot accept new candidates. Select an open position and click Search.']);
            }
            // Lock the position to serialize duplicate checks for concurrent resume assignments.
            $request->merge(['person_id' => $resume->person_id]);

            return app(PositionCandidateController::class)->store($request, $position->id);
        });
    }

    private function acceptingCandidates(Position $position): bool
    {
        return ! in_array(strtolower(trim($position->status)), ['closed', 'cancelled', 'canceled']);
    }

    private function log(Resume $resume, string $action, string $description): void
    {
        app(UserEventLogger::class)->record(eventType: $action, module: 'resumes', action: $action, subject: $resume, description: $description, subjectLabel: $resume->name ?: $resume->original_filename, metadata: ['person_id' => $resume->person_id], routeName: 'portal.resumes.show', routeParameters: ['resume' => $resume->id]);
    }

    private function escapeLike(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }
}
