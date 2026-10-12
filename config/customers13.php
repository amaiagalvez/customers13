<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Integration views
    |--------------------------------------------------------------------------
    |
    | This is the whole seam between this package and an optional integration that adds something
    | to the customer table. `columns` renders one `<flux:table.column>` per extra column, `cells`
    | one `<flux:table.cell>` per extra column in the same order, and `count` says how many there
    | are so the empty state can span them.
    |
    | All three are null or zero by default, so the customer table shows only what a customer knows
    | by itself.
    |
    */

    'views' => [

        // Columns appended to the customer list.
        'customer_list' => [
            'columns' => null,
            'cells' => null,
            'count' => 0,
        ],

    ],

];
