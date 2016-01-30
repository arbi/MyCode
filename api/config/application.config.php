<?php
/**
 * Configuration file generated by ZF Apigility Admin
 *
 * The previous config file has been stored in ./config/application.config.old
 */
return array(
    'modules' => array(
        'Application',
        'ZF\\DevelopmentMode',
        'ZF\\Apigility',
        'ZF\\Apigility\\Provider',
        'ZF\\ApiProblem',
        'ZF\\Configuration',
        'ZF\\OAuth2',
        'ZF\\MvcAuth',
        'ZF\\Hal',
        'ZF\\ContentNegotiation',
        'ZF\\ContentValidation',
        'ZF\\Rest',
        'ZF\\Rpc',
        'ZF\\Versioning',
        'AssetManager',
        'Warehouse',
        'ZF2Graylog2',
        'FileManager',
        'BsbFlysystem',
        'Task',
        'Common',
        'GinosiLink',
        'GinosiTally',
    ),
    'module_listener_options' => array(
        'module_paths' => array(
            './module',
            '/ginosi/backoffice/library',
            '/ginosi/backoffice/module',
        ),
        'config_glob_paths' => array(
            'config/autoload/{,*.}{global,development}.php',
        ),
        'config_cache_key' => 'application.config.cache',
        'config_cache_enabled' => true,
        'module_map_cache_key' => 'application.module.cache',
        'module_map_cache_enabled' => true,
        'cache_dir' => 'data/cache/',
    ),
);
