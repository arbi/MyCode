<?php
return array(
    'service_manager' => array(
        'factories' => array(
            'Task\\V1\\Rest\\Incidents\\IncidentsResource' => 'Task\\V1\\Rest\\Incidents\\IncidentsResourceFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'task.rest.incidents' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/task/incidents[/:incidents_id]',
                    'defaults' => array(
                        'controller' => 'Task\\V1\\Rest\\Incidents\\Controller',
                    ),
                ),
            ),
        ),
    ),
    'zf-versioning' => array(
        'uri' => array(
            0 => 'task.rest.incidents',
        ),
    ),
    'zf-rest' => array(
        'Task\\V1\\Rest\\Incidents\\Controller' => array(
            'listener' => 'Task\\V1\\Rest\\Incidents\\IncidentsResource',
            'route_name' => 'task.rest.incidents',
            'route_identifier_name' => 'incidents_id',
            'collection_name' => 'incidents',
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
            'entity_class' => 'Task\\V1\\Rest\\Incidents\\IncidentsEntity',
            'collection_class' => 'Task\\V1\\Rest\\Incidents\\IncidentsCollection',
            'service_name' => 'incidents',
        ),
    ),
    'zf-content-negotiation' => array(
        'controllers' => array(
            'Task\\V1\\Rest\\Incidents\\Controller' => 'Json',
        ),
        'accept_whitelist' => array(
            'Task\\V1\\Rest\\Incidents\\Controller' => array(
                0 => 'application/vnd.task.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
        ),
        'content_type_whitelist' => array(
            'Task\\V1\\Rest\\Incidents\\Controller' => array(
                0 => 'application/vnd.task.v1+json',
                1 => 'application/json',
            ),
        ),
    ),
    'zf-hal' => array(
        'metadata_map' => array(
            'Task\\V1\\Rest\\Incidents\\IncidentsEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'task.rest.incidents',
                'route_identifier_name' => 'incidents_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'Task\\V1\\Rest\\Incidents\\IncidentsCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'task.rest.incidents',
                'route_identifier_name' => 'incidents_id',
                'is_collection' => true,
            ),
        ),
    ),
    'zf-content-validation' => array(
        'Task\\V1\\Rest\\Incidents\\Controller' => array(
            'input_filter' => 'Task\\V1\\Rest\\Incidents\\Validator',
        ),
    ),
    'input_filter_specs' => array(
        'Task\\V1\\Rest\\Incidents\\Validator' => array(
            0 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'description',
                'allow_empty' => true,
            ),
            1 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'uuid',
            ),
            2 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'locationEntityType',
            ),
            3 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'locationEntityId',
            ),
        ),
    ),
);
