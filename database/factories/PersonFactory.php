<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'person_code' => 'EMP-' . str_pad($number++, 3, '0', STR_PAD_LEFT),
            'first_name' => fake()->firstName(),
            'alternate_first_name' => fake()->optional(0.15)->firstName(),
            'preferred_name' => fake()->optional(0.25)->firstName(),
            'last_name' => fake()->lastName(),
            'alternate_last_name' => fake()->optional(0.10)->lastName(),
            'company_name' => fake()->randomElement($companies),
            'cell_phone' => fake()->numerify('###-###-####'),
            'email' => fake()->unique()->safeEmail(),
            'employment_status' => fake()->randomElement(['active', 'inactive', 'leave']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'employment_status' => 'active',
        ]);
    }
}