<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Person;
use App\Models\Alert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
                    'role_id' => 1,
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
                    'role_id' => 2,
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
                    'role_id' => 3,
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
                    'role_id' => 4,
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
                    'role_id' => 5,
                ],
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

                $exists = DB::table('role_user')
                    ->where('user_id', $user->id)
                    ->where('role_id', $seedUser['role_id'])
                    ->exists();

                if (! $exists) {
                    DB::table('role_user')->insert([
                        'user_id' => $user->id,
                        'role_id' => $seedUser['role_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // ✅ CREATE MULTIPLE ALERTS FOR COTR + PMO
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

                        // Prevent duplicates on reseed
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