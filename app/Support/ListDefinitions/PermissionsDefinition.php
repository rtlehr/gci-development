<?php

namespace App\Support\ListDefinitions;

class PermissionsDefinition
{
    public static function get()
    {
        return [
            'list_key' => 'permissions',
            'default_sort' => 'name',
            'default_direction' => 'asc',

            'columns' => [
                [
                    'key' => 'group_name',
                    'label' => 'Group',
                    'db_field' => 'permissions.group_name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 1,
                ],
                [
                    'key' => 'label',
                    'label' => 'Label',
                    'db_field' => 'permissions.label',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 2,
                ],
                [
                    'key' => 'name',
                    'label' => 'Name',
                    'db_field' => 'permissions.name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 3,
                ],
                [
                    'key' => 'description',
                    'label' => 'Description',
                    'db_field' => 'permissions.description',
                    'sortable' => false,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 4,
                ],
                [
                    'key' => 'is_locked',
                    'label' => 'Locked',
                    'db_field' => 'permissions.is_locked',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => 99,
                ],
            ],
        ];
    }
}