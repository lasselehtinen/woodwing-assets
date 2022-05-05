<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WoodWing Assets configuration
    |--------------------------------------------------------------------------
    |
    | Note! Remember to include the full API endpoint to the hostname
    |
     */

    'endpoint' => env('WOODWING_ASSETS_ENDPOINT', 'https://assets.example.com/services'),
    'username' => env('WOODWING_ASSETS_USERNAME', 'guest'),
    'password' => env('WOODWING_ASSETS_PASSWORD', 'guest'),
];
