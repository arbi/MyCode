<?php

return array(
    'view_helpers' => array(
        'invokables' => array(
        )
    ),
	'mail' => array(
     	'renderer' => array(
			'templatePathStack' => array(
				__DIR__ . '/../templates',
                __DIR__ . '/../view'
     		),
            'templateMap' => array(
                'layout/layout' => __DIR__ . '/../layout/layout.phtml',
                'layout/layout-new'  => __DIR__ . '/../layout/layout-new.phtml',
                'layout/clean'  => __DIR__ . '/../layout/clean.phtml',
            ),
		),
		'transport' => array(
     		'type' => 'smtp',
			'options' => array(
				'name'              => 'localhost',
				'host'              => 'smtp.gmail.com',
				'port'              => 587, // Notice port change for TLS is 587
				'connection_class'  => 'plain',
				'connection_config' => array(
					'username' => 'reservations@ginosi.com',
					'password' => 'eFjE285FU0p0X1XO',
					'ssl'      => 'tls',
				),
			),
     	),
        'transport-alerts' => array(
     		'type' => 'smtp',
			'options' => array(
				'name'              => 'localhost',
				'host'              => 'smtp.gmail.com',
				'port'              => 587, // Notice port change for TLS is 587
				'connection_class'  => 'plain',
				'connection_config' => array(
					'username' => 'alert@ginosi.com',
					'password' => 'WhB383I&8emI&D173C',
					'ssl'      => 'tls',
				),
			),
     	),
	),
);