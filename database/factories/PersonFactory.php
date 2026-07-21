<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        static $number = 1;

        $companies = [
            'TechCorp',
            'DevSolutions',
            'CloudWorks',
            'Blue Ridge Systems',
            'Nexus Federal',
            'Summit Analytics',
        ];

        return [
            'user_id' => null,
            'person_code' => 'TEST-' . str_pad((string) $number++, 7, '0', STR_PAD_LEFT),
            'first_name' => fake()->firstName(),
            'alternate_first_name' => fake()->optional(0.15)->firstName(),
            'preferred_name' => fake()->optional(0.25)->firstName(),
            'last_name' => fake()->lastName(),
            'alternate_last_name' => fake()->optional(0.10)->lastName(),
            'company_name' => fake()->randomElement($companies),
            'email' => fake()->unique()->safeEmail(),
            'employment_status' => fake()->randomElement([
                'active',
                'inactive',
                'leave',
            ]),
            'notes' => fake()->optional()->sentence(),
            'resume_path' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'employment_status' => 'active',
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
