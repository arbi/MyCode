<?php
return array(
    'service_manager' => array(
        'factories' => array(
            'GinosiTally\\V1\\Rest\\VenueCharges\\VenueChargesResource' => 'GinosiTally\\V1\\Rest\\VenueCharges\\VenueChargesResourceFactory',
            'GinosiTally\\V1\\Rest\\UserPins\\UserPinsResource' => 'GinosiTally\\V1\\Rest\\UserPins\\UserPinsResourceFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'ginosi-tally.rest.venue-charges' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/ginosi-tally/venue-charges[/:venue_id]',
                    'defaults' => array(
                        'controller' => 'GinosiTally\\V1\\Rest\\VenueCharges\\Controller',
                    ),
                ),
            ),
            'ginosi-tally.rest.user-pins' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/ginosi-tally/user/:user_id/pin[/:pin]',
                    'defaults' => array(
                        'controller' => 'GinosiTally\\V1\\Rest\\UserPins\\Controller',
                    ),
                ),
            ),
        ),
    ),
    'zf-versioning' => array(
        'uri' => array(
            0 => 'ginosi-tally.rest.venue-charges',
            1 => 'ginosi-tally.rest.user-pins',
        ),
    ),
    'zf-rest' => array(
        'GinosiTally\\V1\\Rest\\VenueCharges\\Controller' => array(
            'listener' => 'GinosiTally\\V1\\Rest\\VenueCharges\\VenueChargesResource',
            'route_name' => 'ginosi-tally.rest.venue-charges',
            'route_identifier_name' => 'venue_id',
            'collection_name' => 'venue_charges',
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
            'entity_class' => 'GinosiTally\\V1\\Rest\\VenueCharges\\VenueChargesEntity',
            'collection_class' => 'GinosiTally\\V1\\Rest\\VenueCharges\\VenueChargesCollection',
            'service_name' => 'venueCharges',
        ),
        'GinosiTally\\V1\\Rest\\UserPins\\Controller' => array(
            'listener' => 'GinosiTally\\V1\\Rest\\UserPins\\UserPinsResource',
            'route_name' => 'ginosi-tally.rest.user-pins',
            'route_identifier_name' => 'pin',
            'collection_name' => 'user_pins',
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
            'entity_class' => 'GinosiTally\\V1\\Rest\\UserPins\\UserPinsEntity',
            'collection_class' => 'GinosiTally\\V1\\Rest\\UserPins\\UserPinsCollection',
            'service_name' => 'userPins',
        ),
    ),
    'zf-content-negotiation' => array(
        'controllers' => array(
            'GinosiTally\\V1\\Rest\\VenueCharges\\Controller' => 'Json',
            'GinosiTally\\V1\\Rest\\UserPins\\Controller' => 'Json',
        ),
        'accept_whitelist' => array(
            'GinosiTally\\V1\\Rest\\VenueCharges\\Controller' => array(
                0 => 'application/vnd.ginosi-tally.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
            'GinosiTally\\V1\\Rest\\UserPins\\Controller' => array(
                0 => 'application/vnd.ginosi-tally.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
        ),
        'content_type_whitelist' => array(
            'GinosiTally\\V1\\Rest\\VenueCharges\\Controller' => array(
                0 => 'application/vnd.ginosi-tally.v1+json',
                1 => 'application/json',
            ),
            'GinosiTally\\V1\\Rest\\UserPins\\Controller' => array(
                0 => 'application/vnd.ginosi-tally.v1+json',
                1 => 'application/json',
            ),
        ),
    ),
    'zf-hal' => array(
        'metadata_map' => array(
            'GinosiTally\\V1\\Rest\\VenueCharges\\VenueChargesEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'ginosi-tally.rest.venue-charges',
                'route_identifier_name' => 'venue_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'GinosiTally\\V1\\Rest\\VenueCharges\\VenueChargesCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'ginosi-tally.rest.venue-charges',
                'route_identifier_name' => 'venue_id',
                'is_collection' => true,
            ),
            'GinosiTally\\V1\\Rest\\UserPins\\UserPinsEntity' => array(
                'entity_identifier_name' => 'pin',
                'route_name' => 'ginosi-tally.rest.user-pins',
                'route_identifier_name' => 'pin',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'GinosiTally\\V1\\Rest\\UserPins\\UserPinsCollection' => array(
                'entity_identifier_name' => 'pin',
                'route_name' => 'ginosi-tally.rest.user-pins',
                'route_identifier_name' => 'pin',
                'is_collection' => true,
            ),
        ),
    ),
);
