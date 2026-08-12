<?php

namespace App\Support\ListDefinitions;

class PositionsDefinition
{
    public static function get()
    {
        return [
            'list_key' => 'positions',
            'entity_model' => \App\Models\Position::class,
            'entity_table' => 'positions',
            'default_sort' => 'created_at',
            'default_direction' => 'desc',

            'columns' => [
                self::column('id', 'ID', 'positions.id', true, true, 1),
                self::column('job_title', 'Job Title', 'positions.job_title', true, true, 2),
                self::column('level', 'Level', 'positions.level', true, true, 3),
                self::column('team_name', 'Team Name', 'positions.team_name', true, true, 4),
                self::column('location', 'Location', 'positions.location', true, true, 5),
                self::column('building', 'Building', 'positions.building', true, true, 6),
                self::column('created_at', 'Created', 'positions.created_at', true, false, 7),
                self::column('close_date', 'Closed', 'positions.close_date', true, false, 8),

                self::column('position_code', 'Position Code', 'positions.position_code', false, true, 20),
                self::column('labor_category', 'Labor Category', 'positions.labor_category', false, true, 21),
                self::column('status', 'Status', 'positions.status', false, true, 22),
                self::column('component', 'Component', 'positions.component', false, true, 23),
                self::column('is_essential', 'Essential', 'positions.is_essential', false, false, 24),
                self::column('travel_required', 'Travel Required', 'positions.travel_required', false, false, 25),
                self::column('high_risk_role', 'High Risk Role', 'positions.high_risk_role', false, false, 26),
                self::column('scheduled_to_close', 'Scheduled To Close', 'positions.scheduled_to_close', false, false, 27),
            ],
        ];
    }

    private static function column(
        string $key,
        string $label,
        string $dbField,
        bool $defaultVisible,
        bool $searchable,
        int $defaultOrder
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'db_field' => $dbField,
            'sortable' => true,
            'searchable' => $searchable,
            'hideable' => true,
            'exportable' => true,
            'default_visible' => $defaultVisible,
            'default_order' => $defaultOrder,
        ];
    }
}
