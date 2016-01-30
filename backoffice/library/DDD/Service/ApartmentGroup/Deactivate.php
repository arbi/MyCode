<?php

namespace DDD\Service\ApartmentGroup;

use DDD\Dao\Apartment\General;
use DDD\Service\ServiceBase;
use DDD\Dao\ApartmentGroup\ApartmentGroup as ApartmentGroupDAO;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

/**
 * Class Deactivate
 * @package DDD\Service\ApartmentGroup
 *
 * @author Tigran Petrosyan
 */
class Deactivate extends ServiceBase
{

    /**
     * @param $apartmentGroupId
     * @return bool
     */
    public function removeConciergeDashboardAccess($apartmentGroupId)
    {
        /**
         * @var \DDD\Dao\ApartmentGroup\ConciergeDashboardAccess $conciergeDashboardAccessDao
         */
        $conciergeDashboardAccessDao = $this->getServiceLocator()->get('dao_apartment_group_concierge_dashboard_access');

        $conciergeDashboardAccessDao->delete([
            'apartment_group_id' => $apartmentGroupId
        ]);

        return true;
    }
}
