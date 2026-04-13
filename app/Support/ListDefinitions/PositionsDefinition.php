<?php

namespace App\Support\ListDefinitions;

class PositionsDefinition
{
    public static function get()
    {
        return [
            'list_key' => 'positions',
            'default_sort' => 'created_at',
            'default_direction' => 'desc',

            'columns' => [
                [
                    'key' => 'position_code',
                    'label' => 'Code',
                    'db_field' => 'positions.position_code',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 1,
                ],
                [
                    'key' => 'job_title',
                    'label' => 'Job Title',
                    'db_field' => 'positions.job_title',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 2,
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'db_field' => 'positions.status',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 3,
                ],
                [
                    'key' => 'labor_category',
                    'label' => 'Labor Category',
                    'db_field' => 'positions.labor_category',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 4,
                ],
                [
                    'key' => 'project_team_name',
                    'label' => 'Team',
                    'db_field' => 'positions.project_team_name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 5,
                ],
            ],
        ];
    }
}