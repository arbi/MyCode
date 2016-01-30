<?php

return [
    'bsb_flysystem' => [
        'adapters' => [
            'ginosi_uploads' => [
                'type'    => 'local',
                'options' => [
                    'root' => '/ginosi/uploads'
                ],
            ],
            'ginosi_images' => [
                'type'    => 'local',
                'options' => [
                    'root' => '/ginosi/images'
                ],
            ],
            'ginosi_db_backup' => [
                'type'    => 'local',
                'options' => [
                    'root' => '/ginosi/db'
                ],
            ],
        ],
        'filesystems' => [
            'uploads' => [
                'adapter'   => 'ginosi_uploads',
                'cache'     => false,
                'eventable' => false,
                'plugins'   => [
                    'League\Flysystem\Plugin\ListFiles',
                    'League\Flysystem\Plugin\GetWithMetadata',
                    'League\Flysystem\Plugin\ListPaths'
                ]
            ],
            'images' => [
                'adapter'   => 'ginosi_images',
                'cache'     => false,
                'eventable' => false,
                'plugins'   => [
                    'League\Flysystem\Plugin\ListFiles',
                    'League\Flysystem\Plugin\GetWithMetadata',
                    'League\Flysystem\Plugin\ListPaths',
                    'League\Flysystem\Plugin\ListWith'
                ]
            ],
            'db_backup' => [
                'adapter'   => 'ginosi_db_backup',
                'cache'     => false,
                'eventable' => false,
                'plugins'   => [
                    'League\Flysystem\Plugin\ListFiles',
                    'League\Flysystem\Plugin\GetWithMetadata',
                    'League\Flysystem\Plugin\ListPaths',
                ]
            ],
        ],
    ],
];
