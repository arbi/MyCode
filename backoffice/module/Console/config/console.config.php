<?php

// Ginosole Environment
$env = 'development';

$modules = array(
    'Console',
    'Mailer',
    'ZF2Graylog2',
    'CreditCard',
    'FileManager'
);

// for development only
if ($env !== 'production') {
    $modules = array_merge($modules, array(
        //'DevModulesHere',
    ));
}

return array(
    // This should be an array of module namespaces used in the application.
    'modules' => $modules,

    // These are various options for the listeners attached to the ModuleManager
    'module_listener_options' => array(
        // This should be an array of paths in which modules reside.
        // If a string key is provided, the listener will consider that a module
        // namespace, the value of that key the specific path to that module's
        // Module class.
        'module_paths' => array(
            './module',
            './library',
            './vendor',
        ),

        // An array of paths from which to glob configuration files after
        // modules are loaded. These effectively overide configuration
        // provided by modules themselves. Paths may use GLOB_BRACE notation.
        'config_glob_paths' => array(
            sprintf('config/autoload/{,*.}{global,%s}.php', $env),
            //'config/autoload/{,*.}{global,local}.php',
            //'config/autoload/{,*.}' . (getenv('APPLICATION_ENV') ?: 'production') . '.php',
        ),
    ),
   'service_manager' => array(
        'use_defaults' => true,
        'factories'    => array(
        ),
    ),
);
