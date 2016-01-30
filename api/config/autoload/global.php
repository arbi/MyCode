<?php
return array(
    'db' => array(
        'adapters' => array(
            'db' => array(),
        ),
    ),
    'router' => array(
        'routes' => array(
            'oauth' => array(
                'options' => array(
                    'spec' => '%oauth%',
                    'regex' => '(?P<oauth>(/oauth))',
                ),
                'type' => 'regex',
            ),
        ),
    ),
    'zf-mvc-auth' => array(
        'authentication' => array(
            'map' => array(
                'Asset\\V1' => 'bo-oauth',
                'Warehouse\\V1' => 'oauth2_pdo',
                'Task\\V1' => 'oauth2_pdo',
                'Common\\V1' => 'oauth2_pdo',
                'User\\V1' => 'oauth2_pdo',
            ),
        ),
    ),
);
