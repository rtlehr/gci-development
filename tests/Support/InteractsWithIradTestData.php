<?php

namespace Tests\Support;

use App\Models\JobTitle;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;

trait InteractsWithIradTestData
{
    protected function createLinkedUser(
        array $userAttributes = [],
        array $personAttributes = [],
    ): array {
        $user = User::factory()->create($userAttributes);

        $person = Person::factory()
            ->forUser($user)
            ->active()
            ->create(array_merge([
                'first_name' => 'Test',
                'preferred_name' => 'Test',
                'last_name' => 'User',
                'email' => $user->email,
            ], $personAttributes));

        return [
            'user' => $user,
            'person' => $person,
        ];
    }

    protected function createRole(
        string $name,
        ?string $label = null,
        ?string $description = null,
    ): Role {
        return Role::query()->firstOrCreate(
            ['name' => $name],
            [
                'label' => $label ?? str($name)->replace('_', ' ')->title(),
                'description' => $description ?? 'Created for automated testing.',
            ],
        );
    }

    protected function createUserWithRole(
        string $roleName,
        array $userAttributes = [],
        array $personAttributes = [],
    ): array {
        $context = $this->createLinkedUser(
            $userAttributes,
            $personAttributes,
        );

        $role = $this->createRole($roleName);

        $context['user']->roles()->syncWithoutDetaching([$role->id]);
        $context['role'] = $role;

        return $context;
    }

    protected function createProjectManager(
        array $userAttributes = [],
        array $personAttributes = [],
    ): array {
        return $this->createUserWithRole(
            'project_manager',
            $userAttributes,
            $personAttributes,
        );
    }

    protected function createCotr(
        array $userAttributes = [],
        array $personAttributes = [],
    ): array {
        return $this->createUserWithRole(
            'cotr',
            $userAttributes,
            $personAttributes,
        );
    }

    protected function actingAsLinkedUser(
        array $userAttributes = [],
        array $personAttributes = [],
    ): array {
        $context = $this->createLinkedUser(
            $userAttributes,
            $personAttributes,
        );

        $this->actingAs($context['user']);

        return $context;
    }

    protected function actingAsProjectManager(
        array $userAttributes = [],
        array $personAttributes = [],
    ): array {
        $context = $this->createProjectManager(
            $userAttributes,
            $personAttributes,
        );

        $this->actingAs($context['user']);

        return $context;
    }

    protected function actingAsCotr(
        array $userAttributes = [],
        array $personAttributes = [],
    ): array {
        $context = $this->createCotr(
            $userAttributes,
            $personAttributes,
        );

        $this->actingAs($context['user']);

        return $context;
    }

    protected function createJobTitle(
        array $attributes = [],
    ): JobTitle {
        return JobTitle::query()->create(array_merge([
            'name' => 'Test Position Title',
            'description' => 'Created for automated testing.',
            'is_active' => true,
            'sort_order' => 1,
        ], $attributes));
    }
}
