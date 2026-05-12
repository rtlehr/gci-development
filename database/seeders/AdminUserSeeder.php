<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Group;
use App\Models\Team;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $seedUsers = [
                [
                    'email' => 'owner@example.com',
                    'name' => 'Owner Admin',
                    'password' => 'password',
                    'person_code' => '1111111',
                    'first_name' => 'Owner',
                    'preferred_name' => 'Owner',
                    'last_name' => 'User',
                    'company_name' => 'Internal',
                    'notes' => 'Default seeded owner account.',
                    'role_name' => 'owner',
                    'groups' => [],
                    'teams' => [],
                ],
                [
                    'email' => 'admin@example.com',
                    'name' => 'Admin',
                    'password' => 'password',
                    'person_code' => '1234567',
                    'first_name' => 'Admin',
                    'preferred_name' => 'Admin User',
                    'last_name' => 'User',
                    'company_name' => 'Internal',
                    'notes' => 'Default seeded administrator account.',
                    'role_name' => 'admin',
                    'groups' => [],
                    'teams' => [],
                ],
                [
                    'email' => 'cotr@example.com',
                    'name' => 'COTR User',
                    'password' => 'password',
                    'person_code' => '2345678',
                    'first_name' => 'COTR',
                    'preferred_name' => 'COTR User',
                    'last_name' => 'User',
                    'company_name' => 'Internal',
                    'notes' => 'Default seeded COTR account.',
                    'role_name' => 'cotr',
                    'groups' => [],
                    'teams' => [],
                ],
                [
                    'email' => 'pmo@example.com',
                    'name' => 'PMO User',
                    'password' => 'password',
                    'person_code' => '3456789',
                    'first_name' => 'PMO',
                    'preferred_name' => 'PMO User',
                    'last_name' => 'User',
                    'company_name' => 'Internal',
                    'notes' => 'Default seeded PMO account.',
                    'role_name' => 'pmo',
                    'groups' => [],
                    'teams' => [],
                ],
                [
                    'email' => 'candidate@example.com',
                    'name' => 'Candidate User',
                    'password' => 'password',
                    'person_code' => '4567890',
                    'first_name' => 'Candidate',
                    'preferred_name' => 'Candidate User',
                    'last_name' => 'User',
                    'company_name' => 'Internal',
                    'notes' => 'Default seeded Candidate account.',
                    'role_name' => 'candidate',
                    'groups' => [],
                    'teams' => [],
                ],
                [
                    'email' => 'developer@example.com',
                    'name' => 'Developer User',
                    'password' => 'password',
                    'person_code' => '5678912',
                    'first_name' => 'Developer',
                    'preferred_name' => 'Developer User',
                    'last_name' => 'User',
                    'company_name' => 'Internal',
                    'notes' => 'Default seeded Developer account.',
                    'role_name' => 'admin',
                    'groups' => [],
                    'teams' => ['DEVELOPER'],
                ]
            ];

            foreach ($seedUsers as $seedUser) {

                $user = User::updateOrCreate(
                    ['email' => $seedUser['email']],
                    [
                        'name' => $seedUser['name'],
                        'password' => Hash::make($seedUser['password']),
                    ]
                );

                $person = Person::updateOrCreate(
                    ['person_code' => $seedUser['person_code']],
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

                $groupIds = Group::whereIn('group_name', $seedUser['groups'] ?? [])
                    ->pluck('id')
                    ->toArray();

                $teamIds = Team::whereIn('team_name', $seedUser['teams'] ?? [])
                    ->pluck('id')
                    ->toArray();

                $person->groups()->sync($groupIds);
                $person->teams()->sync($teamIds);

                DB::table('person_phone_numbers')->updateOrInsert(
                    [
                        'person_id' => $person->id,
                        'phone_type' => 'work',
                    ],
                    [
                        'phone_number' => $seedUser['phone_number'] ?? '555-555-5555',
                        'is_primary' => true,
                        'extension' => null,
                        'notes' => 'Default seeded phone number.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                DB::table('addresses')->updateOrInsert(
                    [
                        'person_id' => $person->id,
                        'address_type' => 'work',
                    ],
                    [
                        'line_1' => $seedUser['address_line_1'] ?? '123 Main Street',
                        'line_2' => null,
                        'city' => $seedUser['city'] ?? 'Winchester',
                        'state' => $seedUser['state'] ?? 'VA',
                        'postal_code' => $seedUser['postal_code'] ?? '22601',
                        'country' => 'USA',
                        'is_primary' => true,
                        'notes' => 'Default seeded address.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $role = Role::where('name', $seedUser['role_name'])->first();

                if ($role) {
                    $user->roles()->syncWithoutDetaching([$role->id]);
                }

                if (in_array($seedUser['email'], ['cotr@example.com', 'pmo@example.com'])) {

                    $alerts = [
                        [
                            'title' => 'New Assignment Available',
                            'message' => 'You have been assigned a new item that requires your attention.',
                            'type' => 'assignment',
                            'priority' => 'normal',
                        ],
                        [
                            'title' => 'Action Required',
                            'message' => 'Please review and take action on a pending request.',
                            'type' => 'workflow',
                            'priority' => 'high',
                        ],
                        [
                            'title' => 'Reminder',
                            'message' => 'You have outstanding items that have not been completed.',
                            'type' => 'reminder',
                            'priority' => 'low',
                        ],
                    ];

                    foreach ($alerts as $alertData) {
                        $exists = Alert::where('user_id', $user->id)
                            ->where('title', $alertData['title'])
                            ->exists();

                        if (! $exists) {
                            Alert::create([
                                'user_id' => $user->id,
                                'person_id' => $person->id,
                                'type' => $alertData['type'],
                                'priority' => $alertData['priority'],
                                'title' => $alertData['title'],
                                'message' => $alertData['message'],
                                'action_url' => '/',
                                'source_type' => 'seed',
                                'source_id' => null,
                                'metadata' => [
                                    'seeded' => true,
                                ],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        });
    }
}