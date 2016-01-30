<?php

namespace Document\Controller;

use Document\Form\SearchDocumentForm;
use Document\Form\Document as DocumentForm;
use Document\Form\InputFilter\DocumentFilter;
use FileManager\Constant\DirectoryStructure;
use Library\Constants\Constants;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use DDD\Service\Team\Usages\Base as TeamUsageService;
use DDD\Service\Document\Document as DocumentService;
use Library\Utility\CsvGenerator;
use Library\Utility\Helper;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class Document extends ControllerBase
{
    public function searchAction()
    {
        /* @var $auth \Library\Authentication\BackofficeAuthenticationService */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_DOCUMENTS_MANAGEMENT)) {
            return $this->redirect()->toRoute('home');
        }


        /** @var \DDD\Service\Document\Document $documentService */
        $documentService = $this->getServiceLocator()->get('service_document_document');
        $formResources = $documentService->prepareDocumentSearchFormResources();

        $teamUsageService = $this->getServiceLocator()->get('service_team_usages_security');
        $teamSecurityLists = $teamUsageService->getUserSecuredTeams($auth->getIdentity()->id);

        $accessTeams = [];
        foreach ($teamSecurityLists as $row) {
            $accessTeams[$row->getId()] = $row->getName();
        }

        $formResources['accessTeams'] = $accessTeams;
        $legalEntityService = $this->getServiceLocator()->get('service_finance_legal_entities');
        $legalEntitiesArray = $legalEntityService->getLegalEntitiesForSelect();
        $userService = $this->getServiceLocator()->get('service_user');
        $legalEntitiesArray[0] = '-- Legal Entity --';
        $signatoriesArray = $userService->getAllActiveUsersArray();
        $signatoriesArray[0] = '-- Signatory --';
        $searchForm = new SearchDocumentForm('search_document', $formResources, $legalEntitiesArray, $signatoriesArray);

        return new ViewModel(['search_form' => $searchForm]);
    }

    public function ajaxGetDocumentsJsonAction()
    {
        /** @var \DDD\Service\Document\Document $documentService */
        $documentService = $this->getServiceLocator()->get('service_document_document');

        $queryParams = $this->params()->fromQuery();
        $result = [];

        $documents = $documentService->getDocumentSearchResults($queryParams);

        if ($documents && $documents->count() > 0) {
            foreach ($documents as $document) {
                $entityTypeLabel = '';
                if ($document->getEntityType() == DocumentService::ENTITY_TYPE_APARTMENT) {
                    $entityTypeLabel = '<label class="label label-success">A</label>';
                } else {
                    if ($document->getEntityType() == DocumentService::ENTITY_TYPE_APARTMENT_GROUP) {
                        $entityTypeLabel = '<label class="label label-info">B</label>';
                    }
                }
                $documentEntityName = $entityTypeLabel . ' ' . $document->getEntityName();
                $documentDesc = strip_tags($document->getDescription(), '<br>');
                $dotes = (strlen($documentDesc) > 42) ? '...' : '';

                $documentDescriptionCleaup =
                    '<div style="white-space: pre-line !important;">'
                    . trim(substr(
                        str_replace(
                            ['&lt;p&gt;', '&lt;/p&gt;', "\n", "\r\n", "\r"],
                            ' ',
                            strip_tags($document->getDescription(), '<br>')
                        ), 0, 42)) . $dotes
                    . '</div>';

                if ($document->getAttachment()) {
                    $downloadUrl = $this->url()->fromRoute('documents/download', [
                        'id' => $document->getId(),
                    ]);

                    $downloadAction = '<a type="button" class="btn btn-xs btn-success self-submitter state downloadViewButton pull-right" href="' . $downloadUrl . '"><i class="glyphicon glyphicon-download"></i> Download</a>';
                } else {
                    $downloadAction = '';
                }

                if (!empty($document->getUrl())) {
                    $externalLink = '<a class="btn btn-xs btn-info pull-right" href="' . $document->getUrl() . '" target="_blank"><i class="glyphicon glyphicon-eye-open"></i> View </a>';
                } else {
                    $externalLink = '';
                }

                $editUrl = $this->url()->fromRoute('documents/edit_document', [
                    'id' => $document->getId(),
                ]);

                $editAction = '<a class="btn btn-xs btn-primary pull-right" href="' . $editUrl . '" data-html-content="Edit" target="_blank"></a>';

                // 78  === not set
                if ($document->getSupplierId() == '78' || empty($document->getSupplierId())) {
                    $supplierName = '';
                } else {
                    $supplierName = $document->getSupplierName();
                }

                $result[] = [
                    $documentEntityName,
                    $document->getTeamName(),
                    $document->getTypeName(),
                    $supplierName,
                    $documentDescriptionCleaup,
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($document->getCreatedDate())),
                    $downloadAction,
                    $externalLink,
                    $editAction
                ];
            }
        }
        return new JsonModel([
            "aaData" => $result
        ]);
    }

    public function editAction()
    {
        /** @var \DDD\Service\Document\Document $documentService */
        $documentService = $this->getServiceLocator()->get('service_document_document');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $teamUsageService = $this->getServiceLocator()->get('service_team_usages_base');
        $legalEntityService = $this->getServiceLocator()->get('service_finance_legal_entities');
        $userService = $this->getServiceLocator()->get('service_user');

        $this->apartmentId = 0;

        $signatoriesArray = $userService->getAllActiveUsersArray();
        $legalEntitiesArray = $legalEntityService->getLegalEntitiesForSelect();
        $documentId = $this->params()->fromRoute('id', 0);

        $data = false;
        $options = $documentService->getFormOptions();
        $options['legalEntityArray'] = $legalEntitiesArray;
        $options['signatoriesArray'] = $signatoriesArray;

        if ($documentId > 0) {
            $data = $documentService->getData($documentId);
        }

        $securedTeamsList = $teamUsageService->getTeamsByUsage(TeamUsageService::TEAM_USAGE_SECURITY);

        foreach ($securedTeamsList as $row) {
            $securedTeamsListArray[$row->getId()] = $row->getName();
        }

        $teamUsageService = $this->getServiceLocator()->get('service_team_usages_security');
        $userSecuredTeams = $teamUsageService->getUserSecuredTeams($auth->getIdentity()->id);

        $userTeamSecurityLists = [];
        if ($userSecuredTeams->count()) {
            foreach ($userSecuredTeams as $row) {
                array_push($userTeamSecurityLists, $row->getId());
            }
        }

        if ($documentId && (!$data || (
                    !in_array($data->getSecurityLevel(), $userTeamSecurityLists)
                    && !$auth->hasRole(Roles::ROLE_DOCUMENTS_MANAGEMENT_GLOBAL)
                ))
        ) {
            $this->redirect()->toRoute('documents');
        }

        $form = new DocumentForm($data, $options);

        if ($documentId > 0 && $data) {
            $documentService->addDownloadButton($documentId, $data->getAttachment(), $form, $this);
        }

        $messages = '';
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setInputFilter(new DocumentFilter());

            $filter = $form->getInputFilter();
            $form->setInputFilter($filter);

            $postData = $request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                $vData = $form->getData();
                $id = $documentService->documentSave((array)$vData);

                if ($id > 0) {
                    $txt = TextConstants::SUCCESS_ADD;
                    if ($documentId > 0) {
                        $txt = TextConstants::SUCCESS_UPDATE;
                    }

                    $flash['success'] = $txt;

                    if (!empty($request->getFiles()['attachment_doc']['name'])) {
                        $uploadStatus = $documentService->uploadFile($request, $id);
                        if ($uploadStatus === true) {
                            $flash['success'] = $txt;
                        } else {
                            $flash['error'] = $uploadStatus;
                        }
                    }
                } else {
                    $flash['error'] = $messages;
                }

                $this->redirect()->toRoute('documents/edit_document', ['id' => $id]);
            } else {
                $errors = $form->getMessages();

                foreach ($errors as $key => $row) {
                    if (!empty($row)) {
                        $messages .= ucfirst($key) . ' ';
                        $messages_sub = '';

                        foreach ($row as $keyer => $rower) {
                            $messages_sub .= $rower;
                        }

                        $messages .= $messages_sub . '<br>';
                    }
                }

                $flash['error'] = $messages;
            }

            Helper::setFlashMessage($flash);
        }

        $passedEntityId = $this->params()->fromQuery('entity_id', false);
        $passedEntityType = $this->params()->fromQuery('entity_type', false);
        $passedEntityName = $this->params()->fromQuery('entity_name', false);

        return new ViewModel([
            'documentId' => $documentId,
            'docForm' => $form,
            'documentData' => $data,
            'entityId' => $passedEntityId,
            'entityType' => $passedEntityType,
            'entityName' => $passedEntityName,
        ]);
    }

    public function ajaxGetEntityListAction()
    {
        /** @var \DDD\Dao\Apartment\General $apartmentGeneralDao */
        $apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');
        /** @var Request $request */
        $request = $this->getRequest();
        /** @var \DDD\Service\ApartmentGroup $apartmentGroupService */
        $apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');

        $apartmentGroupData = [];
        $apartmentsData = [];
        $data = [];

        try {

            $query = $request->getPost('query', '');

            if ($request->isXmlHttpRequest() && $query) {
                $apartments = $apartmentGeneralDao->getApartmentSearch($query);

                foreach ($apartments as $apartment) {
                    array_push(
                        $apartmentsData,
                        [
                            'id' => $apartment['id'],
                            'text' => $apartment['name'],
                            'type' => DocumentService::ENTITY_TYPE_APARTMENT,
                        ]
                    );
                }

                $buildings = $apartmentGroupService->getBuildingsListForSelect($query);

                foreach ($buildings as $key => $value) {
                    array_push(
                        $apartmentGroupData,
                        [
                            'id' => $value['id'],
                            'text' => $value['name'],
                            'type' => DocumentService::ENTITY_TYPE_APARTMENT_GROUP,
                        ]
                    );
                }

                if (isset($apartmentsData) || isset($apartmentGroupData)) {
                    $data = array_merge(
                        $apartmentsData,
                        $apartmentGroupData
                    );
                }
            }
        } catch (\Exception $e) {
            $data = [];
        }

        return new JsonModel($data);
    }

    public function deleteAction()
    {
        $documentId = (int)$this->params()->fromRoute('id', 0);

        /** @var \DDD\Service\Document\Document $documentService */
        $documentService = $this->getServiceLocator()->get('service_document_document');

        if (!$documentId) {
            Helper::setFlashMessage(['error' => 'Invalid Document Id given.']);
        } else {
            $result = $documentService->deleteDocument($documentId);

            if ($result) {
                Helper::setFlashMessage(['success' => 'Document was removed successfully.']);
            } else {
                Helper::setFlashMessage(['error' => 'Cannot remove given Document.']);
            }
        }

        return $this->redirect()->toRoute('documents');
    }

    public function deleteAttachmentAction()
    {
        /* @var $documentService \DDD\Service\Document\Document */
        $documentService = $this->getServiceLocator()->get('service_document_document');
        $id = (int)$this->params()->fromRoute('id', 0);

        $removable = $documentService->removeAttachment($id);
        Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);

        $this->redirect()->toRoute('documents/edit_document', [
            'id' => $id,
        ]);
    }

    /**
     * Action method used to download apartment document
     */
    public function downloadAction()
    {
        /* @var $documentDao \DDD\Dao\Document\Document */
        $documentDao = $this->getServiceLocator()->get('dao_document_document');
        $documentId = (int)$this->params()->fromRoute('id', 0);

        /**
         * @var \DDD\Domain\Document\Document $documentRow
         */
        $documentRow = $documentDao->fetchOne(
            ['id' => $documentId],
            ['attachment', 'created_date']
        );

        $filePath = DirectoryStructure::FS_UPLOADS_DOCUMENTS . date('Y/m/j/', strtotime($documentRow->getCreatedDate())) . $documentRow->getAttachment();

        /**
         * @var \FileManager\Service\GenericDownloader $genericDownloader
         */
        $genericDownloader = $this->getServiceLocator()->get('fm_generic_downloader');

        $genericDownloader->downloadAttachment($filePath, basename($filePath));

        if ($genericDownloader->hasError()) {
            Helper::setFlashMessage(['error' => $genericDownloader->getErrorMessages(true)]);

            $url = $this->getRequest()->getHeader('Referer')->getUri();
            $this->redirect()->toUrl($url);
        }

        return true;
    }

    public function ajaxGetSupplierListAction()
    {
        $request = $this->getRequest();
        $result = [];

        try {
            if ($request->isXmlHttpRequest()) {
                $requestedSupplier = $request->getPost('query');

                /* @var $suppliersDao \DDD\Dao\Finance\Supplier */
                $suppliersDao = $this->getServiceLocator()->get('dao_finance_supplier');

                $suppliersList = $suppliersDao->getAllSuppliers($requestedSupplier, false);

                $result['status'] = 'success';

                foreach ($suppliersList as $key => $supplier) {
                    $result['result'][$key]['id'] = $supplier->getId();
                    $result['result'][$key]['name'] = $supplier->getName();
                    $result['result'][$key]['category'] = 'Suppliers';
                }
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['result'] = null;
        }

        return new JsonModel($result);
    }

    public function ajaxGetUserListAction()
    {
        $request = $this->getRequest();

        try {
            if ($request->isXmlHttpRequest()) {
                $requestedAuthor = $request->getPost('query');

                /* @var $userService \DDD\Service\User */
                $userService = $this->getServiceLocator()->get('service_user');

                $usersList = $userService->getUsersJSON($requestedAuthor, true);

                $result['status'] = 'success';

                foreach ($usersList as $key => $user) {
                    $result['result'][$key]['id'] = $user['id'];
                    $result['result'][$key]['name'] = $user['text'];
                    $result['result'][$key]['category'] = 'People';
                }
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['result'] = null;
        }

        return new JsonModel($result);
    }

    public function downloadCsvAction()
    {
        $requestParams = $this->params()->fromQuery();

        /* @var $documentService \DDD\Service\Document\Document */
        $documentService = $this->getServiceLocator()->get('service_document_document');
        $documents = $documentService->getDocumentSearchResults($requestParams);

        $result = [];

        if (count($documents) > 0) {
            foreach ($documents as $document) {
                $documentDescriptionCleaup = str_replace('&nbsp;', ' ', strip_tags($document->getDescription(), '<br>'));

                $result[] = [
                    'Property' => $document->getEntityName(),
                    'Security Level' => $document->getTeamName(),
                    'Document Type' => $document->getTypeName(),
                    'Supplier' => $document->getSupplierName(),
                    'Account Number' => $document->getAccountNumber(),
                    'Account Holder' => $document->getAccountHolder(),
                    'Description' => $documentDescriptionCleaup,
                    'Created Date' => $document->getCreatedDate(),
                    'Has Attachment' => (empty($document->getAttachment())) ? '-' : '+',
                    'Has url' => (empty($document->getUrl())) ? '-' : '+',
                    'Valid From' => $document->getValidFrom(),
                    'Valid To' => $document->getValidTo(),
                    'Legal Entity' => $document->getLegalEntityName(),
                    'Signatory' => $document->getSignatoryFullName(),
                ];

            }

            $response = $this->getResponse();
            $headers = $response->getHeaders();

            $filename = 'Apartment Documents ' . date('Y-m-d') . '.csv';

            $utilityCsvGenerator = new CsvGenerator();
            $utilityCsvGenerator->setDownloadHeaders($headers, $filename);

            $csv = $utilityCsvGenerator->generateCsv($result);

            $response->setContent($csv);

            return $response;

        } else {
            $flash_session = Helper::getSessionContainer('use_zf2');
            $flash_session->flash = [
                'notice' => 'The search results were empty, nothing to download.'
            ];

            $url = $this->getRequest()->getHeader('Referer')->getUri();
            $this->redirect()->toUrl($url);
        }
    }
}
