<?php
return [
    'cache' => 1,
    'caching_type' => 'file',
    'caching_type_params' => [
        'redis' => [
            'db' => 1
        ]
    ],
    'cache_life_time' => 3600,
    'load_output_filter' => false,
];