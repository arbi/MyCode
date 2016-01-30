<?php

namespace Backoffice\Controller;

use Library\Constants\Roles;
use Library\Controller\ControllerBase;
use Library\Constants\Constants;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplateMapResolver;


class ApartmentGroupDocumentController extends ControllerBase
{
    public function indexAction()
    {
        /* @var $auth \Library\Authentication\BackofficeAuthenticationService */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        /** @var \DDD\Service\Document\Document $documentService */
        $documentService = $this->getServiceLocator()->get('service_document_document');
        /** @var \DDD\Dao\ApartmentGroup\ApartmentGroup $apartmentGroupDao */
        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        $apartmentGroupId = (int)$this->params()->fromRoute('id', 0);
        $teamUsageService = $this->getServiceLocator()->get('service_team_usages_security');
        $userSecurityTeams = $teamUsageService->getUserSecuredTeams($auth->getIdentity()->id);
        $hasSecurityAccess = $auth->hasRole(Roles::ROLE_DOCUMENTS_MANAGEMENT_GLOBAL);
        $apartmentGroupData = $apartmentGroupDao->getRowById($apartmentGroupId);

        if (!$userSecurityTeams->count() && !$hasSecurityAccess) {
            return $this->redirect()->toUrl('/');
        }

        /** @var \DDD\Domain\Document\Document[] $documents */
        $documents = $documentService->getApartmentGroupDocumentsList(
            $apartmentGroupId,
            $userSecurityTeams
        );
        $documentsArray = [];

        foreach ($documents as $document) {
            $externalLink = '';
            if ($document->getURL() != '') {
                $externalLink = '<a class="btn btn-xs btn-info pull-right" href="' . $document->getURL() . '" target="blank"><i class="glyphicon glyphicon-eye-open"></i> View </a>';
            }
            $url = $this->url()->fromRoute('documents/edit_document', ['id' => $document->getID()]);
            $view = '<a class="btn btn-xs btn-primary" href="' . $url . '" target="_blank">Edit</a>';
            $downloadAction = '';
            if ($document->getAttachment()) {
                $downloadUrl = $this->url()->fromRoute('documents/download', ['id' => $document->getID()]);
                $downloadAction = '<button type="button" class="btn btn-xs btn-success self-submitter state downloadViewButton pull-left" value="' . $downloadUrl . '"><i class="glyphicon glyphicon-download"></i> Download </button>';
            }
            $documentsArray[] = [
                $document->getTypeName(),
                date(Constants::GLOBAL_DATE_FORMAT, strtotime($document->getCreatedDate())),
                $document->getTeamName(),
                '<p class="crop">' . strip_tags($document->getDescription()) . '</p>',
                $downloadAction,
                $externalLink,
                $view,
            ];
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables([
            'aaData' => json_encode($documentsArray),
            'apartmentGroupId' => $apartmentGroupId,
            'apartmentGroupName' => $apartmentGroupData->getName()
        ]);
        $resolver = new TemplateMapResolver(
            ['backoffice/apartment-group/document/index' => '/ginosi/backoffice/module/Backoffice/view/backoffice/apartment-group/document/index.phtml']
        );
        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);
        $viewModel->setTemplate('backoffice/apartment-group/document/index');
        return $viewModel;
    }
}