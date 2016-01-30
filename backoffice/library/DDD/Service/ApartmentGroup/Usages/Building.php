<?php

namespace DDD\Service\ApartmentGroup\Usages;
use DDD\Service\Translation;
use FileManager\Constant\DirectoryStructure;
use Library\Constants\TextConstants;
use Zend\Http\Request;
use Library\Upload\Files;

/**
 * Class Building
 * @package DDD\Service\ApartmentGroup\Usages
 *
 * @author Tigran Petrosyan
 */
class Building extends Base
{
    const ATTACHMENT_SIZE        = 24;
    const KI_PAGE_TYPE_DIRECT    = 1;
    const KI_PAGE_TYPE_RECEPTION = 2;

    public function buildingSave($data, $buildingId, $global, $request)
    {
        /** @var \DDD\Dao\ApartmentGroup\ApartmentGroup $accGroupsManagementDao */
        $accGroupsManagementDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        /** @var \DDD\Dao\ApartmentGroup\BuildingDetails $buildingDetailsDao */
        $buildingDetailsDao     = $this->getServiceLocator()->get('dao_apartment_group_building_details');
        $apartmentGroupData     = $accGroupsManagementDao->getRowById($buildingId);

        if (!$global) {
            /** @var \Library\Authentication\BackofficeAuthenticationService $auth */
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            if ($apartmentGroupData->getGroupManagerId() == $auth->getIdentity()->id) {
                $global = true;
            }
        }

        if ($global) {
            /** @var \DDD\Dao\ApartmentGroup\FacilityItems $buildingFacilityItemsDao */
            $buildingFacilityItemsDao = $this->getServiceLocator()->get('dao_building_facility_items');

            if ($apartmentGroupData->isBuilding()) {
                $buildingFacilityItemsDao->deleteWhere(['building_id' => $buildingId]);

                if (isset($data['facilities'])) {
                    foreach ($data['facilities'] as $facilityId => $isSet) {
                        if ($isSet) {
                            $buildingFacilityItemsDao->save([
                                'facility_id' => $facilityId,
                                'building_id' => $buildingId
                            ]);
                        }
                    }
                }

                if ($data['delete_attachment']) {
                    $this->removeMap($buildingId);
                }

                if ($data['key_instruction_page_type'] == self::KI_PAGE_TYPE_DIRECT) {
                    $data['assigned_office_id'] = 0;
                }

                $buildingDetailsDao->save(
                    [
                        'building_phone'     => $data['building_phone'],
                        'ki_page_type'       => $data['key_instruction_page_type'],
                        'assigned_office_id' => $data['assigned_office_id'],
                    ],
                    ['apartment_group_id' => $buildingId]
                );

            }
            $this->uploadFile($request, $buildingId);
        }

        return $buildingId;
    }

    public function getBuildingListForSelectize($selectedId = 0)
    {
        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        return $apartmentGroupDao->getBuildingListForSelectize($selectedId);
    }


    /**
     * @param Request $request
     * @param int $buildingId
     * @return bool
     */
    public function uploadFile($request, $buildingId)
    {
        try {
            /** @var \DDD\Dao\ApartmentGroup\BuildingDetails $buildingDetailsDao */
            $buildingDetailsDao     = $this->getServiceLocator()->get('dao_apartment_group_building_details');

            $files = $request->getFiles();
            $file = $files['map_attachment'];
            $attachmentExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

            // file attached
            if ($file['error'] !== 4) {
                if ($file['error'] !== 0) {
                    throw new \Exception('File upload failed.');
                }

                if ($file['size'] > self::ATTACHMENT_SIZE * 1024 * 1024) {
                    throw new \Exception('File size is too big.');
                }

                if (in_array($attachmentExtension, ['php', 'phtml', 'html', 'js'])) {
                    throw new \Exception('Invalid file format.');
                }

                $folderPath = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_IMAGES_ROOT
                    . DirectoryStructure::FS_IMAGES_BUILDING
                    . $buildingId . '/map';

                if (!is_dir($folderPath)) {
                    if (!mkdir($folderPath, 0775, true)) {
                        throw new \Exception('Upload failed. Can\'t create directory.');
                    }
                }

                $oldData = $buildingDetailsDao->fetchOne(['apartment_group_id' => $buildingId], ['map_attachment']);
                $filename = 'ki_map_' . $buildingId . '_' . time();
                $filename = $filename . '.' . $attachmentExtension;
                $fullPath = $folderPath . '/' . $filename;

                // remove old uploaded file
                if ($oldData['map_attachment']) {
                    $oldFile = $folderPath . '/' . $oldData['map_attachment'];
                    @unlink($oldFile);
                }

                Files::moveFile($file['tmp_name'], $fullPath);
                $buildingDetailsDao->save(['map_attachment' => $filename], ['apartment_group_id' => $buildingId]);
                return true;
            }
        } catch (\Exception $ex) {
            return false;
        }

        return false;
    }

    /**
     * @param int $buildingId
     * @return bool
     */
    public function removeMap($buildingId)
    {
        /** @var \DDD\Dao\ApartmentGroup\BuildingDetails $buildingDetailsDao */
        $buildingDetailsDao = $this->getServiceLocator()->get('dao_apartment_group_building_details');
        $oldData            = $buildingDetailsDao->fetchOne(['apartment_group_id' => $buildingId]);

        if ($oldData && $oldData['map_attachment']) {
            $file = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_BUILDING
                . $buildingId . '/map/' . $oldData['map_attachment'];
            if (is_readable($file) && @unlink($file)) {
                $buildingDetailsDao->save(['map_attachment' => ''], ['apartment_group_id' => $buildingId]);

                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public static function getApartmentKeyInstructionPageTypes()
    {
        return [
            self::KI_PAGE_TYPE_DIRECT       => 'Direct',
            self::KI_PAGE_TYPE_RECEPTION    => 'Reception',
        ];
    }

    /**
     * @param $data
     * @return int
     * @throws \Exception
     */
    public function saveSection($data)
    {
        /**
         * @var \DDD\Dao\ApartmentGroup\BuildingSections $buildingSectionsDao
         * @var \DDD\Dao\ApartmentGroup\BuildingLots $buildingLotsDao
         * @var \DDD\Dao\Textline\Apartment $productTextlineDao
         */
        $buildingSectionsDao = $this->getServiceLocator()->get('dao_apartment_group_building_sections');
        $buildingLotsDao = $this->getServiceLocator()->get('dao_apartment_group_building_lots');

        if (!isset($data['section_id']) ||
            !isset($data['section_name']) ||
            !isset($data['lock']) || !$data['lock'] ||
            !$data['section_name'] ||
            !$data['building_id']
        ) {
            throw new \Exception(TextConstants::BAD_REQUEST);
        }

        $params = [
            'name' => $data['section_name'],
            'lock_id' => $data['lock'],
            'building_id' => $data['building_id']
        ];

        try {

            $buildingLotsDao->beginTransaction();

            if ($data['section_id']) {// edit mode
                $sectionId = $data['section_id'];
                $buildingSectionsDao->save($params, ['id' => $sectionId]);

                // delete lots
                $buildingLotsDao->delete(['building_section_id' => $sectionId]);

            } else { // insert mode
                $sectionId = $buildingSectionsDao->save($params);

                // insert KI textline
                $productTextlineDao = $this->getServiceLocator()->get('dao_textline_apartment');

                $apartmentEntryTextLine = [
                    'entity_id'   => $sectionId,
                    'entity_type' => Translation::PRODUCT_TEXTLINE_TYPE_BUILDING_SECTION_APARTMENT_ENTRY,
                    'type'        => Translation::PRODUCT_TYPE_BUILDING
                ];

                $apartmentEntryTextlineId = $productTextlineDao->save($apartmentEntryTextLine);

                $buildingSectionsDao->save([
                    'apartment_entry_textline_id' => $apartmentEntryTextlineId
                ], [
                    'id' => (int)$sectionId
                ]);
            }

            // insert lots
            if (isset($data['lots']) && !empty($data['lots'])) {
                foreach ($data['lots'] as $lot) {
                    $buildingLotsDao->save([
                        'lot_id' => $lot,
                        'building_section_id' => $sectionId
                    ]);
                }
            }

            $buildingLotsDao->commitTransaction();

        } catch (\Exception $ex) {
            $buildingLotsDao->rollbackTransaction();
            throw new \Exception(TextConstants::ERROR);
        }

        return $sectionId;
    }

    /**
     * @param $buildingId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getSectionData($buildingId)
    {
        /**
         * @var \DDD\Dao\ApartmentGroup\BuildingSections $buildingSectionsDao
         */
        $buildingSectionsDao = $this->getServiceLocator()->get('dao_apartment_group_building_sections');
        $sections = $buildingSectionsDao->getSectionData($buildingId);
        return $sections;
    }

    /**
     * @param $sectionId
     * @return int
     */
    public function deleteSection($sectionId)
    {
        /**
         * @var \DDD\Dao\ApartmentGroup\BuildingSections $buildingSectionsDao
         */
        $buildingSectionsDao = $this->getServiceLocator()->get('dao_apartment_group_building_sections');
        return $buildingSectionsDao->delete(['id' => $sectionId]);
    }
}
