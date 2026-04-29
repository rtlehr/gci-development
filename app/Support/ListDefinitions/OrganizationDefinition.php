<?php

namespace App\Support\ListDefinitions;

class OrganizationDefinition
{
    public static function get()
    {
        return [
            'list_key' => 'organizations',
            'default_sort' => 'organizations.name',
            'default_direction' => 'asc',

            'columns' => [
                [
                    'key' => 'name',
                    'label' => 'Name',
                    'db_field' => 'organizations.name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 1,
                ],
                [
                    'key' => 'full_path',
                    'label' => 'Full Path',
                    'db_field' => 'organizations.full_path',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 2,
                ],
                [
                    'key' => 'parent_name',
                    'label' => 'Parent',
                    'db_field' => 'parents.name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 3,
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'db_field' => 'organizations.status',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 4,
                ],
                [
                    'key' => 'notes',
                    'label' => 'Notes',
                    'db_field' => 'organizations.notes',
                    'sortable' => false,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => 5,
                ],
                [
                    'key' => 'created_at',
                    'label' => 'Created',
                    'db_field' => 'organizations.created_at',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => 6,
                ],
            ],
        ];
    }
}