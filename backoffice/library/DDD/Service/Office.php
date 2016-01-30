<?php

namespace DDD\Service;

use DDD\Dao\Office\OfficeManager as OfficeDAO;
use DDD\Dao\Office\OfficeSection as OfficeSectionDAO;
use DDD\Dao\User\UserManager as UserDAO;
use DDD\Dao\User\UserManager;
use FileManager\Constant\DirectoryStructure;
use DDD\Service\Contacts\Contact;
use Zend\Form\Form;
use Library\Upload\Files;

class Office extends ServiceBase
{
    const STATUS_DISABLE    = 1;
    const STATUS_ENABLE     = 0;

    /**
     * @param $id
     * @param $status
     * @return int
     */
    public function changeOfficeStatus($id, $status)
    {
        /**
         * @var OfficeDAO $officeDao
         */
        $officeDao = $this->getServiceLocator()->get('dao_office_office_manager');

        return $officeDao->save(
            ['disable' => $status],
            ['id' => $id]
        );
    }

    /**
     * @param $officeId
     * @return int
     */
    public function getOfficeStaffCount($officeId)
    {
        /**
         * @var UserDAO $userDao
         */
        $userDao = $this->getServiceLocator()->get('dao_user_user_manager');

        $result = $userDao->fetchAll([
            'reporting_office_id' => $officeId,
            'disabled' => 0,
            'system' => 0,
        ], ['id']);

        return $result->count();
    }

    public function getOfficeDetailsById($id, $onlyActive = true)
    {
        $officeDao = $this->getServiceLocator()->get('dao_office_office_manager');
        return $officeDao->getOfficeDetailsById($id, $onlyActive);
    }

    /**
     * @param bool $inactiveIncluded
     * @return array
     *
     * @author Tigran Petrosyan
     */
    public function getOfficeSelectOptions($inactiveIncluded = false)
    {
        /**
         * @var OfficeDAO $officeDao
         * @var \DDD\Domain\Office\OfficeManager[]|\ArrayObject $offices
         */
        $officeDao = $this->getServiceLocator()->get('dao_office_office_manager');

        $offices = $officeDao->fetchAll(
            ['disable' => $inactiveIncluded],
            ['id', 'name'],
            ['name' => 'ASC']
        );

        $officeOptions = ['-- Choose Office --'];

        foreach ($offices as $office) {
            $officeOptions[$office->getId()] = $office->getName();
        }

        return $officeOptions;
    }

    public function getOfficeList($id = null)
    {
        return $this->getOfficeManagerDao()->getOfficeList($id);
    }

    public function getOfficesAndSections()
    {
        $officeSectionDao = $this->getOfficeSectionDao();
        $sections = $officeSectionDao->getAllSections();
        $sectionList = [];

        if ($sections->count()) {
            foreach ($sections as $section) {
                array_push($sectionList, $section);
            }
        }

        return $sectionList;
    }

    /**
     * @param $start
     * @param $limit
     * @param $sortCol
     * @param $sortDir
     * @param $search
     * @param $all
     * @return \DDD\Domain\Office\OfficeManager[]
     */
    public function getOfficeListDetail($start, $limit, $sortCol, $sortDir, $search, $all)
    {
        return $this->getOfficeManagerDao()->getOfficeListDetail($start, $limit, $sortCol, $sortDir, $search, $all);
    }

    public function officeCount($search, $all)
    {
        return $this->getOfficeManagerDao()->getOfficeCount($search, $all);
    }

    /**
     * @return bool|\Library\Registry\Registry
     */
    public function getEditOfficeFormOptions()
    {
        /**
         * @var User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        $allActiveHumanUsers = $userService->getPeopleList();

        $this->registry->set('ginosiksList', $allActiveHumanUsers);

        return $this->registry;
    }

    public function getData($id)
    {
        $userDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $userService = $this->getServiceLocator()->get('service_user');
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

        $sections = $this->getOfficeSectionDao()->fetchAll(['office_id' => $id]);
        $office = $this->getOfficeManagerDao()->fetchOne(['id' => $id]);

        if (!$office) {
            return false;
        }

        $staffs = $userDao->fetchAll([
            'reporting_office_id' => $id,
            'disabled' => 0,
            'system' => 0,
        ], ['id']);

        $staffsId = [];
        foreach ($staffs as $staff) {
            array_push($staffsId, $staff->getId());
        }

        $managersInfo = [];
        array_push($managersInfo, [
            'id'     => 'office_manager_id',
            'userId' => $office->getOfficeManagerId(),
        ]);

        array_push($managersInfo, [
            'id'     => 'it_manager_id',
            'userId' => $office->getItManagerId(),
        ]);

        array_push($managersInfo, [
            'id'     => 'finance_manager_id',
            'userId' => $office->getFinanceManagerId(),
        ]);

        $activeUsers = $userService->getPeopleList();

        $users = [];
        foreach ($activeUsers as $user) {
            $users[$user['id']] = $user['firstname'] . ' ' . $user['lastname'];
        }

        $finance_manager_id = [];
        $office_manager_id  = [];
        $it_manager_id      = [];
        $disStaff           = [];

        foreach ($staffsId as $staffId) {
            if (!array_key_exists($staffId, $users)) {
                $disabledUser = $userManagerDao->fetchOne(['id' => $staffId]);

                if ($disabledUser) {
                    array_push($disStaff, [
                        $disabledUser->getId() => $disabledUser->getFirstName() . ' ' . $disabledUser->getLastName()
                    ]);
                 }
            }
        }

        return [
            'office' => $office,
            'sections' => $sections,
            'managersInfo' => $managersInfo,
            'staffsId' => $staffsId,
            'disabledOM' => $office_manager_id,
            'disabledIM' => $it_manager_id,
            'disabledFM' => $finance_manager_id,
            'disabledStaff' => $disStaff,
        ];
    }

    public function officeSave($data, $files, $id, $global)
    {
        /**
         * @var UserManager $userManager
         */
        $officeManagerDao = $this->getOfficeManagerDao();
        $officeSectionManagerDao = $this->getOfficeSectionDao();

        $userManager = $this->getServiceLocator()->get('dao_user_user_manager');
        $data = (array)$data;
        $staffsId = [];

        if ($global) {
            $officeData = [
                'name'               => $data['name'],
                'description'        => $data['description'],
                'address'            => $data['address'],
                'office_manager_id'  => !empty($data['office_manager_id']) ? $data['office_manager_id'] : null,
                'it_manager_id'      => !empty($data['it_manager_id']) ? $data['it_manager_id'] : null,
                'finance_manager_id' => !empty($data['finance_manager_id']) ? $data['finance_manager_id'] : null,
                'country_id'         => $data['country_id'],
                'city_id'            => $data['city_id'],
                'province_id'        => $data['province_id'],
                'phone'              => $data['phone']
            ];

            if ($id) {
                if ($data['delete_attachment']) {
                    $this->removeAttachment($id);
                }

                $officeData['modified_date'] = date('Y-m-d');
                $officeManagerDao->save($officeData, ['id' => (int)$id]);

                $preStaffs = $userManager->fetchAll(['reporting_office_id' => $id]);

                foreach ($preStaffs as $staffId) {
                    array_push($staffsId, $staffId->getId());
                }

                if (!isset($data['staff']) && !empty($staffsId)) {
                    foreach ($staffsId as $staff) {
                        $userManager->setOfficeUser(0, $staff);
                    }
                } elseif (empty($staffsId) && isset($data['staff'])) {
                    foreach ($data['staff'] as $staff) {
                        $userManager->setOfficeUser($id, $staff);
                    }
                } elseif (isset($data['staff']) && !empty($staffsId)) {
                    $sameStaffs = array_intersect(
                        $data['staff'],
                        $staffsId
                    );

                    $deleteStaffs = array_diff(
                        $staffsId,
                        $sameStaffs
                    );

                    $newStaffs = array_diff(
                        $data['staff'],
                        $sameStaffs
                    );

                    foreach ($newStaffs as $staff) {
                        $userManager->setOfficeUser($id, $staff);
                    }

                    foreach ($deleteStaffs as $staff) {
                        $userManager->setOfficeUser(0, $staff);
                    }
                }

                if (isset($data['section'])) {
                    $officeSectionList = $this->getOfficeSectionsList($id);

                    if (count($data['section'])) {
                        foreach ($data['section'] as $submittedOfficeSectionId => $submittedOfficeSectionName) {
                            if (empty(trim($submittedOfficeSectionName))) {
                                continue;
                            }

                            // If section id found then it is an update else it is a new office section
                            if (array_key_exists($submittedOfficeSectionId, $officeSectionList)) {
                                if (trim($submittedOfficeSectionName) == $officeSectionList[$submittedOfficeSectionId]) {
                                    continue;
                                }

                                // Update name
                                $officeSectionManagerDao->save([
                                    'name' => $submittedOfficeSectionName,
                                ], ['id' => $submittedOfficeSectionId]);
                            } else {
                                // Create office section
                                $officeSectionManagerDao->save([
                                    'name' => $submittedOfficeSectionName,
                                    'office_id' => $id,
                                    'disable' => 0,
                                ]);
                            }
                        }
                    }
                }
            } else {
                $officeData['created_date']  = date('Y-m-d');
                $officeData['modified_date'] = null;

                $id = $officeManagerDao->save($officeData);

                if ($id > 0) {
                    /** @var \DDD\Dao\Textline\Apartment $productTexlineDao */
                    $productTexlineDao = $this->getServiceLocator()->get('dao_textline_apartment');

                    $receptionEntryTextLine = [
                        'entity_id'   => $id,
                        'entity_type' => Translation::PRODUCT_TEXTLINE_TYPE_OFFICE_RECEPTION_ENTRY,
                        'type'        => Translation::PRODUCT_TYPE_OFFICE
                    ];

                    $receptionEntryTextLineId = $productTexlineDao->save($receptionEntryTextLine);
                    $officeManagerDao->save(
                        ['textline_id' => $receptionEntryTextLineId],
                        ['id' => $id]
                    );

                    if (isset($data['staff'])) {
                        foreach ($data['staff'] as $staff) {
                            $userManager->setOfficeUser($id, $staff);
                        }
                    }

                    if (isset($data['office_manager_id'])) {
                        $userManager->setOfficeUser($id, $data['office_manager_id']);
                    }

                    if (isset($data['finance_manager_id'])) {
                        $userManager->setOfficeUser($id, $data['finance_manager_id']);
                    }

                    if (isset($data['it_manager_id'])) {
                        $userManager->setOfficeUser($id, $data['it_manager_id']);
                    }

                    if (isset($data['section'])) {
                        foreach ($data['section'] as $section) {
                            if (strlen($section) > 0) {
                                $officeSectionManagerDao->save([
                                    'name' => $section,
                                    'office_id' => $id,
                                ]);
                            }
                        }

                    }
                }
            }

            if (!empty($files) && !empty($files['map_attachment'])) {

                $file = $files['map_attachment'];

                $attachmentPath = DirectoryStructure::FS_IMAGES_OFFICE . $id;
                $fullAttachmentPath = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_IMAGES_ROOT
                    . $attachmentPath;

                if (!is_dir($fullAttachmentPath)) {
                    mkdir($fullAttachmentPath, 0775, true);
                }

                $oldData = $officeManagerDao->fetchOne(['id' => $id], ['map_attachment']);
                // remove old uploaded file
                if ($oldData->getMapAttachment()) {
                    $oldFile = $fullAttachmentPath . '/' . $oldData->getMapAttachment();
                    @unlink($oldFile);
                }

                $fileExtention = pathinfo($file['name'], PATHINFO_EXTENSION);

                if (!in_array(strtolower($fileExtention), ['jpg', 'jpeg', 'png', 'gif'])) {
                    return false;
                }

                $fileNewName = 'map_attachment.' . $fileExtention;

                Files::moveFile($file['tmp_name'], $fullAttachmentPath . '/' . $fileNewName);
                $officeManagerDao->save(
                    ['map_attachment' => $fileNewName],
                    ['id' => $id]
                );
            }

            return $id;
        }

        return false;
    }

    public function deleteOffice($id)
    {
        $textlineDao = $this->getServiceLocator()->get('dao_textline_universal');
        $userManager = $this->getServiceLocator()->get('dao_user_user_manager');

        $officeInfo = $this->getOfficeManagerDao()->fetchOne(['id' => $id]);

        if ($officeInfo) {
            $textlineId = $officeInfo->getTextlineId();
            $textlineDao->deleteWhere(['id' => $textlineId]);
            $this->getOfficeManagerDao()->deleteWhere(['id' => $id]);
            $this->getOfficeSectionDao()->deleteWhere(['office_id' => $id]);

            $officeUsers = $userManager->fetchAll(['reporting_office_id' => $id]);

            foreach ($officeUsers as $user) {
                $userManager->setOfficeUser(0, $user->getId());
            }
        }

        return true;
    }

    public function checkName($name, $id)
    {
        $officeManagerDao = $this->getOfficeManagerDao();
        return $officeManagerDao->checkName($name, $id);
    }

    /**
     * @param $officeId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getOfficeSections($officeId)
    {
        $officeSectionDao = $this->getOfficeSectionDao();

        return $officeSectionDao->fetchAll(['office_id' => $officeId]);
    }

    /**
     * @param int $officeId
     * @return array
     */
    public function getOfficeSectionsList($officeId)
    {
        $offices = $this->getOfficeSections($officeId);
        $officeList = [];

        if ($offices->count()) {
            foreach ($offices as $office) {
                $officeList[$office->getId()] = [
                    'id' => $office->getId(),
                    'name' => $office->getName(),
                    'disable' => $office->getDisable(),
                ];
            }
        }

        return $officeList;
    }

    /**
     * @param $sectionId
     * @return array|\ArrayObject|null
     */
    public function getSectionById($sectionId)
    {
        $officeSectionDao = $this->getOfficeSectionDao();

        return $officeSectionDao->fetchOne(['id' => $sectionId]);
    }


    /**
     * @param int $sectionId
     * @param int $status
     * @return int
     */
    public function changeOfficeSectionStatus($sectionId, $status)
    {
        $officeSectionDao = $this->getOfficeSectionDao();

        return $officeSectionDao->save(
            ['disable' => $status],
            ['id' => $sectionId]
        );
    }

    /**
     * @return OfficeDAO
     */
    public function getOfficeManagerDao()
    {
        if (!isset($this->_dao_office_manager)) {
            $this->_dao_office_manager = $this->getServiceLocator()->get('dao_office_office_manager');
        }

        return $this->_dao_office_manager;
    }

    /**
     * @return OfficeSectionDAO
     */
    public function getOfficeSectionDao()
    {
        if (!isset($this->_dao_office_section)) {
            $this->_dao_office_section = $this->getServiceLocator()->get('dao_office_office_section');
        }

        return $this->_dao_office_section;
    }

    public function searchOfficeByName($name, $onlyActive = true)
    {
        $officeDao  = $this->getServiceLocator()->get('dao_office_office_manager');
        return $officeDao->searchOfficeByName($name, $onlyActive);
    }

    /**
     * @param int $officeId
     * @return bool
     */
    public function removeAttachment($officeId)
    {
        /** @var \DDD\Dao\Office\OfficeManager $buildingDetailsDao */
        $officeDao = $this->getServiceLocator()->get('dao_office_office_manager');
        $oldData   = $officeDao->fetchOne(['id' => $officeId]);

        if ($oldData && $oldData->getMapAttachment()) {
            $file = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_OFFICE
                . $officeId . '/' . $oldData->getMapAttachment();
            if (is_readable($file) && @unlink($file)) {
                $officeDao->save(['map_attachment' => ''], ['id' => $officeId]);

                return true;
            }
        }

        return false;
    }

    /**
     * @param $searchQuery
     * @return array
     */
    public function searchContacts($searchQuery)
    {
        $officeDao = $this->getServiceLocator()->get('dao_office_office_manager');
        $result = $officeDao->searchContacts($searchQuery);
        $resultArray = [];
        foreach ($result as $row) {
            array_push($resultArray,
                [
                    'id'    => $row->getId() . '_' . Contact::TYPE_OFFICE,
                    'type'  => Contact::TYPE_OFFICE,
                    'label'  => Contact::LABEL_NAME_OFFICE,
                    'labelClass'  => Contact::LABEL_CLASS_OFFICE,
                    'text'  => $row->getNAme(),
                    'info'  =>''
                ]);
        }
        return $resultArray;
    }
}
