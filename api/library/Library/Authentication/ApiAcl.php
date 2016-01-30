<?php

namespace ApiLibrary\Authentication;

use DDD\Service\Booking\BookingTicket;
use DDD\Service\Apartment\ReviewCategory;

use Library\Constants\Roles;

class ApiAcl
{
    public static function getResourceRole()
    {
        return [
            Roles::ROLE_NULL => [
            ],
            Roles::ROLE_MOBILE_INCIDENT_REPORT => [
                ['route' => 'task.rest.incidents', 'methods' => ['POST']],
            ],
            Roles::ROLE_MOBILE_ASSET_MANAGER => [
                ['route' => 'warehouse.rest.barcodes',         'methods' => ['POST', 'PATCH', 'GET']],
                ['route' => 'warehouse.rest.assets',           'methods' => ['POST', 'PATCH', 'GET']],
                ['route' => 'warehouse.rest.histories',        'methods' => ['GET']],
                ['route' => 'warehouse.rest.categories',       'methods' => ['POST', 'GET']],
                ['route' => 'warehouse.rest.configs',          'methods' => ['GET']],
                ['route' => 'warehouse.rest.statuses',         'methods' => ['GET']],
                ['route' => 'common.rest.users',               'methods' => ['GET']],
                ['route' => 'common.rest.locations',           'methods' => ['GET']],
                ['route' => 'ginosi-link.rest.users',          'methods' => ['GET']],
                ['route' => 'ginosi-link.rest.user-hashes',    'methods' => ['GET', 'POST', 'DELETE']],
                ['route' => 'ginosi-tally.rest.venue-charges', 'methods' => ['GET']],
                ['route' => 'ginosi-tally.rest.user-pins',     'methods' => ['GET']],
            ],
        ];
    }
}
