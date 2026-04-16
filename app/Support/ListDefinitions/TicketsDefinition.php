<?php

namespace App\Support\ListDefinitions;

class TicketsDefinition
{
    public static function get()
    {
        return [
            'list_key' => 'tickets',
            'default_sort' => 'tickets.created_at',
            'default_direction' => 'desc',

            'columns' => [
                [
                    'key' => 'ticket_number',
                    'label' => 'Ticket',
                    'db_field' => 'tickets.ticket_number',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 1,
                ],
                [
                    'key' => 'title',
                    'label' => 'Title',
                    'db_field' => 'tickets.title',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 2,
                ],
                [
                    'key' => 'request_type',
                    'label' => 'Type',
                    'db_field' => 'tickets.request_type',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 3,
                ],
                [
                    'key' => 'importance',
                    'label' => 'Importance',
                    'db_field' => 'tickets.importance',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 4,
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'db_field' => 'tickets.status',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 5,
                ],
                [
                    'key' => 'submitted_by_name',
                    'label' => 'Submitted By',
                    'db_field' => 'submitted_by_name',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 6,
                ],
                [
                    'key' => 'assigned_to_name',
                    'label' => 'Assigned To',
                    'db_field' => 'assigned_to_name',
                    'sortable' => true,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 7,
                ],
                [
                    'key' => 'category',
                    'label' => 'Category',
                    'db_field' => 'tickets.category',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => 98,
                ],
                [
                    'key' => 'created_at',
                    'label' => 'Created',
                    'db_field' => 'tickets.created_at',
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