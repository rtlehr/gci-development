<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Person;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the application's default admin user, person record,
     * and role assignment.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Create or update the default admin user
            $user = User::updateOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'System Admin',
                    'password' => Hash::make('password'),
                ]
            );

            // 2. Create or update the matching person record
            $person = Person::updateOrCreate(
                ['person_code' => '1234567'],
                [
                    'user_id' => $user->id,
                    'first_name' => 'System',
                    'preferred_name' => 'Admin',
                    'last_name' => 'User',
                    'company_name' => 'Internal',
                    'email' => 'admin@example.com',
                    'employment_status' => 'Active',
                    'notes' => 'Default seeded administrator account.',
                ]
            );

            // Make sure the person is linked to the correct user
            if ((int) $person->user_id !== (int) $user->id) {
                $person->user_id = $user->id;
                $person->save();
            }

            // 3. Assign admin role in role_user pivot table
            $exists = DB::table('role_user')
                ->where('user_id', $user->id)
                ->where('role_id', 1)
                ->exists();

            if (! $exists) {
                DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}