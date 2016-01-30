<?php

namespace DDD\Service\Website;

use DDD\Dao\ApartmentGroup\ConciergeView;
use DDD\Service\ApartmentGroup;
use DDD\Service\ServiceBase;

/**
 * Class Arrivals
 * @package DDD\Service\Website
 *
 * @author Tigran Petrosyan
 */
class Arrivals extends ServiceBase
{
    /**
     * @param $apartmentGroupId
     * @param $date
     * @return \DDD\Domain\ApartmentGroup\Concierge\ConciergeWebsiteView[]
     */
    public function getArrivals($apartmentGroupId, $date)
    {
        /**
         * @var ConciergeView $conciergeViewDao
         * @var ApartmentGroup $apartmentGroupService
         */
        $conciergeViewDao = $this->getServiceLocator()->get('dao_apartment_group_concierge_view');
        $apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');

        $apartmentIds = $apartmentGroupService->getApartmentGroupItems($apartmentGroupId);

        $arrivals = [];
        if (isset($apartmentIds[0])) {
            $arrivals = $conciergeViewDao->getArrivalsForWebsitePage($apartmentIds, $date);
        }

        return $arrivals;
    }
}
