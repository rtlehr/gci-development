<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\Position;
use App\Models\PositionAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionAssignmentFactory extends Factory
{
    protected $model = PositionAssignment::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-12 months', '-7 days');
        $isEnded = fake()->boolean(30);

        return [
            'position_id' => Position::factory(),
            'person_id' => Person::factory(),
            'start_date' => $startDate,
            'end_date' => $isEnded
                ? fake()->dateTimeBetween($startDate, 'now')
                : null,
            'assignment_status' => $isEnded ? 'ended' : 'active',
            'assignment_type' => fake()->randomElement(['primary', 'temporary', 'backfill']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'end_date' => null,
            'assignment_status' => 'active',
        ]);
    }

    public function ended(): static
    {
        return $this->state(function () {
            $startDate = fake()->dateTimeBetween('-12 months', '-2 months');

            return [
                'start_date' => $startDate,
                'end_date' => fake()->dateTimeBetween($startDate, '-1 day'),
                'assignment_status' => 'ended',
            ];
        });
    }
}