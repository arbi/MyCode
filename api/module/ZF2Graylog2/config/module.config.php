<?php

return [
    'service_manager' => array(
        'factories' => array(
            'Graylog' => function($sm) {
                $logger = new \ZF2Graylog2\Service\Logger($sm);
                $logger->setServiceLocator($sm);

                return $logger;
            },
        ),
    ),
];
