<?php

namespace App\Support\ListDefinitions;

class GroupDefinition
{
    public static function get()
    {
        return [
            'list_key' => 'groups',
            'default_sort' => 'group_name',
            'default_direction' => 'asc',

            'columns' => [
                [
                    'key' => 'group_name',
                    'label' => 'Group Name',
                    'db_field' => 'groups.group_name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 1,
                ],
                [
                    'key' => 'created_at',
                    'label' => 'Created',
                    'db_field' => 'groups.created_at',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => 2,
                ],
                [
                    'key' => 'updated_at',
                    'label' => 'Updated',
                    'db_field' => 'groups.updated_at',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => 3,
                ],
            ],
        ];
    }
}