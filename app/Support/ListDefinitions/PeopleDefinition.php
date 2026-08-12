<?php

namespace App\Support\ListDefinitions;

use Illuminate\Support\Facades\DB;

class PeopleDefinition
{
    public static function get()
    {
        return [
            'list_key' => 'people',
            'entity_model' => \App\Models\Person::class,
            'entity_table' => 'people',
            'default_sort' => 'last_name',
            'default_direction' => 'asc',

            'columns' => [
                [
                    'key' => 'person_code',
                    'label' => 'Code',
                    'db_field' => 'people.person_code',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 1,
                ],
                [
                    'key' => 'first_name',
                    'label' => 'First Name',
                    'db_field' => 'people.first_name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 2,
                ],
                [
                    'key' => 'alternate_first_name',
                    'label' => 'Alternate First Name',
                    'db_field' => 'people.alternate_first_name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => 3,
                ],
                [
                    'key' => 'preferred_name',
                    'label' => 'Preferred Name',
                    'db_field' => 'people.preferred_name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => 4,
                ],
                [
                    'key' => 'last_name',
                    'label' => 'Last Name',
                    'db_field' => 'people.last_name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 5,
                ],
                [
                    'key' => 'alternate_last_name',
                    'label' => 'Alternate Last Name',
                    'db_field' => 'people.alternate_last_name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => false,
                    'default_order' => 6,
                ],
                [
                    'key' => 'company_name',
                    'label' => 'Company',
                    'db_field' => 'people.company_name',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 7,
                ],
                [
                    'key' => 'email',
                    'label' => 'Email',
                    'db_field' => 'people.email',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 8,
                ],
                [
                    'key' => 'employment_status',
                    'label' => 'Employment Status',
                    'db_field' => 'people.employment_status',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 9,
                ],
                [
                    'key' => 'primary_phone_number',
                    'label' => 'Primary Phone',
                    'db_field' => 'primary_phone.phone_number',
                    'sortable' => true,
                    'searchable' => true,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                [
                    'key' => 'primary_address_display',
                    'label' => 'Primary Address',
                    'db_field' => 'primary_address.line_1',
                    'sortable' => false,
                    'searchable' => false,
                    'hideable' => true,
                    'exportable' => true,
                    'default_visible' => true,
                    'default_order' => 11,
                ],

            ],
        ];
    }
}