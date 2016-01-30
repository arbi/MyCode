<?php

namespace DDD\Service\ApartmentGroup;

use DDD\Dao\ApartmentGroup\ApartmentGroupItems as ApartmentGroupItemsDAO;
use DDD\Service\Apartel\General as apartelGeneral;
use DDD\Service\Lock\General as LockGeneral;
use DDD\Service\ServiceBase;
use DDD\Service\ApartmentGroup\Deactivate as ApartmentGroupDeactivateService;
use DDD\Dao\ApartmentGroup\ApartmentGroup as ApartmentGroupDAO;

use DDD\Service\Translation;
use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\ActionLogger\Logger as ActionLogger;

use Zend\Db\Sql\Where;
/**
 * Class Main
 * @package DDD\Service\ApartmentGroup
 *
 * @author Tigran Petrosyan
 */
class Main extends ServiceBase
{
    protected $_concierge              = null;
    protected $_conciergeaccommodation = null;
    protected $_accommodation          = null;
    protected $_conciergeView          = null;
    protected $_conciergeGroup         = null;
    protected $_user                   = null;
    protected $_booking                = null;

    public function generalSave($data, $id, $global)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroupItems $apartmentGroupItemsDao
         * @var \Library\ActionLogger\Logger $actionLogger
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $accGroupsManagementDao
         * @var \DDD\Dao\Apartel\General $apartelDao
         * @var \DDD\Dao\ApartmentGroup\BuildingSections $buildingSectionsDao
         * @var \DDD\Service\ApartmentGroup\Usages\Building $serviceBuilding
         */
        $actionLogger             = $this->getServiceLocator()->get('ActionLogger');
        $buildingFacilityItemsDao = $this->getServiceLocator()->get('dao_building_facility_items');
        $accGroupsManagementDao   = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $apartelDao               = $this->getServiceLocator()->get('dao_apartel_general');
        $serviceBuilding          = $this->getServiceLocator()->get('service_apartment_group_usages_building');
        $apartmentGroupId         = $id;

        if (!$global) {
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $conciergeData = $accGroupsManagementDao->getRowById($id);

            if ($conciergeData->getGroupManagerId() == $auth->getIdentity()->id) {
                $global = true;
            }
        }

        if (!isset($data['check_users']) || !(int)$data['check_users']) {
            $data['concierge_email'] = null;
        }
        $buildingSectionId = 0;
        if ($global) {
            $accGroupData = array(
                'name'                      => $data['name'],
                'timezone'                  => $data['timezone'],
                'group_manager_id'          => !empty($data['group_manager_id']) ? $data['group_manager_id'] : null,
                'usage_concierge_dashboard' => (isset($data['check_users']) && (int) $data['check_users'] > 0) ? 1 : 0,
                'usage_cost_center'         => (isset($data['usage_cost_center']) && (int) $data['usage_cost_center'] > 0) ? 1 : 0,
                'usage_building'            => (isset($data['usage_building']) && (int) $data['usage_building'] > 0) ? 1 : 0,
                'usage_performance_group'   => (isset($data['usage_performance_group']) && (int) $data['usage_performance_group'] > 0) ? 1 : 0,
                'country_id'                => (!empty($data['country_id'])) ? $data['country_id'] : null,
            );

            $buildingDetailsDao = $this->getServiceLocator()->get('dao_apartment_group_building_details');

            $accommodationsDao = $this->getAccommodationsDao();

            if ($id > 0) {
                $apartmentGroupCurrentState = $accGroupsManagementDao->getRowById($id);
                $buildingSectionId = $apartmentGroupCurrentState->getBuildingSectionId();

                // check for change Apartment Group name
                if ($apartmentGroupCurrentState->getName() != $data['name']) {
                    $actionLogger->save(
                        ActionLogger::MODULE_APARTMENT_GROUPS,
                        $id,
                        ActionLogger::ACTION_APARTMENT_GROUPS_NAME,
                        'The Apartment Group name change from "'
                            .$apartmentGroupCurrentState->getName()
                            .'" to "'
                            .$data['name'].'"'
                    );

                    // update slug
                    $apartelDao->update(
                        ['slug' => apartelGeneral::generateApartelSlug($data['name'])],
                        ['apartment_group_id'   => $id]
                    );
                }

                // check for change Usage - Cost Center
                if ($apartmentGroupCurrentState->getCostCenter() != $data['usage_cost_center']) {
                    $value = ($data['usage_cost_center']) ? 'is set as' : 'is set as non';

                    $actionLogger->save(
                        ActionLogger::MODULE_APARTMENT_GROUPS,
                        $id,
                        ActionLogger::ACTION_APARTMENT_GROUPS_USAGE,
                        'Group\'s usage ' . $value . ' Cost Center'
                    );
                }

                // check for change Usage - Concierge Dashboard
                if ($apartmentGroupCurrentState->getIsArrivalsDashboard() != $data['check_users']) {
                    $value = ($data['check_users']) ? 'is set as' : 'is set as non';

                    $actionLogger->save(
                        ActionLogger::MODULE_APARTMENT_GROUPS,
                        $id,
                        ActionLogger::ACTION_APARTMENT_GROUPS_USAGE,
                        'Group\'s usage ' . $value . ' Concierge Dashboard'
                    );
                }

                // check for change Usage - Building
                if ($apartmentGroupCurrentState->isBuilding() != $data['usage_building']) {
                    $value = ($data['usage_building']) ? 'is set as' : 'is set as non';

                    $actionLogger->save(
                        ActionLogger::MODULE_APARTMENT_GROUPS,
                        $id,
                        ActionLogger::ACTION_APARTMENT_GROUPS_USAGE,
                        'Group\'s usage ' . $value . ' Building'
                    );
                }


                // check for change Usage - Performance
                if ($apartmentGroupCurrentState->getIsPerformanceGroup() != $data['usage_performance_group']) {
                    $value = ($data['usage_performance_group']) ? 'is set as' : 'is set as non';

                    $actionLogger->save(
                        ActionLogger::MODULE_APARTMENT_GROUPS,
                        $id,
                        ActionLogger::ACTION_APARTMENT_GROUPS_USAGE,
                        'Group\'s usage ' . $value . ' Performance'
                    );
                }

                $apartmentGroupItemsDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group_items');
                $apartmentsCurrentList = $apartmentGroupItemsDao->getApartmentGroupItems($id);

                if (isset($data['accommodations']) && is_array($data['accommodations'])) {
                    $newApartmentsList = $data['accommodations'];
                } else {
                    $newApartmentsList = [];
                }

                // check for removing Apartments from group
                foreach ($apartmentsCurrentList as $currentApartment) {
                    if (   ($key = array_search($currentApartment->getApartmentId(), $newApartmentsList)) === FALSE
                        && $apartmentGroupCurrentState->isBuilding()
                    ) {

                        $accommodationsDao->save([
                            'building_id' => 0,
                            'building_section_id' => 0,
                        ], ['id' => $currentApartment->getApartmentId()]);

                        $actionLogger->save(
                            ActionLogger::MODULE_APARTMENT_GROUPS,
                            $id,
                            ActionLogger::ACTION_APARTMENT_GROUPS_APARTMENT_LIST,
                            'Apartment ' . $currentApartment->getApartmentName() .
                            ' (id: ' . $currentApartment->getApartmentId() . ') removed from group'
                        );
                    } else {
                        unset($newApartmentsList[$key]);
                    }
                }

                // check for adding Apartments to group
                if (!empty($newApartmentsList)) {
                    $apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');

                    foreach ($newApartmentsList as $addedApartmentId) {
                        $apartmentGeneral = $apartmentGeneralService->getApartmentGeneral($addedApartmentId);

                        $actionLogger->save(
                            ActionLogger::MODULE_APARTMENT_GROUPS,
                            $id,
                            ActionLogger::ACTION_APARTMENT_GROUPS_APARTMENT_LIST,
                            'Apartment '
                                .$apartmentGeneral['name']
                                . ' (id: '
                                .$addedApartmentId
                                .') added to group'
                        );
                    }
                }

                $accGroupsManagementDao->save($accGroupData, ['id' => (int)$id]);
            } else {
                $apartmentGroupId = $accGroupsManagementDao->save($accGroupData);
            }
            // fill in building details
            /** @var \DDD\Dao\Textline\Apartment $productTextlineDao */
            $productTextlineDao = $this->getServiceLocator()->get('dao_textline_apartment');

            if ($id && isset($data['usage_building']) && (int)$data['usage_building'] && !$apartmentGroupCurrentState->isBuilding()) {
                $productTextlineDao->insert([
                    'type'        => Translation::PRODUCT_TYPE_BUILDING,
                    'entity_type' => Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_FACILITIES,
                    'entity_id'   => $id,
                    'en'          => '',
                ]);
                $productTextlineDao->insert([
                    'type'        => Translation::PRODUCT_TYPE_BUILDING,
                    'entity_type' => Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_USAGE,
                    'entity_id'   => $id,
                    'en'          => '',
                ]);
                $productTextlineDao->insert([
                    'type'        => Translation::PRODUCT_TYPE_BUILDING,
                    'entity_type' => Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_POLICIES,
                    'entity_id'   => $id,
                    'en'          => '',
                ]);

                if (!$apartmentGroupCurrentState->getBuildingSectionId()) {
                    $buildingSectionId = $serviceBuilding->saveSection([
                        'section_id' => 0,
                        'section_name' => 'Section 1',
                        'building_id' => $id,
                        'lock' => LockGeneral::FREE_ENTRY,
                    ]);
                }

                /** @var \DDD\Dao\ApartmentGroup\BuildingDetails $buildingDetailsDao */
                $buildingDetailsDao = $this->getServiceLocator()->get('dao_apartment_group_building_details');
                $buildingDetailsDao->save(
                    [
                        'apartment_group_id' => $id
                    ]
                );
            }

            $accGroupsAccommodationDao = $this->getConciergeAccommodation();
            $accGroupsAccommodationDao->deleteWhere(['apartment_group_id' => (int)$apartmentGroupId]);

            if (isset($data['accommodations']) && !empty($data['accommodations'])) {
                foreach ($data['accommodations'] as $row) {
                    $accGroupsAccommodationDao->save([
                        'apartment_group_id' => (int)$apartmentGroupId,
                        'apartment_id' => (int)$row,
                    ]);
                }

                //update all accommmodations for this group and set building_id to this group id
                if (isset($data['usage_building']) && $data['usage_building']) {
                    $where = new Where();
                    $where->in('id', $data['accommodations']);
                    $accommodationsDao->update([
                        'building_id' => (int)$apartmentGroupId,
                        'building_section_id' => (int)$buildingSectionId,
                    ], $where);
                }
            }
        }

        return $apartmentGroupId;
    }

    /**
     * @param $apartmentGroupId
     * @param $mainData
     * @param $apartmentIds
     */
    public function save($apartmentGroupId, $mainData, $apartmentIds)
    {
        /**
         * @var ApartmentGroupDAO $apartmentGroupDao
         * @var ApartmentGroupItemsDAO $apartmentGroupItemsDao
         */
        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $apartmentGroupItemsDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group_items');

        /**
         * @todo save main data
         * @todo save apartments in relation table
         */
    }

    /**
     * @param int $apartmentGroupId
     * @return bool
     */
    public function deactivate($apartmentGroupId)
    {
        /**
         * @var ApartmentGroupDeactivateService $apartmentGroupDeactivateService
         * @var ApartmentGroupDAO $apartmentGroupDao
         */
        $apartmentGroupDeactivateService = $this->getServiceLocator()->get('service_apartment_group_deactivate');
        $apartmentGroupDao               = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $apartmentGroupData              = $apartmentGroupDao->getRowById($apartmentGroupId);
        $buildingDetailsDao              = $this->getServiceLocator()->get('dao_apartment_group_building_details') ;

        // check if apartel deactivate
        if ($apartmentGroupData->getIsApartel()) {
            /**
             * @var \DDD\Service\Apartel\General $apartelService
             */
            $apartelService = $this->getServiceLocator()->get('service_apartel_general');
            $apartelService->deactivateApartel($apartmentGroupId);
        }

        $apartmentGroupDao->update(
            [
                'active'      => 0
            ],
            ['id' => $apartmentGroupId]
        );

        $removeConciergeDashboardAccessResult = $apartmentGroupDeactivateService->removeConciergeDashboardAccess($apartmentGroupId);

        return $removeConciergeDashboardAccessResult;
    }

    public function checkGroupName($name, $id, $nameIsChanged = false)
    {
        /**
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $accGroupsManagementDao
         */
        $accGroupsManagementDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $rs = $accGroupsManagementDao->checkGroupName($name, $id, $nameIsChanged);

        return $rs;
    }

    public function getAccommodationsDao()
    {
        return $this->getServiceLocator()->get('dao_accommodation_accommodations');
    }

    public function getConciergeAccommodation()
    {
        if ($this->_conciergeaccommodation === null) {
            $this->_conciergeaccommodation = $this->getServiceLocator()->get('dao_apartment_group_apartment_group_items');
        }

        return $this->_conciergeaccommodation;
    }

    public function getAccommodationNotInBuilding($accommodations, $building_id)
    {
        $accommodationsDao = $this->getAccommodationsDao();
        $missmatched_accommodations = $accommodationsDao->getAccommodationNotInBuilding($accommodations, $building_id);

        return $missmatched_accommodations;
    }

    /**
     * @return array
     */
    public function getAllGroupNamesButApartelsAtFirst()
    {
        /**
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $accGroupsManagementDao
         */
        $accGroupsManagementDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $resArrayObj = $accGroupsManagementDao->getAllGroupNamesButApartelsAtFirst();
        $resArray = ["" => '-- All apartment groups --'];
        foreach($resArrayObj as $row) {
            $name = $row['name'];
            if ($row['usage_apartel']) {
                $name .= ' (Apartel)';
            }
            $resArray[$row['id']] = $name;
        }
        return $resArray;
    }
}
