<?php

namespace Database\Seeders\Qa;

use App\Models\Alert;
use App\Models\Candidate;
use App\Models\CandidateStepEvent;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\Group;
use App\Models\JobTitle;
use App\Models\JobTitleSkill;
use App\Models\JobTitleTask;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\Role;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class QaScenarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $organizations = $this->seedOrganizations();
            $teams = $this->seedTeams();
            $groups = $this->seedGroups();
            $jobTitles = $this->seedJobTitles();
            $users = $this->seedUsers($teams, $groups);
            $this->seedCustomFields($users['owner']);

            $positions = $this->seedPositions($organizations, $jobTitles, $users);
            $people = Person::query()->where('company_name', 'QA Test Organization')->get()->keyBy('person_code');

            $this->seedCandidateScenarios($positions, $people);
            $this->seedAssignments($positions, $people);
            $this->seedTickets($users);
            $this->seedAlerts($users);
        });

        $this->command?->newLine();
        $this->command?->info('QA scenario data seeded. QA users authenticate by person_code through the configured identity driver.');
        $this->command?->warn('Note: QA-POS-HOLD is not seeded because the current positions.status enum and staffing-state service do not provide a persistent On-Hold input state. Keep PORT On-Hold testing blocked until that product behavior is defined/fixed.');
    }

    /** @return array<string, Organization> */
    private function seedOrganizations(): array
    {
        $root = Organization::query()->create([
            'name' => 'QA Organization',
            'status' => 'active',
            'notes' => 'QA root organization.',
        ]);
        $root->rebuildHierarchyFields();

        $operations = Organization::query()->create([
            'name' => 'QA Operations',
            'parent_id' => $root->id,
            'status' => 'active',
            'notes' => 'QA operational organization.',
        ]);
        $operations->rebuildHierarchyFields();

        $program = Organization::query()->create([
            'name' => 'QA Program Office',
            'parent_id' => $root->id,
            'status' => 'active',
            'notes' => 'QA program organization.',
        ]);
        $program->rebuildHierarchyFields();

        return compact('root', 'operations', 'program');
    }

    /** @return array<string, Team> */
    private function seedTeams(): array
    {
        return [
            'developer' => Team::query()->create(['team_name' => 'DEVELOPER']),
            'alpha' => Team::query()->create(['team_name' => 'QA TEAM ALPHA']),
            'bravo' => Team::query()->create(['team_name' => 'QA TEAM BRAVO']),
        ];
    }

    /** @return array<string, Group> */
    private function seedGroups(): array
    {
        return [
            'one' => Group::query()->create(['group_name' => 'QA GROUP ONE']),
            'two' => Group::query()->create(['group_name' => 'QA GROUP TWO']),
        ];
    }

    /** @return array<string, JobTitle> */
    private function seedJobTitles(): array
    {
        $titles = [
            'engineer' => JobTitle::query()->create([
                'name' => 'QA Software Engineer',
                'description' => 'Primary QA job title used for workflow and requirement testing.',
                'is_active' => true,
                'sort_order' => 10,
            ]),
            'analyst' => JobTitle::query()->create([
                'name' => 'QA Business Analyst',
                'description' => 'Secondary QA job title.',
                'is_active' => true,
                'sort_order' => 20,
            ]),
            'manager' => JobTitle::query()->create([
                'name' => 'QA Project Manager',
                'description' => 'QA management title.',
                'is_active' => true,
                'sort_order' => 30,
            ]),
        ];

        JobTitleSkill::query()->create([
            'job_title_id' => $titles['engineer']->id,
            'name' => 'QA Required Skill',
            'description' => 'Required skill used by QA tests.',
            'requirement_type' => 'required',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        JobTitleSkill::query()->create([
            'job_title_id' => $titles['engineer']->id,
            'name' => 'QA Desired Skill',
            'description' => 'Desired skill used by QA tests.',
            'requirement_type' => 'desired',
            'sort_order' => 20,
            'is_active' => true,
        ]);
        JobTitleTask::query()->create([
            'job_title_id' => $titles['engineer']->id,
            'name' => 'QA Primary Task',
            'description' => 'Task used by QA tests.',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        return $titles;
    }

    /**
     * @param array<string, Team> $teams
     * @param array<string, Group> $groups
     * @return array<string, User>
     */
    private function seedUsers(array $teams, array $groups): array
    {
        $definitions = [
            'owner' => ['qa.owner@localhost', '1111111', 'Olivia', 'Owner', 'owner'],
            'admin' => ['qa.admin@localhost', '9000002', 'Adam', 'Admin', 'admin'],
            'developer' => ['qa.developer@localhost', '9000003', 'Dana', 'Developer', 'developer'],
            'cotr' => ['qa.cotr@localhost', '9000004', 'Casey', 'COTR', 'cotr'],
            'pmo' => ['qa.pmo@localhost', '9000005', 'Parker', 'PMO', 'pmo'],
            'pm1' => ['qa.pm1@localhost', '9000006', 'Morgan', 'Manager', 'project_manager'],
            'pm2' => ['qa.pm2@localhost', '9000007', 'Taylor', 'Manager', 'project_manager'],
            'candidate' => ['qa.candidate@localhost', '9000008', 'Cameron', 'Candidate', 'candidate'],
            'restricted' => ['qa.restricted@localhost', '9000009', 'Riley', 'Restricted', 'candidate'],
            'empty' => ['qa.empty@localhost', '9000010', 'Emery', 'Empty', 'project_manager'],
        ];

        $roles = Role::query()->get()->keyBy('name');
        $users = [];

        foreach ($definitions as $key => [$email, $personCode, $first, $last, $roleName]) {
            $user = User::query()->create([
                'name' => $first.' '.$last,
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
            ]);

            $person = Person::updateOrCreateByPersonCode($personCode, [
                'user_id' => $user->id,
                'first_name' => $first,
                'preferred_name' => $first,
                'last_name' => $last,
                'company_name' => 'QA Test Organization',
                'email' => $email,
                'employment_status' => $roleName === 'candidate' ? 'Candidate' : 'Active',
                'notes' => 'Deterministic QA account: '.$key.'.',
            ]);

            $role = $roles->get($roleName);
            $user->roles()->sync($role ? [$role->id] : []);

            if ($key === 'developer') {
                $person->teams()->sync([$teams['developer']->id]);
            } elseif ($key === 'pm1') {
                $person->teams()->sync([$teams['alpha']->id]);
                $person->groups()->sync([$groups['one']->id]);
            } elseif ($key === 'pm2') {
                $person->teams()->sync([$teams['bravo']->id]);
                $person->groups()->sync([$groups['two']->id]);
            }

            $users[$key] = $user;
        }

        // Person record intentionally not linked to a User for ADFS negative testing.
        Person::updateOrCreateByPersonCode('9000999', [
            'user_id' => null,
            'first_name' => 'Una',
            'preferred_name' => 'Una',
            'last_name' => 'Unlinked',
            'company_name' => 'QA Test Organization',
            'email' => 'qa.unlinked@example.test',
            'employment_status' => 'Active',
            'notes' => 'QA-PER-UNLINK: valid person with no linked application user.',
        ]);

        // Extra editable/candidate people with stable person codes.
        foreach ([
            ['QA20001', 'Edith', 'Editable', 'Active'],
            ['QA20002', 'Nora', 'Notes', 'Active'],
            ['QA20003', 'Avery', 'Applicant', 'Candidate'],
            ['QA20004', 'Finley', 'Filled', 'Active'],
            ['QA20005', 'Sydney', 'Selected', 'Candidate'],
            ['QA20006', 'Jordan', 'Other', 'Candidate'],
        ] as [$code, $first, $last, $status]) {
            Person::updateOrCreateByPersonCode($code, [
                'first_name' => $first,
                'preferred_name' => $first,
                'last_name' => $last,
                'company_name' => 'QA Test Organization',
                'email' => strtolower($first.'.'.$last).'@example.test',
                'employment_status' => $status,
                'notes' => 'Purpose-built QA person record '.$code.'.',
            ]);
        }

        return $users;
    }

    private function seedCustomFields(User $owner): void
    {
        $fields = [
            ['person', 'QA Text Field', 'qa_person_text', 'text', false, false, 10],
            ['person', 'QA Date Field', 'qa_person_date', 'date', false, false, 20],
            ['person', 'QA Radio Field', 'qa_person_radio', 'radio', false, false, 30],
            ['person', 'QA Sensitive Text', 'qa_person_sensitive', 'text', false, true, 40],
            ['position', 'QA Multiline Field', 'qa_position_multiline', 'textarea', false, false, 10],
            ['position', 'QA Checkbox Field', 'qa_position_checkbox', 'checkbox', false, false, 20],
            ['position', 'QA Required Field', 'qa_position_required', 'text', true, false, 30],
        ];

        foreach ($fields as [$entity, $name, $key, $type, $required, $sensitive, $order]) {
            $field = CustomField::query()->create([
                'entity_type' => $entity,
                'name' => $name,
                'key' => $key,
                'field_type' => $type,
                'description' => 'Purpose-built QA custom field.',
                'placeholder' => null,
                'is_required' => $required,
                'is_active' => true,
                'is_sensitive' => $sensitive,
                'is_list_column' => ! $sensitive && $type === 'text',
                'is_searchable' => ! $sensitive && $type === 'text',
                'is_filterable' => false,
                'sort_order' => $order,
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ]);

            if (in_array($type, ['radio', 'checkbox'], true)) {
                foreach ([['alpha', 'Alpha'], ['bravo', 'Bravo'], ['charlie', 'Charlie']] as $index => [$value, $label]) {
                    CustomFieldOption::query()->create([
                        'custom_field_id' => $field->id,
                        'value' => $value,
                        'label' => $label,
                        'sort_order' => ($index + 1) * 10,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    /**
     * @param array<string, Organization> $organizations
     * @param array<string, JobTitle> $jobTitles
     * @param array<string, User> $users
     * @return array<string, Position>
     */
    private function seedPositions(array $organizations, array $jobTitles, array $users): array
    {
        $base = [
            'status' => 'Open',
            'job_title' => $jobTitles['engineer']->name,
            'job_title_id' => $jobTitles['engineer']->id,
            'level' => 3,
            'team_name' => 'QA TEAM ALPHA',
            'labor_category' => 'QA Software Engineer - Experienced',
            'location' => 'Arlington, VA',
            'building' => 'QA Building A',
            'position_organization_id' => $organizations['operations']->id,
            'sponsoring_organization_id' => $organizations['program']->id,
            'funding_organization_id' => $organizations['root']->id,
            'customer_created_at' => now()->subDays(60)->toDateString(),
        ];

        $definitions = [
            'vacant' => ['QA-POS-VAC', $users['pm1']->id, 'Vacant dashboard scenario.'],
            'selected' => ['QA-POS-SEL', $users['pm1']->id, 'Selected candidate dashboard scenario.'],
            'filled' => ['QA-POS-FILL', $users['pm1']->id, 'Filled position dashboard scenario.'],
            'departing' => ['QA-POS-DEP', $users['pm2']->id, 'Departing assignment dashboard scenario.'],
            'pm1' => ['QA-POS-PM1', $users['pm1']->id, 'Project Manager One scope test.'],
            'other' => ['QA-POS-OTHER', $users['pm2']->id, 'Must be hidden from Project Manager One.'],
            'edit' => ['QA-POS-EDIT', $users['pm1']->id, 'Disposable position used by edit/custom-field tests.'],
        ];

        $positions = [];
        foreach ($definitions as $key => [$code, $pmId, $notes]) {
            $positions[$key] = Position::query()->create($base + [
                'position_code' => $code,
                'project_manager_user_id' => $pmId,
                'notes' => $notes,
            ]);
        }

        return $positions;
    }

    /**
     * @param array<string, Position> $positions
     * @param \Illuminate\Support\Collection<string, Person> $people
     */
    private function seedCandidateScenarios(array $positions, $people): void
    {
        $workflow = Workflow::query()->where('code', 'default_candidate_workflow')->firstOrFail();
        $steps = WorkflowStep::query()->where('workflow_id', $workflow->id)->get()->keyBy('code');
        $submitter = $people->get('QA10005'); // PMO

        $partial = Candidate::query()->create([
            'candidate_code' => 'QA-CAND-PART',
            'person_id' => $people->get('QA10008')->id,
            'position_id' => $positions['pm1']->id,
            'workflow_id' => $workflow->id,
            'status' => 'submitted',
            'candidate_fbr' => 1.10,
            'submitted_at' => now()->subDays(10),
            'submitted_by_person_id' => $submitter?->id,
            'scheduled_start_date' => null,
        ]);
        $this->event($partial, $steps['resume_review'], null, now()->subDays(8), $submitter, 'Resume review completed for partial QA workflow.');
        $this->event($partial, $steps['interview'], 'scheduled', null, $submitter, 'Interview scheduled for QA workflow.', now()->subDays(7), now()->addDays(2));

        $selected = Candidate::query()->create([
            'candidate_code' => 'QA-CAND-SEL',
            'person_id' => $people->get('QA20005')->id,
            'position_id' => $positions['selected']->id,
            'workflow_id' => $workflow->id,
            'status' => 'selected',
            'candidate_fbr' => 1.25,
            'submitted_at' => now()->subDays(20),
            'submitted_by_person_id' => $submitter?->id,
            'scheduled_start_date' => now()->addDays(21)->toDateString(),
        ]);
        $this->event($selected, $steps['resume_review'], null, now()->subDays(18), $submitter, 'Selected candidate resume reviewed.');
        $this->event($selected, $steps['interview'], 'completed', now()->subDays(14), $submitter, 'Selected candidate interview completed.', now()->subDays(16), now()->subDays(14));
        $this->event($selected, $steps['tech_screen'], 'completed', now()->subDays(10), $submitter, 'Selected candidate technical screen completed.', now()->subDays(12), now()->subDays(10));

        $filled = Candidate::query()->create([
            'candidate_code' => 'QA-CAND-FILL',
            'person_id' => $people->get('QA20004')->id,
            'position_id' => $positions['filled']->id,
            'workflow_id' => $workflow->id,
            'status' => 'assigned',
            'candidate_fbr' => 1.40,
            'submitted_at' => now()->subDays(45),
            'submitted_by_person_id' => $submitter?->id,
            'scheduled_start_date' => now()->subDays(7)->toDateString(),
        ]);

        foreach (['resume_review', 'interview', 'tech_screen', 'crossover', 'offer_sent', 'offer_signed', 'subcontract_signed'] as $index => $code) {
            $status = match ($code) {
                'interview', 'tech_screen' => 'completed',
                'crossover' => 'approved',
                default => null,
            };
            $this->event($filled, $steps[$code], $status, now()->subDays(40 - ($index * 4)), $submitter, 'Completed QA filled workflow step: '.$code.'.');
        }

        Candidate::query()->create([
            'candidate_code' => 'QA-CAND-OTHER',
            'person_id' => $people->get('QA20006')->id,
            'position_id' => $positions['other']->id,
            'workflow_id' => $workflow->id,
            'status' => 'submitted',
            'candidate_fbr' => 0.90,
            'submitted_at' => now()->subDays(5),
            'submitted_by_person_id' => $submitter?->id,
        ]);
    }

    private function event(
        Candidate $candidate,
        WorkflowStep $step,
        ?string $status,
        $completedAt,
        ?Person $performedBy,
        ?string $notes = null,
        $requestedAt = null,
        $scheduledAt = null,
    ): void {
        CandidateStepEvent::query()->create([
            'candidate_id' => $candidate->id,
            'workflow_step_id' => $step->id,
            'status_code' => $status,
            'requested_at' => $requestedAt,
            'scheduled_at' => $scheduledAt,
            'completed_at' => $completedAt,
            'performed_by_person_id' => $performedBy?->id,
            'notes' => $notes,
            'comments' => null,
            'metadata' => ['qa_seed' => true],
        ]);
    }

    /**
     * @param array<string, Position> $positions
     * @param \Illuminate\Support\Collection<string, Person> $people
     */
    private function seedAssignments(array $positions, $people): void
    {
        PositionAssignment::query()->create([
            'position_id' => $positions['filled']->id,
            'person_id' => $people->get('QA20004')->id,
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => null,
            'assignment_status' => 'active',
            'assignment_type' => 'staffing',
            'notes' => 'QA-ASG-FILL active assignment.',
        ]);

        PositionAssignment::query()->create([
            'position_id' => $positions['departing']->id,
            'person_id' => $people->get('QA20001')->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'assignment_status' => 'active',
            'assignment_type' => 'staffing',
            'notes' => 'QA departing assignment ending within 30 days.',
        ]);
    }

    /** @param array<string, User> $users */
    private function seedTickets(array $users): void
    {
        Ticket::query()->create([
            'ticket_number' => 'QA-TCK-001',
            'title' => 'QA own portal ticket',
            'submitted_by_user_id' => $users['candidate']->id,
            'assigned_to_user_id' => $users['developer']->id,
            'request_type' => 'bug',
            'importance' => 'asap',
            'category' => 'UI',
            'description' => 'Deterministic ticket used to verify portal ownership and ticket workflow.',
            'source_url' => '/portal',
            'status' => 'new',
        ]);

        Ticket::query()->create([
            'ticket_number' => 'QA-TCK-002',
            'title' => 'QA other user ticket',
            'submitted_by_user_id' => $users['pm1']->id,
            'assigned_to_user_id' => null,
            'request_type' => 'improvement',
            'importance' => 'nice_to_have',
            'category' => 'Workflow',
            'description' => 'Ticket that must not appear as the Candidate user own ticket.',
            'source_url' => '/portal',
            'status' => 'in_progress',
        ]);
    }

    /** @param array<string, User> $users */
    private function seedAlerts(array $users): void
    {
        foreach ([
            [$users['candidate'], 'QA Candidate Alert', 'Unread alert owned by QA Candidate.'],
            [$users['pm1'], 'QA Project Manager Alert', 'Unread alert owned by QA Project Manager One.'],
        ] as [$user, $title, $message]) {
            Alert::query()->create([
                'user_id' => $user->id,
                'person_id' => $user->person?->id,
                'type' => 'qa',
                'priority' => 'normal',
                'title' => $title,
                'message' => $message,
                'action_url' => '/portal',
                'source_type' => 'qa_seed',
                'source_id' => null,
                'metadata' => ['qa_seed' => true],
                'read_at' => null,
                'should_email' => false,
            ]);
        }
    }
}
