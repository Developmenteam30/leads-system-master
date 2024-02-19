<?php

// Miscellaneous Application Settings

return [
    'default_job_email' => env('DEFAULT_JOB_EMAIL', 'hello@example.com'),
    'developer_email' => env('DEVELOPER_EMAIL', 'hello@example.com'),
    'job_memory_limit' => env('JOB_MEMORY_LIMIT', '6G'),
    'jwt' => [
        'secret' => env('JWT_SECRET', ''),
        'algorithm' => env('JWT_ALGORITHM', 'HS256'),
    ],
    'timezone' => [
        'local' => env('TIMEZONE', 'America/New_York'),
        'belize' => env('BELIZE_TIMEZONE', 'America/Belize'),
        // See config('convoso.timezone') for the Convoso timezone
    ],
];
