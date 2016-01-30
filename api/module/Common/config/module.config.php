<?php
return array(
    'service_manager' => array(
        'factories' => array(
            'Common\\V1\\Rest\\Locations\\LocationsResource' => 'Common\\V1\\Rest\\Locations\\LocationsResourceFactory',
            'Common\\V1\\Rest\\Users\\UsersResource' => 'Common\\V1\\Rest\\Users\\UsersResourceFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'common.rest.locations' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/locations[/:locations_id]',
                    'defaults' => array(
                        'controller' => 'Common\\V1\\Rest\\Locations\\Controller',
                    ),
                ),
            ),
            'common.rest.users' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/users[/:users_id]',
                    'defaults' => array(
                        'controller' => 'Common\\V1\\Rest\\Users\\Controller',
                    ),
                ),
            ),
        ),
    ),
    'zf-versioning' => array(
        'uri' => array(
            0 => 'common.rest.locations',
            1 => 'common.rest.users',
        ),
    ),
    'zf-rest' => array(
        'Common\\V1\\Rest\\Locations\\Controller' => array(
            'listener' => 'Common\\V1\\Rest\\Locations\\LocationsResource',
            'route_name' => 'common.rest.locations',
            'route_identifier_name' => 'locations_id',
            'collection_name' => 'locations',
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
            'entity_class' => 'Common\\V1\\Rest\\Locations\\LocationsEntity',
            'collection_class' => 'Common\\V1\\Rest\\Locations\\LocationsCollection',
            'service_name' => 'locations',
        ),
        'Common\\V1\\Rest\\Users\\Controller' => array(
            'listener' => 'Common\\V1\\Rest\\Users\\UsersResource',
            'route_name' => 'common.rest.users',
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
            'entity_class' => 'Common\\V1\\Rest\\Users\\UsersEntity',
            'collection_class' => 'Common\\V1\\Rest\\Users\\UsersCollection',
            'service_name' => 'users',
        ),
    ),
    'zf-content-negotiation' => array(
        'controllers' => array(
            'Common\\V1\\Rest\\Locations\\Controller' => 'Json',
            'Common\\V1\\Rest\\Users\\Controller' => 'Json',
        ),
        'accept_whitelist' => array(
            'Common\\V1\\Rest\\Locations\\Controller' => array(
                0 => 'application/vnd.common.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
            'Common\\V1\\Rest\\Users\\Controller' => array(
                0 => 'application/vnd.common.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
        ),
        'content_type_whitelist' => array(
            'Common\\V1\\Rest\\Locations\\Controller' => array(
                0 => 'application/vnd.common.v1+json',
                1 => 'application/json',
            ),
            'Common\\V1\\Rest\\Users\\Controller' => array(
                0 => 'application/vnd.common.v1+json',
                1 => 'application/json',
            ),
        ),
    ),
    'zf-hal' => array(
        'metadata_map' => array(
            'Common\\V1\\Rest\\Locations\\LocationsEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'common.rest.locations',
                'route_identifier_name' => 'locations_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'Common\\V1\\Rest\\Locations\\LocationsCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'common.rest.locations',
                'route_identifier_name' => 'locations_id',
                'is_collection' => true,
            ),
            'Common\\V1\\Rest\\Users\\UsersEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'common.rest.users',
                'route_identifier_name' => 'users_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'Common\\V1\\Rest\\Users\\UsersCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'common.rest.users',
                'route_identifier_name' => 'users_id',
                'is_collection' => true,
            ),
        ),
    ),
);
