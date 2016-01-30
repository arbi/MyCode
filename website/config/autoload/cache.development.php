<?php

// clean apc chache
apc_clear_cache();

return array(
    'caches' => array(
        'memcached' => array(
            'adapter' => array(
                'name'     =>'memcached',
                'lifetime' => 7200,
                'options'  => array(
                    'servers'   => array(
                        array(
                            '127.0.0.1',11211
                        )
                    ),
                    'namespace'  => 'GINOSICACHE',
                    'liboptions' => array (
                        'COMPRESSION' => true,
                        'binary_protocol' => true,
                        'no_block' => true,
                        'connect_timeout' => 100
                    )
                )
            ),
            'plugins' => array(
                'exception_handler' => array(
                    'throw_exceptions' => false
                ),
            ),
        ),
    ),
);