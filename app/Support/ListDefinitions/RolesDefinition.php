<?php

namespace App\Support\ListDefinitions;

class RolesDefinition
{
    public static function get()
    {
        return [
            'list_key' => 'roles',
            'default_sort' => 'name',
            'default_direction' => 'asc',

            'columns' => [
                [
                    'key' => 'label',
                    'label' => 'Label',
                    'db_field' => 'roles.label',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 1,
                ],
                [
                    'key' => 'name',
                    'label' => 'Name',
                    'db_field' => 'roles.name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 2,
                ],
                [
                    'key' => 'description',
                    'label' => 'Description',
                    'db_field' => 'roles.description',
                    'sortable' => false,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 3,
                ],
                [
                    'key' => 'permissions_count',
                    'label' => 'Permission Count',
                    'db_field' => 'permissions_count',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 4,
                ],
            ],
        ];
    }
}