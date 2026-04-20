<?php

namespace App\Support\ListDefinitions;

class WorkflowDefinition
{
    public static function get()
    {
        return [
            'list_key' => 'workflows',
            'default_sort' => 'workflows.name',
            'default_direction' => 'asc',

            'columns' => [
                [
                    'key' => 'id',
                    'label' => 'ID',
                    'db_field' => 'workflows.id',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => 1,
                ],
                [
                    'key' => 'name',
                    'label' => 'Name',
                    'db_field' => 'workflows.name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 2,
                ],
                [
                    'key' => 'code',
                    'label' => 'Code',
                    'db_field' => 'workflows.code',
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
                    'db_field' => 'workflows.description',
                    'sortable' => false,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 4,
                ],
                [
                    'key' => 'is_primary',
                    'label' => 'Primary Workflow',
                    'db_field' => 'workflows.is_primary',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 5,
                ],
                [
                    'key' => 'is_active',
                    'label' => 'Active',
                    'db_field' => 'workflows.is_active',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 6,
                ],
                [
                    'key' => 'step_count',
                    'label' => 'Steps',
                    'db_field' => 'step_count',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 7,
                ],
                [
                    'key' => 'created_at',
                    'label' => 'Created',
                    'db_field' => 'workflows.created_at',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 8,
                ],
            ],
        ];
    }
}