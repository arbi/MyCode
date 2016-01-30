<?php

namespace DDD\Service\Document;

use DDD\Service\ServiceBase;
use DDD\Dao\Apartment\DocumentCategory;
use DDD\Service\Team\Usages\Base as TeamUsageService;
use Library\Controller\ControllerBase;
use Library\Constants\Roles;
use Library\Constants\Constants;
use Library\Constants\Objects;
use Library\Constants\DbTables;
use FileManager\Constant\DirectoryStructure;
use Library\ActionLogger\Logger as ActionLogger;

use Zend\Db\Sql\Where;
use Zend\Form\Form;

/**
 * Service class providing methods to work with documents
 * @package core
 * @subpackage core/service
 */
class Document extends ServiceBase
{
    const ENTITY_TYPE_APARTMENT = 1;
    const ENTITY_TYPE_APARTMENT_GROUP = 2;

    const ADD_DOC_SIZE = 50;

    /**
     * @param array $data
     * @return array
     */
    public function getFormOptions($data = [])
    {
        $auth              = $this->getServiceLocator()->get('library_backoffice_auth');
        /** @var \DDD\Service\Team\Usages\Security $teamUsageService */
        $teamUsageService  = $this->getServiceLocator()->get('service_team_usages_security');
        /** @var \DDD\Dao\Finance\Supplier $supplierDao */
        $supplierDao = $this->getServiceLocator()->get('dao_finance_supplier');

        /** @var \DDD\Dao\Document\Category $documentTypeDao */
        $documentTypeDao = $this->getServiceLocator()->get('dao_document_category');
        /** @var \DDD\Domain\Document\Type $list */
        $list            = $documentTypeDao->getList();
        $params['list']  = $list;

        $hasSecurityAccess = false;
        if ($auth->hasRole(Roles::ROLE_DOCUMENTS_MANAGEMENT_GLOBAL)) {
            $hasSecurityAccess = true;
        }

        $securedTeamListsArray = [];
        if ($hasSecurityAccess) {
            $securedTeamLists = $teamUsageService->getTeamsByUsage(TeamUsageService::TEAM_USAGE_SECURITY);
            foreach ($securedTeamLists as $row) {
                $securedTeamListsArray[$row->getId()] = $row->getName();
            }

        } else {
            $teamSecurityLists = $teamUsageService->getUserSecuredTeams($auth->getIdentity()->id);
            if ($teamSecurityLists->count() && !$hasSecurityAccess) {
                foreach ($teamSecurityLists as $row) {
                    $securedTeamListsArray[$row->getId()] = $row->getName();
                }
            }
        }

        $params['security_level'] = $securedTeamListsArray;

        if (!empty($data['supplier_id'])) {
            $params['suppliers'] = $supplierDao->getForSelect($data['supplier_id']);
        } else {
            $params['suppliers'] = $supplierDao->getAllSuppliers();
        }
        return $params;
    }

    /**
     * @param int $docId
     * @return \DDD\Domain\Document\Document
     */
    public function getData($docId) {
        /* @var $documentDao \DDD\Dao\Document\Document */
        $documentDao = $this->getServiceLocator()->get('dao_document_document');
        $documentData = $documentDao->getDocumentDataById($docId);

        return $documentData;
    }

    public function addDownloadButton($id, $url, Form $form) {
        if ($url) {
            $downloadUrl = '/documents/download/' . $id;
            $removeUrl   = '/documents/delete-attachment/' . $id;

            $form->add(
                [
                    'name' => 'download',
                    'type' => 'Zend\Form\Element\Button',
                    'attributes' => [
                        'value' => $downloadUrl,
                        'id'    => 'download-attachment',
                        'class' =>'btn btn-info btn-large pull-left self-submitter state hidden-file-input'
                    ],
                    'options' => [
                        'label'         => 'Download Attachment',
                        'download-icon' => 'icon-download-alt icon-white',
                        'remove-icon'   => 'icon-remove icon-white',
                        'remove-url'    => $removeUrl
                    ],
                ],
                [
                    'name'     => 'download',
                    'priority' => 9
                ]
            );
        }
    }

    /**
     * @param array $data
     * @return int
     */
    public function documentSave($data)
    {
        /** @var \DDD\Dao\Document\Document $documentDao */
        $documentDao = $this->getServiceLocator()->get('dao_document_document');
        $auth        = $this->getServiceLocator()->get('library_backoffice_auth');
        $authId      = $auth->getIdentity()->id;

        $params = [
            'type_id'          => (int) $data['category'],
            'entity_id'        => (int) $data['entity_id'],
            'entity_type'      => (int) $data['entity_type'],
            'description'      => $data['description'],
            'username'         => $data['username'],
            'password'         => $data['password'],
            'account_number'   => $data['account_number'],
            'account_holder'   => $data['account_holder'],
            'security_level'   => $data['security_level'],
            'supplier_id'      => $data['supplier_id'],
            'url'              => $data['url'],
            'valid_from'       => ($data['valid_from'] == '') ? NULL : date("Y-m-d", strtotime($data['valid_from'])),
            'valid_to'         => ($data['valid_to'] == '') ? NULL : date("Y-m-d", strtotime($data['valid_to'])),
            'signatory_id'     => (int) $data['signatory_id'],
            'legal_entity_id'  => (int) $data['legal_entity_id'],
            'is_frontier'      => (int) $data['is_frontier'],
            'last_edited_by'   => $authId,
            'last_edited_date' => date('Y-m-d H:i:s')
        ];

        if ((int) $data['edit_id'] > 0) {
            $documentDao->save($params, ['id' => (int) $data['edit_id']]);
            $id = $data['edit_id'];
        } else {
            $params  = array_merge(
                $params,
                [
                    'created_by'   => $authId,
                    'created_date' => date('Y-m-d H:i:s')
                ]
            );

            $id = $documentDao->save($params);
        }

        return $id;
    }

    public function uploadFile($request, $id)
    {
        try {
            $file = $request->getFiles();

            $file = $file['attachment_doc'];


            if (substr($file['name'], -6) == 'tar.gz') {
                $attachmentExtension = 'tar.gz';
            } else {
                $attachmentExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            }

            // file attached
            if ($file['error'] !== 4) {

                if ($file['error'] !== 0) {
                    throw new \Exception('File upload failed.');
                }

                if ($file['size'] > self::ADD_DOC_SIZE * 1024 * 1024) {
                    throw new \Exception('File size is too big.');
                }

                if (in_array($attachmentExtension, ['php', 'phtml', 'html', 'js'])) {
                    throw new \Exception('Invalid file format.');
                }

                /** @var \DDD\Dao\Document\Document $documentDao */
                $documentDao = $this->getServiceLocator()->get('dao_document_document');
                /** @var \DDD\Domain\Document\Document $document */
                $document = $documentDao->fetchOne(['id' => $id]);

                if ($document) {
                    $dateFolder = date('Y/m/j/', strtotime($document->getCreatedDate()));

                    $folderPath = DirectoryStructure::FS_GINOSI_ROOT
                        . DirectoryStructure::FS_UPLOADS_ROOT
                        . DirectoryStructure::FS_UPLOADS_DOCUMENTS
                        . $dateFolder;

                    if (!is_dir($folderPath)) {
                        if (!mkdir($folderPath, 0777, true)) {
                            throw new \Exception('Upload failed. Can\'t create directory.');
                        }
                    }

                    /** @var \DDD\Dao\Document\Category $documentTypeDao */
                    $documentTypeDao = $this->getServiceLocator()->get('dao_document_category');
                    /** @var \DDD\Domain\Document\Type $documentType */
                    $documentType = $documentTypeDao->fetchOne(['id'=>(int)$request->getPost('category')]);

                    $ducumentType = str_replace('/', '', $documentType->getName());
                    $filename = join('_', [date('Y'), time(), $ducumentType, $id]);
                    $filename = str_replace(' ', '_', $filename);
                    $filename = implode('.', [$filename, $attachmentExtension]);
                    $fullPath = implode('/', [$folderPath, $filename]);

                    // remove old uploaded file
                    if (!empty($document->getAttachment())) {
                        $currentFile = $folderPath . '/' . $document->getAttachment();
                        @unlink($currentFile);
                    }

                    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                        throw new \Exception('Cannot copy file.');
                    } else {
                        $documentDao->save(['attachment' => $filename], ['id' => $id]);
                        return true;
                    }
                } else {
                    throw new \Exception('Category name is invalid.');
                }
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

        return false;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteDocument($id){
        /**
         * @var \DDD\Dao\Document\Document $documentDao
         */
        $documentDao = $this->getServiceLocator()->get('dao_document_document');

        $documentData = $documentDao->getDocumentDataById($id);

        if ($documentData) {
            /**
             * @var \Library\ActionLogger\Logger $actionLogger
             */
            $actionLogger = $this->getServiceLocator()->get('ActionLogger');

            $loggerModule = 0;
            switch ($documentData->getEntityType()) {
                case self::ENTITY_TYPE_APARTMENT:
                    $loggerModule = ActionLogger::MODULE_APARTMENT_DOCUMENTS;
                    break;
                case self::ENTITY_TYPE_APARTMENT_GROUP:
                    $loggerModule = ActionLogger::MODULE_APARTMENT_GROUPS;
                    break;
            }

            $loggerMessage = 'Document has been deleted';
            if (!empty($documentData->getTypeName())) {
                $loggerMessage .= ' "' . $documentData->getTypeName() .'"';
            }

            $actionLogger->save(
                $loggerModule,
                $documentData->getEntityId(),
                ActionLogger::ACTION_BLOB,
                $loggerMessage
            );

            $this->removeAttachment($id);

            return $documentDao->delete(['id' => $id]);
        }

        return false;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function removeAttachment($id) {
        /** @var \DDD\Dao\Document\Document $documentDao */
        $documentDao = $this->getServiceLocator()->get('dao_document_document');
        /** @var \DDD\Domain\Document\Document $document */
        $document = $documentDao->fetchOne(['id' => $id]);

        $dateFolder = date('Y/m/j/', strtotime($document->getCreatedDate()));
        $file = DirectoryStructure::FS_GINOSI_ROOT
            . DirectoryStructure::FS_UPLOADS_ROOT
            . DirectoryStructure::FS_UPLOADS_DOCUMENTS
            . $dateFolder . $document->getAttachment();

        if($document->getAttachment()){
            if (is_readable($file)) {
                $documentDao->save(['attachment' => ''], ['id' => $id]);
                if (@unlink($file)) {
                    return true;
                } else {
                    return false;
                }
            }
            return false;
        }
        return true;
    }

    /**
     * @param array $filterParams
     * @return \DDD\Domain\Document\Document[]|\ArrayObject
     */
    public function getDocumentSearchResults($filterParams = [])
    {
        /** @var \Library\Authentication\BackofficeAuthenticationService $auth */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $hasSecurityAccess = false;
        if ($auth->hasRole(Roles::ROLE_DOCUMENTS_MANAGEMENT_GLOBAL)) {
            $hasSecurityAccess = true;
        }

        /** @var \DDD\Service\Team\Usages\Security $teamUsageService */
        $teamUsageService  = $this->getServiceLocator()->get('service_team_usages_security');
        $teamSecurityLists = $teamUsageService->getUserSecuredTeams($auth->getIdentity()->id);

        $securityLevels = [];
        if ($teamSecurityLists->count() && !$hasSecurityAccess) {
            foreach ($teamSecurityLists as $row) {
                array_push($securityLevels, $row->getId());
            }
        }

        if (!$teamSecurityLists->count() && !$hasSecurityAccess) {
            $result = [];
        } else {
            /** @var \DDD\Dao\Document\Document $documentDao */
            $documentDao = $this->getServiceLocator()->get('dao_document_document');

            $where = $this->constructWhereFromFilterParams($filterParams, $securityLevels);
            $result = $documentDao->getDocuments($where);
        }

        return $result;
    }


    /**
     *
     * @param array $filterParams
     * @param bool $testApartments
     * @return Where
     */
    public function constructWhereFromFilterParams($filterParams, $securityLevels = [])
    {
        /* @var $auth \Library\Authentication\BackofficeAuthenticationService */
        $auth            = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasDevTestRole  = $auth->hasRole(Roles::ROLE_DEVELOPMENT_TESTING);

        $documentsTable  = DbTables::TBL_DOCUMENTS;
        $where           = new Where();

        if (!$hasDevTestRole) {
            $where->expression(
                'apartment.id NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')', []
            );
        }

        if (isset($filterParams["validation-range"]) && $filterParams["validation-range"] != '') {
            $tempDatesArray = explode(' - ', $filterParams['validation-range']);
            $validFrom = $tempDatesArray[0];
            $validTo   = $tempDatesArray[1];
            $where->expression(
                'DATE(' . $documentsTable . '.valid_from) >= DATE("' . $validFrom . '") ' .
                'AND DATE(' . $documentsTable .   '.valid_to) <= DATE("' . $validTo   . '") ',
                []
            );
        }

        if (isset($filterParams['createdDate']) && $filterParams['createdDate'] !== '') {
            $createdDate = explode(' - ', $filterParams['createdDate']);

            $where->between($documentsTable . '.created_date', $createdDate['0'].' 00:00:00', $createdDate['1'].' 23:59:59');
        }

        if (!empty($filterParams['supplier_id']) && $filterParams['supplier_id'] != '78') {
            $where->equalTo($documentsTable . '.supplier_id', $filterParams['supplier_id']);
        }

        if (!empty($filterParams['document_type'])) {
            $where->equalTo($documentsTable . '.type_id', $filterParams['document_type']);
        }

        if (isset($filterParams['legal_entity_id']) && ($filterParams['legal_entity_id'] != 0)) {
            $where->equalTo($documentsTable . '.legal_entity_id', $filterParams['legal_entity_id']);
        }
        if (isset($filterParams['signatory_id']) && ($filterParams['signatory_id'] != 0)) {
            $where->equalTo($documentsTable . '.signatory_id', $filterParams['signatory_id']);
        }
        if (!empty($filterParams['author_id'])) {
            $where->equalTo($documentsTable . '.created_by', $filterParams['author_id']);
        }

        if (!empty($filterParams['account_number'])) {
            $where->like($documentsTable . '.account_number', '%' . $filterParams['account_number'] . '%');
        }

        if (!empty($filterParams['entity_id'])) {
            $where->equalTo($documentsTable . '.entity_id', $filterParams['entity_id']);
        }

        if (!empty($filterParams['entity_type'])) {
            $where->equalTo($documentsTable . '.entity_type', $filterParams['entity_type']);
        }

        if (!empty($filterParams['account_holder'])) {
            $where->like($documentsTable . '.account_holder', '%' . $filterParams['account_holder'] . '%');
        }

        if (!empty($filterParams['has_attachment'])) {
            switch ($filterParams['has_attachment']) {
                case 1: $where
                    ->isNotNull($documentsTable . '.attachment')
                    ->notEqualTo($documentsTable . '.attachment', '');
                    break;
                case 2: $where
                    ->NEST
                    ->isNull($documentsTable . '.attachment')
                    ->OR
                    ->equalTo($documentsTable . '.attachment', '')
                    ->UNNEST;
                    break;
            }
        }

        if (isset($filterParams['has_url']) && !empty($filterParams['has_url'])) {
            switch ($filterParams['has_url']) {
                case 1: $where->notEqualTo($documentsTable . '.url', '');
                    break;
                case 2: $where->equalTo($documentsTable . '.url', '');
                    break;
            }
        }

        $hasSecurityAccess = $auth->hasRole(Roles::ROLE_DOCUMENTS_MANAGEMENT_GLOBAL);

        if (isset($securityLevels[0]) && !$hasSecurityAccess) {
            $where->in($documentsTable . '.security_level', $securityLevels);
        }

        return $where;
    }

    /**
     *
     * @param int $apartmentId
     * @return \DDD\Domain\Document\Document[]
     */
    public function getApartmentDocumentsList($apartmentId, $userSecurityTeams)
    {
        /** @var \DDD\Dao\Document\Document $documentDao */
        $documentDao = $this->getServiceLocator()->get('dao_document_document');
        $auth                 = $this->getServiceLocator()->get('library_backoffice_auth');

        $hasSecurityAccess = false;
        if ($auth->hasRole(Roles::ROLE_DOCUMENTS_MANAGEMENT_GLOBAL)) {
            $hasSecurityAccess = true;
        }

        $securityLevels = [];
        if ($userSecurityTeams->count() && !$hasSecurityAccess) {
            foreach ($userSecurityTeams as $row) {
                array_push($securityLevels, $row->getId());
            }
        }

        $documents = $documentDao->getApartmentDocuments($apartmentId, $securityLevels, $hasSecurityAccess);
        return $documents;
    }

    /**
     * Prepare resources needed for product search form
     * @access public
     *
     * @return array
     */
    public function prepareDocumentSearchFormResources()
    {
        /* @var $documentsCategoryDao \DDD\Dao\Document\Category */
        $documentsCategoryDao  = $this->getServiceLocator()->get('dao_document_category');
        $documentsCategoryList = $documentsCategoryDao->getList();

        return [
            'document_types' => $documentsCategoryList
        ];
    }

    public function getAfter60DaysExpiringApartmentDocumentsWithInvolvedManagersList()
    {
        /** @var \DDD\Dao\Document\Document $documentDao */
        $documentDao = $this->getServiceLocator()->get('dao_document_document');
        $result = $documentDao->getAfter60DaysExpiringApartmentDocumentsWithInvolvedManagersList();
        return $result;
    }

    /**
     * @param int $apartmentGroupId
     * @param \DDD\Domain\Team\Team[] $userSecurityTeams
     * @return bool|\Zend\Db\ResultSet\ResultSet|\DDD\Domain\Document\Document
     */
    public function getApartmentGroupDocumentsList($apartmentGroupId, $userSecurityTeams)
    {
        /** @var \DDD\Dao\Document\Document $documentDao */
        $documentDao = $this->getServiceLocator()->get('dao_document_document');
        $auth        = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasSecurityAccess = false;
        if ($auth->hasRole(Roles::ROLE_DOCUMENTS_MANAGEMENT_GLOBAL)) {
            $hasSecurityAccess = true;
        }
        $securityLevels = [];
        if ($userSecurityTeams->count() && !$hasSecurityAccess) {
            foreach ($userSecurityTeams as $row) {
                array_push($securityLevels, $row->getId());
            }
        }
        $documents = $documentDao->getApartmentGroupDocuments($apartmentGroupId, $securityLevels, $hasSecurityAccess);
        return $documents;
    }
}
