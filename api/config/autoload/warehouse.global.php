<?php

// numbers set as hour
return [
    'warehouse' => [
        'assets'     => 86400, // 1 day
        'locations'  => 86400, // 1 day
        'users'      => 86400, // 1 day
        'categories' => 86400, // 1 day
        'updateTime' => 86400, // 1 day
        'apiExpired' => 604800, // 7 days
        'version'    => 1,
    ],
    'providers' => [
        "google",
        "ginosi"
    ],
    'imageConfigs' => [
        'maxWidth'  =>  1024,
        'maxHeight' => 1024
    ],
];
