<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lot label
    |--------------------------------------------------------------------------
    |
    | What a supplier lot is called on screen: "Lot", "Heat number" for steel,
    | "Batch" for chemicals and food. The data model is the same either way.
    |
    */

    'lot_label' => env('QMS_LOT_LABEL', 'Lot'),

    /*
    |--------------------------------------------------------------------------
    | Due-soon window
    |--------------------------------------------------------------------------
    |
    | Gauges show as "due" and documents as "due for review" this many days
    | before their date, and their owners start getting the daily reminder.
    |
    */

    'due_soon_days' => (int) env('QMS_DUE_SOON_DAYS', 14),

];
