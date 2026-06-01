<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fee view password
    |--------------------------------------------------------------------------
    | Parents must enter this shared password to unlock the fee/payment
    | section. Stored in .env so it never lives in the codebase.
    */
    'fee_view_password' => env('FEE_VIEW_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | PayNow number
    |--------------------------------------------------------------------------
    | Appended to the generated WhatsApp billing message so parents know
    | where to send payment. Not a secret (it is shared with parents anyway).
    */
    'paynow_number' => env('PAYNOW_NUMBER'),

    /*
    |--------------------------------------------------------------------------
    | Default currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('WOWLO_CURRENCY', 'SGD'),

];
