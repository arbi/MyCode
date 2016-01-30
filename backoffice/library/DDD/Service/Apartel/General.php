<?php

namespace DDD\Service\Apartel;

use DDD\Service\ServiceBase;
use DDD\Service\Translation;
use Library\ActionLogger\Logger as ActionLogger;
use Library\ActionLogger\Logger;
use Library\Constants\TextConstants;
use Library\Upload\Images;
use FileManager\Constant\DirectoryStructure;
use FileManager\Service\Utils as FileUtils;
use Zend\Db\Sql\Where;

class General extends ServiceBase
{
    const APARTEL = 'apartel';
    const APARTEL_TYPE = 'apartel_type';
    const RATE = 'rate';

    const APARTEL_STATUS_INACTIVE   = 0;
    const APARTEL_STATUS_ACTIVE     = 1;

    public static $apartelStatuses = [
        self::APARTEL_STATUS_INACTIVE => 'Inactive',
        self::APARTEL_STATUS_ACTIVE   => 'Active',
    ];

    /**
     * @param $groupId
     * @return array
     */
    public function createApartel($groupId)
    {
        /**
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $groupsManagementDao
         * @var \DDD\Dao\Apartel\General $generalApartelDao
         * @var \Library\ActionLogger\Logger $actionLogger
         * @var \DDD\Dao\Textline\Apartment $productTextlineDao
         * @var \DDD\Dao\Apartel\Details $apartelDetailsDao
         */
        $groupsManagementDao    = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $generalApartelDao      = $this->getServiceLocator()->get('dao_apartel_general');
        $productTextlineDao     = $this->getServiceLocator()->get('dao_textline_apartment');
        $apartelDetailsDao      = $this->getServiceLocator()->get('dao_apartel_details');

        // save apartel usage
        $groupsManagementDao->save(['usage_apartel' => 1], ['id' => $groupId]);
        $apartmentGroup = $groupsManagementDao->getRowById($groupId);

        // save apartel
        $apartelId = $generalApartelDao->save([
            'apartment_group_id' => $groupId
        ]);

        $productTextlineDao->insert([
            'entity_id'     => $apartelId,
            'entity_type'   => Translation::PRODUCT_TEXTLINE_TYPE_APARTEL_CONTENT,
        ]);
        $contentTxtId = $productTextlineDao->getLastInsertValue();

        $productTextlineDao->insert([
            'entity_id'     => $apartelId,
            'entity_type'   => Translation::PRODUCT_TEXTLINE_TYPE_APARTEL_MOTO,
        ]);
        $motoTxtId = $productTextlineDao->getLastInsertValue();

        $productTextlineDao->insert([
            'entity_id'     => $apartelId,
            'entity_type'   => Translation::PRODUCT_TEXTLINE_TYPE_APARTEL_META_DESCRIPTION,
        ]);
        $metaDescriptionTxtId = $productTextlineDao->getLastInsertValue();

        $generalApartelDao->update(
            ['slug' => self::generateApartelSlug($apartmentGroup->getName())],
            ['id'   => $apartelId]
        );

        $apartelDetailsDao->insert([
            'apartel_id'                    => $apartelId,
            'content_textline_id'           => $contentTxtId,
            'moto_textline_id'              => $motoTxtId,
            'meta_description_textline_id'  => $metaDescriptionTxtId
        ]);

        // set log
        $actionLogger = $this->getServiceLocator()->get('ActionLogger');
        $actionLogger->save(
            ActionLogger::MODULE_APARTMENT_GROUPS,
            $groupId,
            ActionLogger::ACTION_APARTMENT_GROUPS_USAGE,
            'Create Apartel'
        );

        return [
            'status' => 'success',
            'msg' => TextConstants::SUCCESS_CREATED,
            'apartelId' => $apartelId,
        ];
    }

    /**
     * @param $groupId
     * @return array
     */
    public function deactivateApartel($groupId)
    {
        /**
         * @var \Library\ActionLogger\Logger $actionLogger
         */
        $actionLogger = $this->getServiceLocator()->get('ActionLogger');

        try {
            /**
             * @var \DDD\Dao\Apartel\General $apartelGeneralDao
             */
            $apartelGeneralDao = $this->getServiceLocator()->get('dao_apartel_general');

            // get apartel data
            $apartelData = $apartelGeneralDao->fetchOne(['apartment_group_id' => $groupId], ['id']);

            if ($apartelData) {
                /**
                 * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $groupsManagementDao
                 * @var \DDD\Dao\Apartel\Inventory $apartelInventoryDao
                 * @var \DDD\Dao\Apartel\OTADistribution $apartelOtaDistributionDao
                 * @var \DDD\Dao\Apartel\Rate $apartelRateDao
                 * @var \DDD\Dao\Apartel\RelTypeApartment $apartelTypesRelDao
                 * @var \DDD\Dao\Apartel\Type $apartelTypeDao
                 * @var \DDD\Dao\Apartel\Details $apartelDetailsDao
                 * @var \DDD\Dao\Textline\Group $apartmentGroupTextlineDao
                 */
                $groupsManagementDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
                $apartelInventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');
                $apartelOtaDistributionDao = $this->getServiceLocator()->get('dao_apartel_ota_distribution');
                $apartelRateDao = $this->getServiceLocator()->get('dao_apartel_rate');
                $apartelTypesRelDao = $this->getServiceLocator()->get('dao_apartel_rel_type_apartment');
                $apartelTypeDao = $this->getServiceLocator()->get('dao_apartel_type');
                $apartelDetailsDao = $this->getServiceLocator()->get('dao_apartel_details');
                $apartmentGroupTextlineDao = $this->getServiceLocator()->get('dao_textline_group');

                $apartelImagesPath = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_IMAGES_ROOT
                    . DirectoryStructure::FS_IMAGES_APARTEL_BG_IMAGE
                    . $apartelData->getId();

                // remove apartel images
                if (is_writable($apartelImagesPath)) {
                    FileUtils::deleteDir($apartelImagesPath);
                }

                // delete apartel inventory
                $apartelInventoryDao->delete([
                    'apartel_id' => $apartelData->getId()
                ]);

                // delete apartel ota distribution data
                $apartelOtaDistributionDao->delete([
                    'apartel_id' => $apartelData->getId()
                ]);

                // delete apartel rates
                $apartelRateDao->delete([
                    'apartel_id' => $apartelData->getId()
                ]);

                $apartelTypeRels = $apartelTypeDao->fetchAll(
                    ['apartel_id' => $apartelData->getId()],
                    ['id']
                );

                // delete apartel type rel to apartments
                foreach ($apartelTypeRels as $apartelTypeRel) {
                    $apartelTypesRelDao->delete([
                        'apartel_type_id' => $apartelTypeRel->getId()
                    ]);
                }

                // delete apartel types
                $apartelTypeDao->delete([
                    'apartel_id' => $apartelData->getId()
                ]);

                // get apartel textlines id's
                $apartelTextiles = $apartelDetailsDao->fetchOne(
                    ['apartel_id' => $apartelData->getId()],
                    [
                        'content_textline_id',
                        'moto_textline_id',
                        'meta_description_textline_id'
                    ]
                );

                // delete apartel details
                $apartelDetailsDao->delete([
                    'apartel_id' => $apartelData->getId()
                ]);

                $apartelTextlineWhere = new Where();
                $apartelTextlineWhere->in('id',
                    [
                        $apartelTextiles->getContentTextlineId(),
                        $apartelTextiles->getMotoTextlineId(),
                        $apartelTextiles->getMetaDescriptionTextlineId()
                    ]);

                // delete apartel textlines
                $apartmentGroupTextlineDao->delete($apartelTextlineWhere);

                // delete apartel general data
                $apartelGeneralDao->delete([
                    'id' => $apartelData->getId()
                ]);

                // save apartel usage
                $groupsManagementDao->save(
                    ['usage_apartel' => 0],
                    ['id' => $groupId]
                );
            }

            $actionLogger->save(
                ActionLogger::MODULE_APARTMENT_GROUPS,
                $groupId,
                ActionLogger::ACTION_APARTMENT_GROUPS_USAGE,
                'Delete Apartel'
            );

            return [
                'status' => 'success',
                'msg' => TextConstants::SUCCESS_DEACTIVATE,
            ];
        } catch (\Exception $e) {
            $this->gr2logException($e, "Apartel wasn't deactivated", [
                'apartment_group_id' => $groupId
            ]);

            return [
                'status' => 'error',
                'msg' => TextConstants::SERVER_ERROR,
            ];
        }
    }

    public function getGeneralViewData($apartelId)
    {
        /**
         * @var \DDD\Service\Apartment\Review $apartmentReviewService
         * @var \DDD\Dao\Apartel\Type $apartelTypeDao
         * @var \DDD\Dao\Apartel\General $apartelGeneralDao
         */
        $apartelTypeDao         = $this->getServiceLocator()->get('dao_apartel_type');
        $apartmentReviewService = $this->getServiceLocator()->get('service_apartment_review');
        $apartelGeneralDao      = $this->getServiceLocator()->get('dao_apartel_general');

        // get apartment list
        $apartmentList                      = $apartelTypeDao->getAllApartmentForApartel($apartelId);
        $apartmentList                      = iterator_to_array($apartmentList);

        $apartmentCount                     = count($apartmentList);
        $allApartmentScoreLastTwoYearsSum   = 0;
        $allApartmentScoreLastThreeMonthSum = 0;

        $notZeroReviewCountsFor3Months = 0;
        $notZeroReviewCountsFor2Years = 0;
        foreach ($apartmentList as $apartment) {
            $apartmentScores = $apartmentReviewService->getApartmentReviewScore($apartment['id']);
            if ($apartmentScores['scoreLastTwoYears']) {
                $allApartmentScoreLastTwoYearsSum += $apartmentScores['scoreLastTwoYears'];
                $notZeroReviewCountsFor2Years++;
            }
            if ($apartmentScores['scoreLastThreeMonth']) {
                $allApartmentScoreLastThreeMonthSum += $apartmentScores['scoreLastThreeMonth'];
                $notZeroReviewCountsFor3Months++;
            }

        }

        $reviews = $apartelGeneralDao->getPopularReviews($apartelId);

        $apartelGeneralData = $apartelGeneralDao->getApartelById($apartelId, false);

        return [
            'scoreLastTwoYears'  => $notZeroReviewCountsFor2Years ? round($allApartmentScoreLastTwoYearsSum/$notZeroReviewCountsFor2Years, 2) : 0,
            'scoreLastThreeMont' => $notZeroReviewCountsFor3Months ? round($allApartmentScoreLastThreeMonthSum/$notZeroReviewCountsFor3Months, 2) : 0,
            'reviews'            => $reviews,
            'apartelGeneralData' => $apartelGeneralData
        ];
    }

    /**
     * @param \Zend\Stdlib\Parameters $data
     * @param array $file
     * @return bool
     */
    public function saveApartel($data, $file = [])
    {
        try {
            /** @var \DDD\Dao\Apartel\General $apartelGeneralDao */
            $apartelGeneralDao  = $this->getServiceLocator()->get('dao_apartel_general');
            /** @var \DDD\Dao\Apartel\Details $apartelDetailsDao */
            $apartelDetailsDao  = $this->getServiceLocator()->get('dao_apartel_details');
            /** @var \DDD\Dao\Apartel\Type $typeDao */
            $typeDao            = $this->getServiceLocator()->get('dao_apartel_type');
            /** @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService */
            $syncService        = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
            /** @var \DDD\Dao\Apartel\Inventory $inventoryDao */
            $inventoryDao       = $this->getServiceLocator()->get('dao_apartel_inventory');
            /** @var Logger $logger */
            $logger = $this->getServiceLocator()->get('ActionLogger');

            $apartelGeneralData = $apartelGeneralDao->getApartelById($data['id'], false);

            if ($apartelGeneralData === false) {
                throw new \Exception('Cannot find Apartel by Id');
            }

            $apartelDetailsData = $apartelDetailsDao->getApartelDetailsById($data['id']);

            if ($apartelGeneralData->getStatus() != $data['status']) {
                $apartelGeneralDao->update(
                    ['status' => (int)$data['status']],
                    ['id' => $data['id']]
                );

                $logger->save(
                    Logger::MODULE_APARTEL,
                    $data['id'],
                    Logger::ACTION_APARTEL_STATUS,
                    'Changed Status to ' . self::$apartelStatuses[$data['status']]
                );
            }

            if (   $apartelDetailsData
                && ($apartelDetailsData->getDefaultAvailability() != $data['default_availability'])
                && $data['default_availability']
            ) {

                $roomTypeList = $typeDao->getAllSyncRoomTypes($data['id']);
                $dates        = $inventoryDao->getMinMaxDate();
                $dateMin      = $dates['min_date'];
                $dateMax      = $dates['max_date'];

                foreach ($roomTypeList as $roomType) {
                    $roomTypeId = $roomType['id'];
                    $inventoryDao->setApartelAvailabilityByRoomType($roomTypeId);
                    $syncService->push($roomTypeId, $dateMin, $dateMax, [], $syncService::ENTITY_TYPE_APARTEL);
                }
            }

            $apartelDetailsDao->update(
                ['default_availability' => $data['default_availability']],
                ['apartel_id' => $data['id']]
            );

            if ($apartelDetailsData
                && ($apartelDetailsData->getDefaultAvailability() != $data['default_availability'])
            ) {
                $logger->save(
                    Logger::MODULE_APARTEL,
                    $data['id'],
                    Logger::ACTION_APARTEL_MAXIMIZED,
                    ($data['default_availability'] ? 'Checked' : 'Unchecked') . ' <b>Take Maximized</b> option'
                );
            }

            return true;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot save Apartel general data', $data);
        }

        return false;
    }

    /**
     * @param string $name
     * @return string | false
     */
    public static function generateApartelSlug($name)
    {
        if (empty($name)) {
            return false;
        }

        // Replace all spaces with dashes
        $slug = str_replace(' ', '-', trim(strtolower($name)));
        // Remove all characters except alphanumeric and dash
        $slug = preg_replace('/[^a-zA-Z0-9-]+/', '', $slug);
        // Combine nested dashes into one.
        $slug = preg_replace('/[-]+/', '-', $slug);

        return $slug;
    }

    /**
     * @param int $typeId
     * @return bool
     */
    public function getDefaultAvailabilityByTypeId($typeId)
    {
      /**
       * @var \DDD\Dao\Apartel\Details $apartelDetailsDao
       */
        $apartelDetailsDao  = $this->getServiceLocator()->get('dao_apartel_details');

        $apartelInfo = $apartelDetailsDao->getDefaultAvailabilityByTypeId($typeId);

        $defaultAvailability = 0;
        if ($apartelInfo) {
            $defaultAvailability = $apartelInfo->getDefaultAvailability();
        }
        return $defaultAvailability;
    }

    /**
     * @param $apartelId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getApartelFiscals($apartelId)
    {
        /**
         * @var \DDD\Dao\Apartel\Fiscal $fiscalDao
         */
        $fiscalDao = $this->getServiceLocator()->get('dao_apartel_fiscal');
        $fiscals = $fiscalDao->getApartelFiscals($apartelId);
        return $fiscals;
    }

    /**
     * @param $data
     * @return int
     * @throws \Exception
     */
    public function saveFiscal($data)
    {
        /**
         * @var \DDD\Dao\Apartel\Fiscal $fiscalDao
         */
        $fiscalDao = $this->getServiceLocator()->get('dao_apartel_fiscal');

        if (!isset($data['apartel_id']) ||
            !isset($data['fiscal_name']) ||
            !isset($data['partner']) ||
            !isset($data['channel_partner_id']) ||
            !$data['fiscal_name'] ||
            !$data['apartel_id'] ||
            !$data['partner'] ||
            !$data['channel_partner_id']
        ) {
            throw new \Exception(TextConstants::BAD_REQUEST);
        }

        $params = [
            'name' => $data['fiscal_name'],
            'partner_id' => $data['partner'],
            'channel_partner_id' => $data['channel_partner_id'],
            'apartel_id' => $data['apartel_id']
        ];

        try {

            if ($data['fiscal_id']) {// edit mode
                $fiscalId = $data['fiscal_id'];
                $fiscalDao->save($params, ['id' => $fiscalId]);
            } else { // insert mode
                $fiscalId = $fiscalDao->save($params);
            }

        } catch (\Exception $ex) {
            throw new \Exception(TextConstants::ERROR);
        }

        return $fiscalId;
    }

    /**
     * @param $fiscalId
     * @return int
     */
    public function deleteFiscal($fiscalId)
    {
        /**
         * @var \DDD\Dao\Apartel\Fiscal $fiscalDao
         */
        $fiscalDao = $this->getServiceLocator()->get('dao_apartel_fiscal');
        return $fiscalDao->delete(['id' => $fiscalId]);
    }
}
