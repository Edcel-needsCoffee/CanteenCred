<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache", "array"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | FACU-CRED Specific Configuration
    |--------------------------------------------------------------------------
    */
    'facu_cred_transaction_prefix' => env('FACU_CRED_TRANSACTION_PREFIX', 'TXN'),
    'facu_cred_default_credit_limit' => env('FACU_CRED_DEFAULT_CREDIT_LIMIT', 2000.00),
    'facu_cred_max_credit_limit' => env('FACU_CRED_MAX_CREDIT_LIMIT', 50000.00),
    'facu_cred_login_max_attempts' => env('FACU_CRED_LOGIN_MAX_ATTEMPTS', 5),
    'facu_cred_login_lockout_minutes' => env('FACU_CRED_LOGIN_LOCKOUT_MINUTES', 15),
    'facu_cred_session_timeout_minutes' => env('FACU_CRED_SESSION_TIMEOUT_MINUTES', 120),
    'facu_cred_api_rate_limit' => env('FACU_CRED_API_RATE_LIMIT', 60),
    'facu_cred_payroll_period_default_days' => env('FACU_CRED_PAYROLL_PERIOD_DEFAULT_DAYS', 30),
    'facu_cred_audit_log_retention_days' => env('FACU_CRED_AUDIT_LOG_RETENTION_DAYS', 2555),
    'facu_cred_currency_symbol' => env('FACU_CRED_CURRENCY_SYMBOL', 'P'),
    'facu_cred_decimal_places' => env('FACU_CRED_DECIMAL_PLACES', 2),

];
