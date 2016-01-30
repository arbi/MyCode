<?php

namespace Contacts\Controller;

use Library\Constants\Roles;
use Library\Controller\ControllerBase;
use Library\Constants\TextConstants;
use Library\Utility\Helper;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Contacts\Form\ContactForm;
use Contacts\Form\InputFilter\ContactFilter;
use DDD\Service\Contacts\Contact as ContactService;


class Contacts extends ControllerBase
{
    /**
     * @return ViewModel
     */
    public function searchAction()
    {
        $preSelectedContactIdAndType = $this->params()->fromRoute('contact_id', 0);
        $paramsArray = explode('_', $preSelectedContactIdAndType);
        $type = $paramsArray[0];
        if (!in_array($type,
            [
            ContactService::TYPE_GENERAL,
            ContactService::TYPE_EMPLOYEE,
            ContactService::TYPE_OFFICE,
            ContactService::TYPE_PARTNER,
        ]) && $preSelectedContactIdAndType) {
            return new ViewModel(['status' => 'error', 'msg' => 'This contact type does not exist']);
        }

        if (isset($paramsArray[1])) {
            if (!is_numeric($paramsArray[1]) || !$paramsArray[1]) {
                return new ViewModel(['status' => 'error', 'msg' => 'The Entity Id is not set or it is wrong']);
            }
            $id = $paramsArray[1];
            return new ViewModel($this->renderCard($type, $id));
        }
        return new ViewModel();
    }

    /**
     * @return JsonModel
     */
    public function ajaxSearchContactAction()
    {
        $result = [
            'status'    => 'error',
            'msg'       => TextConstants::SERVER_ERROR
        ];

        $request = $this->getRequest();

        try {
            if($request->isXmlHttpRequest()) {
                $searchQuery = trim(strip_tags($request->getPost('query')));

                /**
                 * @var \DDD\Service\Contacts\Contact $contactService
                 * @var \DDD\Service\Partners $partnerService
                 * @var \DDD\Service\User\Main $userService
                 * @VAR \DDD\Service\Office $officeServer
                 */
                $auth           = $this->getServiceLocator()->get('library_backoffice_auth');
                $contactService = $this->getServiceLocator()->get('service_contact_contact');
                $partnerService = $this->getServiceLocator()->get('service_partners');
                $userService    = $this->getServiceLocator()->get('service_user_main');
                $officeService   = $this->getServiceLocator()->get('service_office');

                $hasPartnerManagementRole = $auth->hasRole(Roles::ROLE_PARTNER_MANAGEMENT);
                $hasProfileAccessRole     = $auth->hasRole(Roles::ROLE_PROFILE);
                $hasOurOfficesRole  = $auth->hasRole(Roles::ROLE_OFFICE);

                $resultGeneral = $contactService->searchContacts($searchQuery);

                if ($hasPartnerManagementRole) {
                    $resultPartner = $partnerService->searchContacts($searchQuery);
                } else {
                    $resultPartner = [];
                }

                if ($hasProfileAccessRole) {
                    $resultEmployee = $userService->searchContacts($searchQuery);
                } else {
                    $resultEmployee = [];
                }

                if ($hasOurOfficesRole) {
                    $resultOffice = $officeService->searchContacts($searchQuery);
                } else {
                    $resultOffice = [];
                }


                $result = array_merge($resultGeneral, $resultPartner, $resultEmployee, $resultOffice);
            }
        } catch (\Exception $e) {
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function ajaxGetContactAction()
    {
        $result = [
            'status'    => 'error',
            'msg'       => TextConstants::SERVER_ERROR
        ];

        $request = $this->getRequest();

        try{
            if($request->isXmlHttpRequest()) {
                $postData = $request->getPost();
                $type = $postData['type'];
                $id   = $postData['id'];
                return new JsonModel($this->renderCard($type, $id));
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot get Contact Info');
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function ajaxCreateContactAction()
    {
        $result = [
            'status'    => 'error',
            'msg'       => TextConstants::SERVER_ERROR
        ];

        $request = $this->getRequest();

        try{
            if($request->isXmlHttpRequest()) {
                $postData = $request->getPost();
                /**
                 * @var \Library\Authentication\BackofficeAuthenticationService $authService
                 */
                $authService = $this->getServiceLocator()->get('library_backoffice_auth');
                $postData['creator_id'] = $authService->getIdentity()->id;
                $postData['date_created'] = date('Y-m-d H:i');
                $form = new ContactForm($this->getServiceLocator());
                $form->setInputFilter(new ContactFilter());
                $form->setData($postData);
                if ($form->isValid()) {
                    /**
                     * @var \DDD\Service\Contacts\Contact $contactService
                     */
                    $contactService = $this->getServiceLocator()->get('service_contact_contact');
                    $checkDuplicateStatus = $contactService
                        ->checkDuplicateByNameWithinTeamId($postData['name'], $postData['team_id']);
                    if ($checkDuplicateStatus->count() === 0) {
                        $newContactId = $contactService->addContact($postData);
                        if ($newContactId) {
                            Helper::setFlashMessage([
                                'success' => 'Contact '. TextConstants::SUCCESS_CREATED
                            ]);
                            $result = [
                                'status'    => 'success',
                                'id'        => $newContactId
                            ];
                        } else {
                            throw new \Exception('Issue in DAO or posted data');
                        }
                    } else {
                        $result = [
                            'status'    => 'error',
                            'msg'       => TextConstants::CONTACTS_DUPLICATE_BY_NAME_WITHIN_TEAM
                        ];
                    }
                } else {
                    $errorMessage = self::parseFormMessage($form);

                    $result = [
                        'status'    => 'error',
                        'msg'       => $errorMessage
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot create a new contact');
        }

        return new JsonModel($result);
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function editAction()
    {
        try {
            /**
             * @var \DDD\Service\Contacts\Contact $contactService
             */
            $contactId = $this->params()->fromRoute('contact_id', 0);
            $contactService = $this->getServiceLocator()->get('service_contact_contact');
            $contactData = $contactService->getContactById($contactId);
            $form = new ContactForm($this->getServiceLocator(), 'contact_form', $contactData);
            $form->prepare();
            $result = new ViewModel([
                'form'          => $form,
                'contactData'   => $contactData,
                'contactId'     => $contactId
            ]);
            return $result;

        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot edit contact');
        }

        return $this->redirect()->toRoute('contacts');
    }

    /**
     * @return JsonModel
     */
    public function ajaxUpdateContactAction()
    {
        $result = [
            'status'    => 'error',
            'msg'       => TextConstants::SERVER_ERROR
        ];

        $request = $this->getRequest();

        try{
            if($request->isXmlHttpRequest()) {
                $postData = $request->getPost();

                $postData['date_modified'] = date('Y-m-d H:i');

                $contactId = $postData['contact_id'];
                unset($postData['contact_id']);

                /**
                 * @var \DDD\Service\Contacts\Contact $contactService
                 */
                $contactService = $this->getServiceLocator()->get('service_contact_contact');
                $contactCurrentData = $contactService->getContactById($contactId);

                if ($contactCurrentData) {

                    $teamIdForDuplicateCheck = $contactCurrentData->getTeamId();
                    $permittedDuplicatesCount = 1;

                    if ($postData['team_id'] != $contactCurrentData->getTeamId()) {
                        $teamIdForDuplicateCheck = $postData['team_id'];
                        $permittedDuplicatesCount = 0;
                    }

                    if ($permittedDuplicatesCount == 1
                        && strtolower($postData['name']) != strtolower($contactCurrentData->getName())
                    ) {
                        $permittedDuplicatesCount = 0;
                    }

                    $checkDuplicateStatus = $contactService
                        ->checkDuplicateByNameWithinTeamId($postData['name'], $teamIdForDuplicateCheck);

                    if ($checkDuplicateStatus
                        && !($checkDuplicateStatus->count() > $permittedDuplicatesCount)
                    ) {
                        $form = new ContactForm($this->getServiceLocator());
                        $form
                            ->remove('creator_id')
                            ->remove('date_created');

                        $filter = new ContactFilter();
                        $filter
                            ->remove('creator_id')
                            ->remove('date_created');

                        $form->setInputFilter($filter);

                        $form->setData($postData);

                        if ($form->isValid()) {
                            /**
                             * @var \DDD\Service\Contacts\Contact $contactService
                             */
                            $contactService = $this->getServiceLocator()->get('service_contact_contact');

                            $updateResult = $contactService->updateContact($contactId, $postData);

                            if ($updateResult !== false) {
                                $result = [
                                    'status'    => 'success',
                                    'msg'       => TextConstants::SUCCESS_UPDATE
                                ];
                            } else {
                                throw new \Exception('Issue in DAO or submitted data');
                            }
                        } else {
                            $errorMessage = self::parseFormMessage($form);

                            $result = [
                                'status'    => 'error',
                                'msg'       => $errorMessage
                            ];
                        }
                    } elseif ($checkDuplicateStatus === false) {
                        $result = [
                            'status'    => 'error',
                            'msg'       => TextConstants::CONTACTS_NOT_FOUND
                        ];
                    } else {
                        $result = [
                            'status'    => 'error',
                            'msg'       => TextConstants::CONTACTS_DUPLICATE_BY_NAME_WITHIN_TEAM
                        ];
                    }
                } else {
                    $result = [
                        'status'    => 'error',
                        'msg'       => TextConstants::NO_PERMISSION
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot update contact');
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function ajaxDeleteContactAction()
    {
        $result = [
            'status'    => 'error',
            'msg'       => TextConstants::SERVER_ERROR
        ];

        $request = $this->getRequest();

        try{
            if($request->isXmlHttpRequest()) {
                $contactId = $this->params()->fromRoute('contact_id', 0);

                if (!is_numeric($contactId) || $contactId == 0) {
                    throw new \Exception('Wrong Contact ID');
                }

                /**
                 * @var \DDD\Service\Contacts\Contact $contactService
                 */
                $contactService = $this->getServiceLocator()->get('service_contact_contact');

                $contactData = $contactService->getContactById($contactId);

                if ($contactData) {
                    $deleteStatus = $contactService->deleteContact($contactId);

                    if ($deleteStatus === null) {
                        Helper::setFlashMessage([
                            'success' => 'Contact '. TextConstants::SUCCESS_DELETE
                        ]);

                        $result = [
                            'status'    => 'success',
                            'msg'       => TextConstants::SUCCESS_DELETE
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot delete contact');
        }

        return new JsonModel($result);
    }

    public function ajaxGetApartmentBuildingAction()
    {
        $request = $this->getRequest();
        $apartmentId = $this->params()->fromRoute('apartment_id', 0);

        $result = [];

        try {

            if ($request->isXmlHttpRequest() && $apartmentId) {
                /** @var \DDD\Service\Apartment\Main $apartmentService */
                $apartmentService = $this->getServiceLocator()->get('service_apartment_main');

                $building = $apartmentService->getApartmentBuilding($apartmentId);
                if ($building) {
                    $result = [
                        'id' => $building['building_id'],
                        'name' => $building['name']
                    ];
                }
            }
        } catch (\Exception $e) {
            // Do Nothing
        }

        return new JsonModel($result);
    }

    public function ajaxGetPhoneCodesAction()
    {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            /**
             * @var \DDD\Dao\Location\Country $countryDao
             */
            $countryDao = $this->getServiceLocator()->get('dao_location_country');
            $countryWithPhoneCodes = $countryDao->getCountryWithPhoneCodes();

            $result = [];

            foreach ($countryWithPhoneCodes as $country) {
                $result[] = [
                    'id'    => $country['id'],
                    'name'  => $country['name'],
                    'code'  => $country['phone_code'],
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }

    /**
     * @param \Contacts\Form\ContactForm $form
     * @return bool|string
     */
    private function parseFormMessage($form)
    {
        if (is_array($form->getMessages())) {
            $errorMessage = '';

            foreach ($form->getMessages() as $title => $values) {
                if (is_array($values)) {
                    $errorMessage .= $form->getElements()[$title]->getLabel() . PHP_EOL;
                    foreach ($values as $value) {
                        $errorMessage .= '<li>' . $value . '</li>';
                    }
                } else {
                    $errorMessage .= $form->getElements()[$title]->getLabel() . PHP_EOL . $values;
                }
            }

            return $errorMessage;
        } elseif(is_string($form->getMessages())) {
            return $form->getMessages();
        } else {
            return false;
        }
    }


    private function renderCard($type, $id)
    {
        /**
         * @var \DDD\Service\Contacts\Contact $contactService
         * @var \DDD\Service\Partners $partnerService
         * @var \DDD\Service\User\Main $userService
         * @VAR \DDD\Service\Office $officeServer
         */
        $auth                      = $this->getServiceLocator()->get('library_backoffice_auth');
        $contactService            = $this->getServiceLocator()->get('service_contact_contact');
        $partnerService            = $this->getServiceLocator()->get('service_partners');
        $userService               = $this->getServiceLocator()->get('service_user_main');
        $officeService             = $this->getServiceLocator()->get('service_office');
        $hasPartnerManagementRole  = $auth->hasRole(Roles::ROLE_PARTNER_MANAGEMENT);
        $hasProfileAccessRole      = $auth->hasRole(Roles::ROLE_PROFILE);
        $hasProfileFullViewerRole  = $auth->hasRole(Roles::ROLE_PROFILE_VIEWER);
        $hasPeopleManagementRole   = $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT);
        $hasPeopleManagementHRRole = $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR);
        $hasOurOfficesRole         = $auth->hasRole(Roles::ROLE_OFFICE);
        $hasOfficeManagementRole   = $auth->hasRole(Roles::ROLE_OFFICE_MANAGER);
        $partial                   = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');

        $doNotHaveSufficientPermissionsError = [
            'status' => 'error',
            'msg'    => 'You do not have Sufficient permissions to view this card'
        ];

        $itemDoesNotExistErrorMessage = [
            'status' => 'error',
            'msg'    => 'Contact not found'
        ];
        switch ($type) {
            case ContactService::TYPE_GENERAL:
                $contactData = $contactService->getContactById($id, true);
                if (!$contactData) {
                    return $itemDoesNotExistErrorMessage;
                }
                $cardsPartial = $partial('contacts/contacts/partial/general', [
                    'card' => $contactData,
                    'userInternalNumber'  => $auth->getIdentity()->internal_number
                ]);
                break;
            case ContactService::TYPE_PARTNER:
                if (!$hasPartnerManagementRole) {
                    return $doNotHaveSufficientPermissionsError;
                }
                $contactData = $partnerService->partnerById($id);
                if (!$contactData) {
                    return $itemDoesNotExistErrorMessage;
                }
                $cardsPartial = $partial('contacts/contacts/partial/partner', [
                    'card' => $contactData,
                    'userInternalNumber'  => $auth->getIdentity()->internal_number
                ]);
                break;
            case ContactService::TYPE_OFFICE:
                if (!$hasOurOfficesRole) {
                    return $doNotHaveSufficientPermissionsError;
                }
                $contactData = $officeService->getOfficeDetailsById($id, false);
                if (!$contactData) {
                    return $itemDoesNotExistErrorMessage;
                }
                $cardsPartial = $partial('contacts/contacts/partial/office', [
                    'card' => $contactData,
                    'hasGlobal' => $hasOfficeManagementRole,
                    'userInternalNumber'  => $auth->getIdentity()->internal_number
                ]);
                break;
            case ContactService::TYPE_EMPLOYEE:
                if (!$hasProfileAccessRole) {
                    return $doNotHaveSufficientPermissionsError;
                }
                $contactData = $userService->getUserForContactInfo($id);
                if (!$contactData) {
                    return $itemDoesNotExistErrorMessage;
                }
                $cardsPartial = $partial('contacts/contacts/partial/employee', [
                    'card'                 => $contactData,
                    'hasGlobal'            => $hasPeopleManagementRole || $hasPeopleManagementHRRole,
                    'hasProfileFullViewer' => $hasProfileFullViewerRole,
                    'userInternalNumber'   => $auth->getIdentity()->internal_number
                ]);
                break;
            default:
                return ['status' => 'error', 'msg' => 'This contact type does not exist'];
        }
        $result = [
            'status'    => 'success',
            'cardsPartial' => $cardsPartial
        ];
        return $result;
    }
}
