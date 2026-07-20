<?php

use App\Support\RoleAbbreviation;

it('returns configured role abbreviations', function () {
    expect(RoleAbbreviation::for('project_manager', 'Project Manager'))->toBe('PM')
        ->and(RoleAbbreviation::for('admin', 'Admin'))->toBe('Admin')
        ->and(RoleAbbreviation::for('pmo', 'PMO'))->toBe('PMO');
});

it('falls back to the role label for unknown roles', function () {
    expect(RoleAbbreviation::for('security_officer', 'Security Officer'))
        ->toBe('Security Officer');
});

it('formats multiple roles uniquely', function () {
    $roles = [
        ['name' => 'admin', 'label' => 'Admin'],
        ['name' => 'project_manager', 'label' => 'Project Manager'],
        ['name' => 'project_manager', 'label' => 'Project Manager'],
    ];

    expect(RoleAbbreviation::forRoles($roles))->toBe(['Admin', 'PM']);
});
