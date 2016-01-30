<?php
return array(
    'service_manager' => array(
        'factories' => array(
            'GinosiLink\\V1\\Rest\\Users\\UsersResource' => 'GinosiLink\\V1\\Rest\\Users\\UsersResourceFactory',
            'GinosiLink\\V1\\Rest\\UserHashes\\UserHashesResource' => 'GinosiLink\\V1\\Rest\\UserHashes\\UserHashesResourceFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'ginosi-link.rest.users' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/ginosi-link/users[/:users_id]',
                    'defaults' => array(
                        'controller' => 'GinosiLink\\V1\\Rest\\Users\\Controller',
                    ),
                ),
            ),
            'ginosi-link.rest.user-hashes' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/ginosi-link/user-hashes[/:user_hashes_id]',
                    'defaults' => array(
                        'controller' => 'GinosiLink\\V1\\Rest\\UserHashes\\Controller',
                    ),
                ),
            ),
        ),
    ),
    'zf-versioning' => array(
        'uri' => array(
            0 => 'ginosi-link.rest.users',
            1 => 'ginosi-link.rest.user-hashes',
        ),
    ),
    'zf-rest' => array(
        'GinosiLink\\V1\\Rest\\Users\\Controller' => array(
            'listener' => 'GinosiLink\\V1\\Rest\\Users\\UsersResource',
            'route_name' => 'ginosi-link.rest.users',
            'route_identifier_name' => 'users_id',
            'collection_name' => 'users',
            'entity_http_methods' => array(
                0 => 'GET',
                1 => 'PATCH',
                2 => 'PUT',
                3 => 'DELETE',
            ),
            'collection_http_methods' => array(
                0 => 'GET',
                1 => 'POST',
            ),
            'collection_query_whitelist' => array(),
            'page_size' => 25,
            'page_size_param' => null,
            'entity_class' => 'GinosiLink\\V1\\Rest\\Users\\UsersEntity',
            'collection_class' => 'GinosiLink\\V1\\Rest\\Users\\UsersCollection',
            'service_name' => 'users',
        ),
        'GinosiLink\\V1\\Rest\\UserHashes\\Controller' => array(
            'listener' => 'GinosiLink\\V1\\Rest\\UserHashes\\UserHashesResource',
            'route_name' => 'ginosi-link.rest.user-hashes',
            'route_identifier_name' => 'user_hashes_id',
            'collection_name' => 'user_hashes',
            'entity_http_methods' => array(
                0 => 'GET',
                1 => 'PATCH',
                2 => 'PUT',
                3 => 'DELETE',
            ),
            'collection_http_methods' => array(
                0 => 'GET',
                1 => 'POST',
            ),
            'collection_query_whitelist' => array(),
            'page_size' => 25,
            'page_size_param' => null,
            'entity_class' => 'GinosiLink\\V1\\Rest\\UserHashes\\UserHashesEntity',
            'collection_class' => 'GinosiLink\\V1\\Rest\\UserHashes\\UserHashesCollection',
            'service_name' => 'userHashes',
        ),
    ),
    'zf-content-negotiation' => array(
        'controllers' => array(
            'GinosiLink\\V1\\Rest\\Users\\Controller' => 'Json',
            'GinosiLink\\V1\\Rest\\UserHashes\\Controller' => 'Json',
        ),
        'accept_whitelist' => array(
            'GinosiLink\\V1\\Rest\\Users\\Controller' => array(
                0 => 'application/vnd.ginosi-link.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
            'GinosiLink\\V1\\Rest\\UserHashes\\Controller' => array(
                0 => 'application/vnd.ginosi-link.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
        ),
        'content_type_whitelist' => array(
            'GinosiLink\\V1\\Rest\\Users\\Controller' => array(
                0 => 'application/vnd.ginosi-link.v1+json',
                1 => 'application/json',
            ),
            'GinosiLink\\V1\\Rest\\UserHashes\\Controller' => array(
                0 => 'application/vnd.ginosi-link.v1+json',
                1 => 'application/json',
            ),
        ),
    ),
    'zf-hal' => array(
        'metadata_map' => array(
            'GinosiLink\\V1\\Rest\\Users\\UsersEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'ginosi-link.rest.users',
                'route_identifier_name' => 'users_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'GinosiLink\\V1\\Rest\\Users\\UsersCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'ginosi-link.rest.users',
                'route_identifier_name' => 'users_id',
                'is_collection' => true,
            ),
            'GinosiLink\\V1\\Rest\\UserHashes\\UserHashesEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'ginosi-link.rest.user-hashes',
                'route_identifier_name' => 'user_hashes_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'GinosiLink\\V1\\Rest\\UserHashes\\UserHashesCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'ginosi-link.rest.user-hashes',
                'route_identifier_name' => 'user_hashes_id',
                'is_collection' => true,
            ),
        ),
    ),
);
