<?php

namespace App\Support\ListDefinitions;

class UserPermissionsDefinition
{
    public static function get()
    {
        return [
            'list_key' => 'user_permissions',
            'default_sort' => 'person_code',
            'default_direction' => 'asc',

            'columns' => [
                [
                    'key' => 'person_code',
                    'label' => 'AIN Number',
                    'db_field' => 'person_code',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 1,
                ],
                [
                    'key' => 'full_name',
                    'label' => 'Name',
                    'db_field' => 'full_name',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 2,
                ],
                [
                    'key' => 'roles',
                    'label' => 'Role',
                    'db_field' => null,
                    'sortable' => false,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 3,
                ],
                [
                    'key' => 'permissions',
                    'label' => 'Permissions',
                    'db_field' => null,
                    'sortable' => false,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 4,
                ],
                [
                    'key' => 'email',
                    'label' => 'Email',
                    'db_field' => 'users.email',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => 99,
                ],
            ],
        ];
    }
}