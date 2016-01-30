<?php

// retreive envionment
$environment = getenv('APPLICATION_ENV') ?: 'production';

// define common module namespaces used in all environments
$commonModules = array(
    'Mailer',
    'Backoffice',
    'Finance',
    'UniversalDashboard',
    'ModuleLayouts',
    'Apartment',
    'Apartel',
    'GMaps',
    'GoogleCharts',
	'BackofficeUser',
    'ZF2Graylog2',
    'Recruitment',
    'FileManager',
    'Lock',
    'Parking',
    'CreditCard',
    'Contacts',
    'Document',
    'Venue',
    'Warehouse',
    'WHOrder',
    'BsbFlysystem',
    'Reviews'
);

// environment depended module namespaces
$environmentModules = array();
switch ($environment) {
	case 'development':
		$environmentModules = array(
			//'ZendDeveloperTools',
			'GMaps',
			'Apartment',
            'UnitTesting',
		);
		break;
}

return array(
    // This should be an array of module namespaces used in the application.
    'modules' => array_merge($commonModules, $environmentModules),

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
            sprintf('config/autoload/{,*.}{global,%s}.php', $environment),
        ),
    ),

   // Initial configuration with which to seed the ServiceManager.
   // Should be compatible with Zend\ServiceManager\Config.
   'service_manager' => array(
        'use_defaults'  => true,
        'factories'     => array(),
    ),
);
