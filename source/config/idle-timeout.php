<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Idle Timeout Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the idle timeout feature.
    | You can customize the timeout values and behavior here.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Idle Timeout
    |--------------------------------------------------------------------------
    |
    | The default number of minutes a user can be idle before being logged out.
    | This can be overridden by the SESSION_IDLE_TIMEOUT environment variable.
    |
    */

    'timeout_minutes' => env('SESSION_IDLE_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Warning Time
    |--------------------------------------------------------------------------
    |
    | The number of minutes before timeout to show a warning to the user.
    | This gives users a chance to stay logged in.
    |
    */

    'warning_minutes' => env('IDLE_WARNING_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Activity Check Interval
    |--------------------------------------------------------------------------
    |
    | How often (in milliseconds) to check for user activity on the client side.
    | Lower values provide more accurate tracking but use more resources.
    |
    */

    'check_interval_ms' => env('IDLE_CHECK_INTERVAL', 60000),

    /*
    |--------------------------------------------------------------------------
    | Server Update Interval
    |--------------------------------------------------------------------------
    |
    | How often (in milliseconds) to update the server with activity status.
    | This helps keep the server-side session in sync.
    |
    */

    'update_interval_ms' => env('IDLE_UPDATE_INTERVAL', 300000),

    /*
    |--------------------------------------------------------------------------
    | Excluded Routes
    |--------------------------------------------------------------------------
    |
    | Routes that should be excluded from idle timeout checking.
    | Useful for API endpoints or pages that don't require user interaction.
    |
    */

    'excluded_routes' => [
        'api/*',
        'logout',
        'login',
        'password/reset*',
        'email/verify*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware that should be excluded from idle timeout checking.
    | Useful for API routes or other special cases.
    |
    */

    'excluded_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Excluded Roles
    |--------------------------------------------------------------------------
    |
    | User roles that should be excluded from idle timeout checking.
    | Users with these roles will never be logged out due to inactivity.
    |
    */

    'excluded_roles' => [
        'Root',
        'Admin',
        // Add more roles here as needed
    ],
];
