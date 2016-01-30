<?php
$dbParams = array(
    'driver'    => 'pdo_mysql',
    'dsn'       => 'mysql:dbname=backoffice;host=localhost;',
    'username'  => 'root',
    'password'  => 'toxindzners'
);

return array(
    'service_manager' => array(
        'factories' => array(
            'Zend\Db\Adapter\Adapter' => function ($sm) use ($dbParams) {
                return new Zend\Db\Adapter\Adapter(array(
                    'driver'         => $dbParams['driver'],
                    'dsn'            => $dbParams['dsn'],
                    'username'       => $dbParams['username'],
                    'password'       => $dbParams['password'],
                    'driver_options' => array(
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARACTER SET \'UTF8\'',
                    ),
                ));
            },
            'Library\DbManager' => function ($sm){
                return new \Library\DbManager\DbManager($sm);
            },

        ),
        'aliases' => array(
            'dbadapter' => 'Zend\Db\Adapter\Adapter',
            'db'        => 'Library\DbManager',
        ),
    ),
    'database_params' => array_merge($dbParams, [
        'backup_path' => '/ginosi/db/'
    ]),
);
