<?php

// Microsoft Graph Settings for Power BI and Sharepoint

return [
    'oauth' => [
        'app_id' => env('MICROSOFT_OAUTH_APP_ID', ''),
        'app_secret' => env('MICROSOFT_OAUTH_APP_SECRET', ''),
        'redirect_uri' => env('MICROSOFT_OAUTH_REDIRECT_URI', ''),
        'scopes' => env('MICROSOFT_OAUTH_SCOPES', ''),
        'authority' => env('MICROSOFT_OAUTH_AUTHORITY', ''),
        'authorize_endpoint' => env('MICROSOFT_OAUTH_AUTHORIZE_ENDPOINT', '/oauth2/v2.0/authorize'),
        'token_endpoint' => env('MICROSOFT_OAUTH_TOKEN_ENDPOINT', '/oauth2/v2.0/token'),
    ],

    'sharepoint' => [
        'site_id' => env('MICROSOFT_SHAREPOINT_SITE_ID', ''),
        'dispo_item_id' => env('MICROSOFT_SHAREPOINT_DISPO_ITEM_ID', ''),
        'performance_item_id' => env('MICROSOFT_SHAREPOINT_PERFORMANCE_ITEM_ID', ''),
        'retreaver_item_id' => env('MICROSOFT_SHAREPOINT_RETREAVER_ITEM_ID', ''),
    ],
];
