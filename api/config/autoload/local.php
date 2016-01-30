<?php
return array(
    'zf-mvc-auth' => array(
        'authentication' => array(
            'adapters' => array(
                'oauth2_pdo' => array(
                    'adapter' => 'ZF\\MvcAuth\\Authentication\\OAuth2Adapter',
                    'storage' => array(
                        'adapter' => 'pdo',
                        'dsn' => 'mysql:dbname=backoffice;host=localhost;',
                        'route' => '/oauth',
                        'username' => 'root',
                        'password' => 'toxindzners',
                    ),
                ),
            ),
        ),
    ),
);
