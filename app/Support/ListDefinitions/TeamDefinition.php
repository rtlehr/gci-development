<?php

namespace App\Support\ListDefinitions;

class TeamDefinition
{
    public static function get()
    {
        return [
            'list_key' => 'teams',
            'default_sort' => 'team_name',
            'default_direction' => 'asc',

            'columns' => [
                [
                    'key' => 'team_name',
                    'label' => 'Team Name',
                    'db_field' => 'teams.team_name',
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
                    'db_field' => 'teams.created_at',
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
                    'db_field' => 'teams.updated_at',
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