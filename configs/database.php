<?php

if (! function_exists('databaseConfigs')) {
    function databaseConfigs() {
        return [
            'connections' => [
                # Retakes
                'default' => [
                    'driver' => env('RETAKES_DB_DRIVER', 'mysql'),
                    'host' => env('RETAKES_DB_HOST', 'localhost'),
                    'database' => env('RETAKES_DB_DATABASE', 'uz'),
                    'username' => env('RETAKES_DB_USERNAME', 'root'),
                    'password' => env('RETAKES_DB_PASSWORD'),
                    'charset' => env('RETAKES_DB_CHARSET', 'utf8'),
                    'collation' => env('RETAKES_DB_COLLATION', 'utf8_unicode_ci'),
                    'prefix' => env('RETAKES_DB_PREFIX', ''),
                ],
                'competitives' => [
                    'driver' => env('COMPETITIVES_DB_DRIVER', 'mysql'),
                    'host' => env('COMPETITIVES_DB_HOST', 'localhost'),
                    'database' => env('COMPETITIVES_DB_DATABASE', 'uz'),
                    'username' => env('COMPETITIVES_DB_USERNAME', 'root'),
                    'password' => env('COMPETITIVES_DB_PASSWORD'),
                    'charset' => env('COMPETITIVES_DB_CHARSET', 'utf8'),
                    'collation' => env('COMPETITIVES_DB_COLLATION', 'utf8_unicode_ci'),
                    'prefix' => env('COMPETITIVES_DB_PREFIX', ''),
                ],
            ],
        ];
    }
}