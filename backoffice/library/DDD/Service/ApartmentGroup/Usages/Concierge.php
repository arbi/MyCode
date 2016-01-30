<?php

namespace DDD\Service\ApartmentGroup\Usages;

use DDD\Dao\ApartmentGroup\ConciergeDashboardAccess as ConciergeDashboardAccessDAO;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Roles;

/**
 * Class Concierge
 * @package DDD\Service\ApartmentGroup\Usages
 *
 * @author Tigran Petrosyan
 */
class Concierge extends Base
{
    public function conciergeSave($data, $global, $id)
    {

        if ($global) {
            $accGroupsManagementDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

            $apartmentGroupData = [
                'email'  => (!empty($data['concierge_email'])) ? $data['concierge_email'] : null,
                'psp_id' => (!empty($data['psp_id'])) ? $data['psp_id'] : null,
            ];

            $accGroupsManagementDao->save($apartmentGroupData, ['id' => (int)$id]);
            return true;
        }
    }

    /**
     * @param int $usage
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\ApartmentGroup\ForSelect[]
     */
    public function getApartmentGroupsByUsage($hasNotDevAccess, $usage = Base::APARTMENT_GROUP_USAGE_CONCIERGE, $deactivatedIncluded = false)
    {
        return parent::getApartmentGroupsByUsage($usage, $deactivatedIncluded, $hasNotDevAccess);
    }

    /**
     * @return array|\DDD\Domain\ApartmentGroup\ForSelect[]
     */
    public function getUserAvailableConciergeApartmentGroups()
    {
        /**
         * @var BackofficeAuthenticationService $authenticationService
         * @var ConciergeDashboardAccessDAO $conciergeDashboardAccessDao
         */
        $authenticationService       = $this->getServiceLocator()->get('library_backoffice_auth');
        $conciergeDashboardAccessDao = $this->getServiceLocator()->get('dao_apartment_group_concierge_dashboard_access');
        $loggedInUserId              = (int)$authenticationService->getIdentity()->id;

        $apartmentGroups = [];
        $hasNotDevAccess = true;
        if ($authenticationService->hasRole(Roles::ROLE_DEVELOPMENT_TESTING)) {
            $hasNotDevAccess = false;
        }

        if ($authenticationService->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER)) {
            $apartmentGroups = $this->getApartmentGroupsByUsage($hasNotDevAccess);
        } elseif ($authenticationService->hasRole(Roles::ROLE_CONCIERGE_DASHBOARD)) {
            $apartmentGroups = $conciergeDashboardAccessDao->getUserAccessibleConciergeDashboards($loggedInUserId, $hasNotDevAccess);
        }

        return $apartmentGroups;
    }
}
