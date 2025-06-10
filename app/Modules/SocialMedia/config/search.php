<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the search functionality
    |
    */

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 50,
        'min_per_page' => 5,
    ],

    'limits' => [
        'query_min_length' => 2,
        'query_max_length' => 255,
    ],

    'cache' => [
        'enabled' => env('SEARCH_CACHE_ENABLED', false),
        'ttl' => env('SEARCH_CACHE_TTL', 300), // 5 minutes
        'prefix' => 'search:',
    ],

    'scoring' => [
        'exact_match_bonus' => 50,
        'partial_match_base' => 100,
        'skill_match_points' => 25,
        'experience_match_points' => 20,
        'recent_activity_bonus' => 10,
    ],
];
