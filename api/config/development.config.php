<?php

return [
    // Development time modules
    'modules' => [
         'ZFTool',
         'ZF\Apigility\Admin',
         'ZF\\Apigility\\Documentation',
    ],
    // development time configuration globbing
    'module_listener_options' => [
        'config_glob_paths'        => ['config/autoload/{,*.}development.php'],
        'config_cache_enabled'     => false,
        'module_map_cache_enabled' => false,
    ]
];
