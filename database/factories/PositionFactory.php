<?php

namespace Database\Factories;

use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        static $number = 1;

        $statuses = ['open', 'filled', 'on_hold', 'closed'];
        $teams = ['China Team', 'Europe Team', 'US Team', 'APAC Team', 'Platform Team'];
        $orgs = [
            'ABC/ABC/ABC/ABC',
            'XYZ/XYZ/XYZ',
            'DEF/DEF',
            'LMN/LMN/LMN',
        ];
        $laborCategories = [
            'Software Engineer',
            'Backend Engineer',
            'Frontend Engineer',
            'DevOps Engineer',
            'Cyber Analyst',
            'Data Analyst',
            'Systems Engineer',
            'Project Manager',
        ];
        $jobTitles = [
            'Frontend Developer',
            'Backend Developer',
            'Cloud Engineer',
            'Systems Administrator',
            'Data Engineer',
            'Business Analyst',
            'QA Engineer',
            'Technical Lead',
        ];

        $status = fake()->randomElement($statuses);
        $customerCreatedAt = fake()->dateTimeBetween('-18 months', 'now');

        return [
            'position_code' => 'ZN-' . str_pad($number++, 3, '0', STR_PAD_LEFT),
            'status' => $status,
            'labor_category' => fake()->randomElement($laborCategories),
            'job_title' => fake()->randomElement($jobTitles),
            'level' => fake()->numberBetween(1, 5),
            'project_team_name' => fake()->randomElement($teams),
            'organization_name' => fake()->randomElement($orgs),
            'customer_lead_name' => fake()->name(),
            'customer_created_at' => $customerCreatedAt,
            'closed_at' => $status === 'closed'
                ? fake()->dateTimeBetween($customerCreatedAt, 'now')
                : null,
            'closed_reason' => $status === 'closed'
                ? fake()->sentence()
                : null,
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'status' => 'open',
            'closed_at' => null,
            'closed_reason' => null,
        ]);
    }

    public function filled(): static
    {
        return $this->state(fn () => [
            'status' => 'filled',
            'closed_at' => null,
            'closed_reason' => null,
        ]);
    }

    public function onHold(): static
    {
        return $this->state(fn () => [
            'status' => 'on_hold',
            'closed_at' => null,
            'closed_reason' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => 'closed',
            'closed_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'closed_reason' => fake()->sentence(),
        ]);
    }
}