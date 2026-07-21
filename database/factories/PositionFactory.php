<?php

namespace Database\Factories;

use App\Models\JobTitle;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        static $number = 1;

        return [
            'position_code' => 'TEST-POS-' . str_pad((string) $number++, 5, '0', STR_PAD_LEFT),
            'status' => 'Open',
            'job_title' => fake()->jobTitle(),
            'job_title_id' => null,
            'experience_level' => null,
            'labor_category' => null,
            'level' => fake()->numberBetween(1, 5),
            'team_name' => fake()->optional()->words(2, true),
            'project_manager_user_id' => null,
            'certifications_required' => null,
            'training_required' => null,
            'experience' => null,
            'is_essential' => false,
            'travel_required' => false,
            'high_risk_role' => false,
            'location' => null,
            'building' => null,
            'mission_description' => null,
            'component' => null,
            'position_organization_id' => null,
            'sponsoring_organization_id' => null,
            'funding_organization_id' => null,
            'funding_info' => null,
            'request_to_close' => false,
            'scheduled_to_close' => null,
            'close_date' => null,
            'close_reason' => null,
            'project_team_name' => null,
            'customer_lead_name' => null,
            'customer_created_at' => now()->toDateString(),
            'notes' => null,
        ];
    }

    public function forJobTitle(JobTitle $jobTitle): static
    {
        return $this->state(fn () => [
            'job_title_id' => $jobTitle->id,
            'job_title' => $jobTitle->name,
        ]);
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'status' => 'Open',
            'close_date' => null,
            'close_reason' => null,
        ]);
    }

    public function inProcess(): static
    {
        return $this->state(fn () => [
            'status' => 'In Process',
            'close_date' => null,
            'close_reason' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => 'Closed',
            'close_date' => now()->toDateString(),
            'close_reason' => fake()->sentence(),
        ]);
    }
}
