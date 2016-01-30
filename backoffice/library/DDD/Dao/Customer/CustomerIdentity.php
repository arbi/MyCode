<?php

namespace DDD\Dao\Customer;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;

class CustomerIdentity extends TableGatewayManager
{

    protected $table = DbTables::TBL_CUSTOMER_IDENTITY;

    public function __construct($sm, $domain = 'DDD\Domain\Customer\CustomerIdentity')
    {
        parent::__construct($sm, $domain);
    }

    /**
     *
     * @param int $reservationId
     * @return \DDD\Domain\Customer\CustomerIdentity
     */
    public function getCustomerIdentityByReservationId($reservationId)
    {
        $result = $this->fetchOne(function (Select $select) use ($reservationId) {
			$select->columns([
                'id',
                'customer_id',
                'reservation_id',
                'user_id',
                'ip_address',
                'ip_hostname',
                'ip_provider',
                'ua_family',
                'ua_major',
                'ua_minor',
                'ua_patch',
                'ua_language',
                'os_family',
                'os_major',
                'os_minor',
                'os_patch',
                'os_patchMinor',
                'device_family',
                'device_brand',
                'device_model',
                'geo_city',
                'geo_region',
                'geo_country',
                'geo_location',
                'landing_page',
                'referer_page',
                'referer_host'
            ]);

            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = users.id',
                ['user_name' => new Expression("CONCAT(`users`.`firstname`, ' ', `users`.`lastname`)")],
                Select::JOIN_LEFT);

            $select->where
                ->equalTo('reservation_id', $reservationId);
		});

		return $result;
    }

}
