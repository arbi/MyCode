<?php

use Library\Constants\Constants;

return [
    'view_manager' => [
        'base_path' => Constants::VERSION,
        'display_not_found_reason' => false,
        'display_exceptions'       => false,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'         => __DIR__ . '/../view/layout/layout.phtml',
            'website/index/index'   => __DIR__ . '/../view/website/index/index.phtml',
            'error/404'             => __DIR__ . '/../view/error/404.phtml',
            'error/index'           => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
            'ViewFeedStrategy'
        ],
    ],
];
