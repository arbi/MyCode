<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Apartment\Form\Document as DocumentForm;
use Apartment\Form\InputFilter\DocumentFilter;

use DDD\Service\User;
use Library\Constants\Constants;
use Zend\View\Model\ViewModel;

use DDD\Service\Team\Usages\Base as TeamUsageService;
use DDD\Service\Team\Team as TeamService;

use Library\Utility\Helper;
use Library\Constants\TextConstants;
use Library\Constants\Roles;

class Document extends ApartmentBaseController
{
    public function indexAction()
    {

        /* @var $auth \Library\Authentication\BackofficeAuthenticationService */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $teamUsageService  = $this->getServiceLocator()->get('service_team_usages_security');
        $userSecurityTeams = $teamUsageService->getUserSecuredTeams($auth->getIdentity()->id);
        $hasSecurityAccess  = $auth->hasRole(Roles::ROLE_DOCUMENTS_MANAGEMENT_GLOBAL);

        if (!$userSecurityTeams->count() && !$hasSecurityAccess) {
            return $this->redirect()->toUrl( '/' );
        }

        /** @var \DDD\Service\Document\Document $documentService */
        $documentService = $this->getServiceLocator()->get('service_document_document');

        /** @var \DDD\Dao\Contacts\Contact $contactDao */
        $contactDao = $this->getServiceLocator()->get('dao_contacts_contact');

        $documents = $documentService->getApartmentDocumentsList(
            $this->apartmentId,
            $userSecurityTeams
        );

        $documentsArray = [];

        if ($documents) {
            foreach ($documents as $document) {
                $externalLink = '';

                if ($document->getURL() != '') {
                    $externalLink = '<a class="btn btn-xs btn-info pull-right" href="' . $document->getURL() . '" target="blank"><i class="glyphicon glyphicon-eye-open"></i> View </a>';
                }

                $url = $this->url()->fromRoute('documents/edit_document', [
                    'id'       => $document->getID(),
                ]);
                $view = '<a class="btn btn-xs btn-primary" href="' . $url . '" target="_blank">Edit</a>';
                $downloadAction = '';

                if ($document->getAttachment()) {
                    $downloadUrl = $this->url()->fromRoute('documents/download', [
                        'id'       => $document->getID(),
                    ]);
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
        }

        $relatedContacts = $contactDao->getContactByApartmentId($this->apartmentId);

        $contactArray = [];

        foreach ($relatedContacts as $contact) {
            $url  = $this->url()->fromRoute('contacts/edit', ['contact_id' => $contact->getId()]);
            $viewContact = '<a class="btn btn-xs btn-primary" href="' . $url . '" target="_blank">View</a>';

            $code = !is_null($contact->getPhoneMobileCountryCode()) ? '(+' . $contact->getPhoneMobileCountryCode() . ') ' : '';
            $contactArray[] = [
                $contact->getName(),
                $contact->getCompany(),
                $code . $contact->getPhoneMobile(),
                $contact->getEmail(),
                $viewContact
            ];

        }

		return new ViewModel([
            'aaData'          => $documentsArray,
            'apartmentId'     => $this->apartmentId,
            'apartmentStatus' => $this->apartmentStatus,
            'contactsData'    => $contactArray
		]);
	}
}
