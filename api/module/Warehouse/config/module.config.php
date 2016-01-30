<?php
return array(
    'service_manager' => array(
        'factories' => array(
            'Warehouse\\V1\\Rest\\Assets\\AssetsResource'         => 'Warehouse\\V1\\Rest\\Assets\\AssetsResourceFactory',
            'Warehouse\\V1\\Rest\\Categories\\CategoriesResource' => 'Warehouse\\V1\\Rest\\Categories\\CategoriesResourceFactory',
            'Warehouse\\V1\\Rest\\Histories\\HistoriesResource'   => 'Warehouse\\V1\\Rest\\Histories\\HistoriesResourceFactory',
            'Warehouse\\V1\\Rest\\Configs\\ConfigsResource'       => 'Warehouse\\V1\\Rest\\Configs\\ConfigsResourceFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'warehouse.rest.assets' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/warehouse/assets[/:assets_id]',
                    'defaults' => array(
                        'controller' => 'Warehouse\\V1\\Rest\\Assets\\Controller',
                    ),
                    'constraints' => array(
                        'assets_id' => '[a-zA-Z0-9]+',
                    ),
                ),
            ),
            'warehouse.rest.categories' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/warehouse/categories[/:categories_id]',
                    'defaults' => array(
                        'controller' => 'Warehouse\\V1\\Rest\\Categories\\Controller',
                    ),
                ),
            ),
            'warehouse.rest.histories' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/warehouse/assets/:assets_id/histories[/:histories_id]',
                    'defaults' => array(
                        'controller' => 'Warehouse\\V1\\Rest\\Histories\\Controller',
                    ),
                    'constraints' => array(
                        'assets_id' => '[a-zA-Z0-9]+',
                    ),
                ),
            ),
            'warehouse.rest.configs' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/warehouse/configs[/:configs_id]',
                    'defaults' => array(
                        'controller' => 'Warehouse\\V1\\Rest\\Configs\\Controller',
                    ),
                ),
            ),
        ),
    ),
    'zf-versioning' => array(
        'uri' => array(
            0 => 'warehouse.rest.assets',
            4 => 'warehouse.rest.categories',
            6 => 'warehouse.rest.histories',
            7 => 'warehouse.rest.configs',
        ),
    ),
    'zf-rest' => array(
        'Warehouse\\V1\\Rest\\Assets\\Controller' => array(
            'listener' => 'Warehouse\\V1\\Rest\\Assets\\AssetsResource',
            'route_name' => 'warehouse.rest.assets',
            'route_identifier_name' => 'assets_id',
            'collection_name' => 'assets',
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
            'entity_class' => 'Warehouse\\V1\\Rest\\Assets\\AssetsEntity',
            'collection_class' => 'Warehouse\\V1\\Rest\\Assets\\AssetsCollection',
            'service_name' => 'assets',
        ),
        'Warehouse\\V1\\Rest\\Categories\\Controller' => array(
            'listener' => 'Warehouse\\V1\\Rest\\Categories\\CategoriesResource',
            'route_name' => 'warehouse.rest.categories',
            'route_identifier_name' => 'categories_id',
            'collection_name' => 'categories',
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
            'entity_class' => 'Warehouse\\V1\\Rest\\Categories\\CategoriesEntity',
            'collection_class' => 'Warehouse\\V1\\Rest\\Categories\\CategoriesCollection',
            'service_name' => 'categories',
        ),
        'Warehouse\\V1\\Rest\\Histories\\Controller' => array(
            'listener' => 'Warehouse\\V1\\Rest\\Histories\\HistoriesResource',
            'route_name' => 'warehouse.rest.histories',
            'route_identifier_name' => 'histories_id',
            'collection_name' => 'histories',
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
            'entity_class' => 'Warehouse\\V1\\Rest\\Histories\\HistoriesEntity',
            'collection_class' => 'Warehouse\\V1\\Rest\\Histories\\HistoriesCollection',
            'service_name' => 'histories',
        ),
        'Warehouse\\V1\\Rest\\Configs\\Controller' => array(
            'listener' => 'Warehouse\\V1\\Rest\\Configs\\ConfigsResource',
            'route_name' => 'warehouse.rest.configs',
            'route_identifier_name' => 'configs_id',
            'collection_name' => 'configs',
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
            'entity_class' => 'Warehouse\\V1\\Rest\\Configs\\ConfigsEntity',
            'collection_class' => 'Warehouse\\V1\\Rest\\Configs\\ConfigsCollection',
            'service_name' => 'configs',
        ),
    ),
    'zf-content-negotiation' => array(
        'controllers' => array(
            'Warehouse\\V1\\Rest\\Assets\\Controller' => 'Json',
            'Warehouse\\V1\\Rest\\Categories\\Controller' => 'Json',
            'Warehouse\\V1\\Rest\\Histories\\Controller' => 'HalJson',
            'Warehouse\\V1\\Rest\\Configs\\Controller' => 'Json',
        ),
        'accept_whitelist' => array(
            'Warehouse\\V1\\Rest\\Assets\\Controller' => array(
                0 => 'application/vnd.warehouse.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
            'Warehouse\\V1\\Rest\\Categories\\Controller' => array(
                0 => 'application/vnd.warehouse.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
            'Warehouse\\V1\\Rest\\Histories\\Controller' => array(
                0 => 'application/vnd.warehouse.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
            'Warehouse\\V1\\Rest\\Configs\\Controller' => array(
                0 => 'application/vnd.warehouse.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
        ),
        'content_type_whitelist' => array(
            'Warehouse\\V1\\Rest\\Assets\\Controller' => array(
                0 => 'application/vnd.warehouse.v1+json',
                1 => 'application/json',
            ),
            'Warehouse\\V1\\Rest\\Categories\\Controller' => array(
                0 => 'application/vnd.warehouse.v1+json',
                1 => 'application/json',
            ),
            'Warehouse\\V1\\Rest\\Histories\\Controller' => array(
                0 => 'application/vnd.warehouse.v1+json',
                1 => 'application/json',
            ),
            'Warehouse\\V1\\Rest\\Configs\\Controller' => array(
                0 => 'application/vnd.warehouse.v1+json',
                1 => 'application/json',
            ),
        ),
    ),
    'zf-hal' => array(
        'metadata_map' => array(
            'Warehouse\\V1\\Rest\\Assets\\AssetsEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'warehouse.rest.assets',
                'route_identifier_name' => 'assets_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'Warehouse\\V1\\Rest\\Assets\\AssetsCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'warehouse.rest.assets',
                'route_identifier_name' => 'assets_id',
                'is_collection' => true,
            ),
            'Warehouse\\V1\\Rest\\Categories\\CategoriesEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'warehouse.rest.categories',
                'route_identifier_name' => 'categories_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'Warehouse\\V1\\Rest\\Categories\\CategoriesCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'warehouse.rest.categories',
                'route_identifier_name' => 'categories_id',
                'is_collection' => true,
            ),
            'Warehouse\\V1\\Rest\\Histories\\HistoriesEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'warehouse.rest.histories',
                'route_identifier_name' => 'histories_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'Warehouse\\V1\\Rest\\Histories\\HistoriesCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'warehouse.rest.histories',
                'route_identifier_name' => 'histories_id',
                'is_collection' => true,
            ),
            'Warehouse\\V1\\Rest\\Configs\\ConfigsEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'warehouse.rest.configs',
                'route_identifier_name' => 'configs_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'Warehouse\\V1\\Rest\\Configs\\ConfigsCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'warehouse.rest.configs',
                'route_identifier_name' => 'configs_id',
                'is_collection' => true,
            ),
        ),
    ),
    'zf-content-validation' => array(
        'Warehouse\\V1\\Rest\\Assets\\Controller' => array(
            'input_filter' => 'Warehouse\\V1\\Rest\\Assets\\Validator',
        ),
    ),
    'input_filter_specs' => array(
        'Warehouse\\V1\\Rest\\Assets\\Validator' => array(
            0 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'locationEntityId',
                'description' => 'location id based on its type',
            ),
            1 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'locationEntityType',
                'description' => 'location type: office, storage, building, apartment',
            ),
            2 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'quantity',
                'description' => 'The quantity of asset',
            ),
            3 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'categoryId',
                'description' => 'category Id',
            ),
            4 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'status',
                'description' => 'if asset is consumable is set to 0 if not it is set correspond to its asset type',
            ),
            5 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'barcode',
                'description' => 'barcode identification whether its sku or serial number',
            ),
            6 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'assigneeId',
                'description' => 'the assigned user id',
                'allow_empty' => true,
            ),
            7 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'name',
                'description' => 'This field is for valuable assets (for consumable asstes set empty)',
                'allow_empty' => true,
            ),
            8 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'assetType',
                'description' => '1 : consumable 2: valuable',
            ),
            9 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'shipmentStatus',
                'description' => 'For valuable asset always set as 1',
                'allow_empty' => false,
            ),
            10 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'comment',
                'allow_empty' => true,
            ),
            11 => array(
                'required' => true,
                'validators' => array(),
                'filters' => array(),
                'name' => 'uuid',
            ),
        ),
    ),
);
