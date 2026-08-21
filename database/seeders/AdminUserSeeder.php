<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Address;
use App\Models\Group;
use App\Models\Person;
use App\Models\PersonPhoneNumber;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $seedUsers = [
                $this->user('owner@localhost', '1111111', 'Sherman', 'Sherman', 'Potter', 'owner', 'Default seeded owner account.'),
                $this->user('admin@localhost', '1234567', 'Benjamin', 'Hawkeye', 'Pierce', 'admin', 'Default seeded administrator account.'),
                $this->user('cotr@localhost', '2345678', 'Margaret', 'Margaret', 'Houlihan', 'cotr', 'Default seeded COTR account.'),
                $this->user('pmo@localhost', '3456789', 'John', 'Trapper John', 'McIntyre', 'pmo', 'Default seeded PMO account.'),
                $this->user('candidate@localhost', '4567890', 'Walter', 'Radar', "O'Reilly", 'candidate', 'Default seeded Candidate account.'),
                $this->user('developer@localhost', '5678912', 'Maxwell', 'Klinger', 'admin', 'Default seeded Developer account.', teams: ['DEVELOPER']),

                $this->user('project.manager1@localhost', '6789123', 'B. J.', 'B. J.', 'Hunnicutt', 'project_manager', 'Seeded project manager account.', groups: ['GROUPS ONE'], teams: ['TEAM ONE']),
                $this->user('project.manager2@localhost', '7891234', 'Charles', 'Charles', 'Winchester', 'project_manager', 'Seeded project manager account.', groups: ['GROUPS TWO'], teams: ['TEAM TWO']),
                $this->user('admin.henry@localhost', '8912345', 'Henry', 'Henry', 'Blake', 'admin', 'Additional seeded administrator account.', groups: ['GROUPS ONE']),
                $this->user('cotr.mulcahy@localhost', '9123456', 'Francis', 'Father Mulcahy', 'Mulcahy', 'cotr', 'Additional seeded COTR account.', groups: ['GROUPS THREE']),
                $this->user('pmo.frank@localhost', '9234567', 'Frank', 'Frank', 'Burns', 'pmo', 'Additional seeded PMO account.', teams: ['TEAM THREE']),
                $this->user('candidate.kellye@localhost', '9345678', 'Kellye', 'Kellye', 'Nakahara', 'candidate', 'Additional seeded Candidate account.'),
            ];

            $rolesByName = Role::query()->get()->keyBy('name');
            $ownerRole = $rolesByName->get('owner');

            if ($ownerRole) {
                DB::table('role_user')->where('role_id', $ownerRole->id)->delete();
            }

            foreach ($seedUsers as $seedUser) {
                $user = User::updateOrCreate(
                    ['email' => $seedUser['email']],
                    [
                        'name' => $seedUser['name'],
                        'password' => Hash::make($seedUser['password']),
                    ]
                );

                $person = Person::updateOrCreateByPersonCode(
                    $seedUser['person_code'],
                    [
                        'user_id' => $user->id,
                        'first_name' => $seedUser['first_name'],
                        'preferred_name' => $seedUser['preferred_name'],
                        'last_name' => $seedUser['last_name'],
                        'company_name' => $seedUser['company_name'],
                        'email' => $seedUser['email'],
                        'employment_status' => 'Active',
                        'notes' => $seedUser['notes'],
                    ]
                );

                $person->groups()->sync(
                    Group::query()->whereIn('group_name', $seedUser['groups'])->pluck('id')
                );

                $person->teams()->sync(
                    Team::query()->whereIn('team_name', $seedUser['teams'])->pluck('id')
                );

                PersonPhoneNumber::updateOrCreate(
                    ['person_id' => $person->id, 'phone_type' => 'work'],
                    [
                        'phone_number' => $seedUser['phone_number'],
                        'is_primary' => true,
                        'extension' => null,
                        'notes' => 'Default seeded phone number.',
                    ]
                );

                Address::updateOrCreate(
                    ['person_id' => $person->id, 'address_type' => 'work'],
                    [
                        'line_1' => $seedUser['address_line_1'],
                        'line_2' => null,
                        'city' => $seedUser['city'],
                        'state' => $seedUser['state'],
                        'postal_code' => $seedUser['postal_code'],
                        'country' => 'USA',
                        'is_primary' => true,
                        'notes' => 'Default seeded address.',
                    ]
                );

                $role = $rolesByName->get($seedUser['role_name']);
                $user->roles()->sync($role ? [$role->id] : []);

                if (in_array($seedUser['role_name'], ['cotr', 'pmo', 'project_manager'], true)) {
                    $this->seedAlerts($user, $person);
                }
            }
        });
    }

    private function user(
        string $email,
        string $personCode,
        string $firstName,
        string $preferredName,
        string $lastName,
        string $roleName,
        string $notes = 'Seeded development account.',
        array $groups = [],
        array $teams = [],
    ): array {
        return [
            'email' => $email,
            'name' => trim($preferredName.' '.$lastName),
            'password' => 'password',
            'person_code' => $personCode,
            'first_name' => $firstName,
            'preferred_name' => $preferredName,
            'last_name' => $lastName,
            'company_name' => 'Internal',
            'notes' => $notes,
            'role_name' => $roleName,
            'groups' => $groups,
            'teams' => $teams,
            'phone_number' => '555-555-5555',
            'address_line_1' => '123 Main Street',
            'city' => 'Winchester',
            'state' => 'VA',
            'postal_code' => '22601',
        ];
    }

    private function seedAlerts(User $user, Person $person): void
    {
        $alerts = [
            ['title' => 'New Assignment Available', 'message' => 'You have been assigned a new item that requires your attention.', 'type' => 'assignment', 'priority' => 'normal'],
            ['title' => 'Action Required', 'message' => 'Please review and take action on a pending request.', 'type' => 'workflow', 'priority' => 'high'],
            ['title' => 'Reminder', 'message' => 'You have outstanding items that have not been completed.', 'type' => 'reminder', 'priority' => 'low'],
        ];

        foreach ($alerts as $alertData) {
            Alert::updateOrCreate(
                ['user_id' => $user->id, 'title' => $alertData['title'], 'source_type' => 'seed'],
                [
                    'person_id' => $person->id,
                    'type' => $alertData['type'],
                    'priority' => $alertData['priority'],
                    'message' => $alertData['message'],
                    'action_url' => '/',
                    'source_id' => null,
                    'metadata' => ['seeded' => true],
                ]
            );
        }
    }
}
