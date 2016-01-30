<?php

namespace Backoffice\Controller;

use Backoffice\Form\Concierge as ConciergeForm;

use Library\Constants\DbTables;
use Library\Utility\Helper;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use DDD\Service\Team\Usages\Base as TeamUsageService;
use Library\Controller\ControllerBase;
use Library\Constants\Constants;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplateMapResolver;


class ApartmentGroupContactsController extends ControllerBase
{
    public function indexAction()
    {
        $apartmentGroupId = (int)$this->params()->fromRoute('id', 0);
        $contactDao       = $this->getServiceLocator()->get('dao_contacts_contact');
        $relatedContacts  = $contactDao->getContactByBuildingId($apartmentGroupId);
        $contactArray     = [];

        foreach ($relatedContacts as $contact) {
            $url         = $this->url()->fromRoute('contacts/edit', ['contact_id' => $contact->getId()]);
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

        $viewModel = new ViewModel();

        $viewModel->setVariables([
            'contactsData' => json_encode($contactArray),
            'id'           => $apartmentGroupId

        ]);

        $resolver = new TemplateMapResolver(
            ['backoffice/apartment-group/usages/contact' => '/ginosi/backoffice/module/Backoffice/view/backoffice/apartment-group/usages/contact.phtml']
        );

        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);
        $viewModel->setTemplate('backoffice/apartment-group/usages/contact');
        return $viewModel;
    }
}
