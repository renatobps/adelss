<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'youtube' => [
        'key' => env('YOUTUBE_API_KEY'),
    ],

    'google_drive' => [
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'root_folder_name' => env('GOOGLE_DRIVE_ROOT_FOLDER', 'ADELSS'),
    ],

    'instagram' => [
        'app_id' => env('INSTAGRAM_APP_ID', env('META_APP_ID')),
        'app_secret' => env('INSTAGRAM_APP_SECRET', env('META_APP_SECRET')),
    ],

];
