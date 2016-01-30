<?php

namespace Contacts\Form;

use DDD\Service\Contacts\Contact;
use DDD\Service\Team\Team;
use Zend\ServiceManager\ServiceLocatorInterface;

use Library\Form\FormBase;

class ContactForm extends FormBase
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator = false;

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name
     * @param \DDD\Domain\Contacts\Contact $data
     */
    public function __construct($serviceLocator, $name = null, $data = [])
    {
        parent::__construct();

        $this->serviceLocator = $serviceLocator;

        if (null === $name) {
            $name = 'contact_form';
        }

        $this->setName($name);

        $this->setAttribute('method', 'post');

        $this->add([
            'name' => 'creator_id',
            'type' => 'Zend\Form\Element\Number',
            'attributes' => [
                'id' => 'creator_id',
                'min' => '1',
                'step' => '1'
            ],
            'options' => [
                'label' => 'Created By'
            ]
        ]);

        $this->add([
            'name' => 'date_created',
            'type' => 'Zend\Form\Element\DateTime',
            'attributes' => [
                'id' => 'date_created',
                'step' => '1'
            ],
            'options' => [
                'label' => 'Created Date',
                'format' => 'Y-m-d H:i'
            ]
        ]);

        $this->add([
            'name' => 'date_modified',
            'type' => 'Zend\Form\Element\DateTime',
            'attributes' => [
                'id' => 'date_modified',
                'step' => '1'
            ],
            'options' => [
                'label' => 'Modified Date',
                'format' => 'Y-m-d H:i'
            ]
        ]);

        $this->add([
            'name' => 'scope',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'scope',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Scope',
                'value_options' => [
                    Contact::SCOPE_TEAM     => 'Team',
                    Contact::SCOPE_PERSONAL => 'Personal',
                    Contact::SCOPE_GLOBAL   => 'Global',
                ]
            ]
        ]);

        $this->add([
            'name' => 'team_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'team_id',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Team',
                'empty_option' => ' -- Select a Team -- ',
                'value_options' => $this->getTeamsOptions()
            ]
        ]);

        $selectedApartmentId = ($data && $data->getApartmentId()) ? $data->getApartmentId() : 0;
        $this->add([
            'name' => 'apartment_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'apartment_id',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Apartment',
                'empty_option' => ' -- Select an Apartment -- ',
                'value_options' => $this->getApartments($selectedApartmentId)
            ]
        ]);

        $selectedBuildingId = ($data && $data->getBuildingId()) ? $data->getBuildingId() : 0;
        $this->add([
            'name' => 'building_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'building_id',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Building',
                'empty_option' => ' -- Select a Building -- ',
                'value_options' => $this->getBuildings($selectedBuildingId)
            ]
        ]);

        $this->add([
            'name' => 'partner_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'partner_id',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Partner',
                'empty_option' => ' -- Select a Partner -- ',
                'value_options' => $this->getPartners()
            ]
        ]);

        $this->add([
            'name' => 'name',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'name',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Contact Name'
            ]
        ]);

        $this->add([
            'name' => 'company',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'company',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Company'
            ]
        ]);

        $this->add([
            'name' => 'position',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'position',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Position'
            ]
        ]);

        $this->add([
            'name' => 'city',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'city',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'City'
            ]
        ]);

        $this->add([
            'name' => 'address',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'address',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Address'
            ]
        ]);

        $this->add([
            'name' => 'email',
            'type' => 'Zend\Form\Element\Email',
            'attributes' => [
                'id' => 'email',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Email'
            ]
        ]);

        $this->add([
            'name' => 'skype',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'skype',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Skype'
            ]
        ]);

        $this->add([
            'name' => 'url',
            'type' => 'Zend\Form\Element\Url',
            'attributes' => [
                'id' => 'url',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'URL'
            ]
        ]);

        $this->add([
            'name' => 'phone_mobile_country_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'phone_mobile_country_id',
                'class' => 'form-control',
                'data-placeholder' => 'code',
            ],
            'options' => [
                'value_options' => [],
                'disable_inarray_validator' => true
            ]
        ]);

        $this->add([
            'name' => 'phone_mobile',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'phone_mobile',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Mobile Phone'
            ]
        ]);

        $this->add([
            'name' => 'phone_company_country_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'phone_company_country_id',
                'class' => 'form-control',
                'data-placeholder' => 'code',
            ],
            'options' => [
                'value_options' => [],
                'disable_inarray_validator' => true
            ]
        ]);

        $this->add([
            'name' => 'phone_company',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'phone_company',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Company Phone'
            ]
        ]);

        $this->add([
            'name' => 'phone_other_country_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'phone_other_country_id',
                'class' => 'form-control',
                'data-placeholder' => 'code',
            ],
            'options' => [
                'value_options' => [],
                'disable_inarray_validator' => true
            ]
        ]);

        $this->add([
            'name' => 'phone_other',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'phone_other',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Other Phone'
            ]
        ]);

        $this->add([
            'name' => 'phone_fax_country_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'phone_fax_country_id',
                'class' => 'form-control',
                'data-placeholder' => 'code',
            ],
            'options' => [
                'value_options' => [],
                'disable_inarray_validator' => true
            ]
        ]);

        $this->add([
            'name' => 'phone_fax',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'id' => 'phone_fax',
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'Fax'
            ]
        ]);

        $this->add([
            'name' => 'notes',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'id' => 'notes',
                'class' => 'form-control tinymce',
                'rows'  => '6'
            ],
            'options' => [
                'label' => 'Notes',
            ]
        ]);

        $buttonOptions = [
            'label' => 'Create Contact',
            'loading_text' => 'Creating...'
        ];

        if (is_object($data)) {
            $buttonOptions = [
                'label' => 'Save Changes',
                'loading_text' => 'Saving...'
            ];
        }

        $this->add(
            [
                'name' => 'save_button',
                'type' => 'Zend\Form\Element\Button',
                'attributes' => [
                    'id'                => 'save_button',
                    'value'             => $buttonOptions['label'],
                    'data-loading-text' => $buttonOptions['loading_text'],
                    'class'             =>
                        'btn btn-primary pull-right col-xs-12 col-sm-2 margin-left-10',
                ],
                'options' => [
                    'label' => $buttonOptions['label'],
                ],
            ]
        );

        if (is_object($data)) {
            $objectData = new \ArrayObject();

            $objectData['creator_id']   = $data->getCreatorId();
            $objectData['date_created']     = $data->getDateCreated();
            $objectData['date_modified']    = $data->getDateModified();
            $objectData['scope']            = $data->getScope();
            $objectData['team_id']          = $data->getTeamId();
            $objectData['apartment_id']     = $data->getApartmentId();
            $objectData['building_id']      = $data->getBuildingId();
            $objectData['partner_id']       = $data->getPartnerId();
            $objectData['name']             = $data->getName();
            $objectData['company']          = $data->getCompany();
            $objectData['position']         = $data->getPosition();
            $objectData['city']             = $data->getCity();
            $objectData['address']          = $data->getAddress();
            $objectData['email']            = $data->getEmail();
            $objectData['skype']            = $data->getSkype();
            $objectData['url']              = $data->getUrl();
            $objectData['phone_mobile']     = $data->getPhoneMobile();
            $objectData['phone_company']    = $data->getPhoneCompany();
            $objectData['phone_other']      = $data->getPhoneOther();
            $objectData['phone_fax']        = $data->getPhoneFax();
            $objectData['notes']            = $data->getNotes();

            $this->bind($objectData);
        }
    }

    /**
     * @return array
     */
    private function getTeamsOptions()
    {
        /**
         * @var Team $teamService
         */
        $teamService = $this->serviceLocator->get('service_team_team');

        $teamsList = $teamService->getPermanentTeams();
        $options = [];

        foreach ($teamsList as $team) {
            $options[$team->getId()] = $team->getName();
        }

        return $options;
    }

    /**
     * @param $selectedId
     * @return array
     */
    private function getApartments($selectedId)
    {
        /**
         * @var \DDD\Service\Apartment\General $apartmentService
         */
        $apartmentService = $this->serviceLocator->get('service_apartment_general');

        $apartmentsList = $apartmentService->getApartmentsForCountryForSelect(false, false, $selectedId);
        $options = [];

        foreach ($apartmentsList as $apartment) {
            $options[$apartment['id']] = $apartment['name'];
        }

        return $options;
    }

    /**
     * @return array
     */
    private function getBuildings($selectedId)
    {
        /** @var \DDD\Service\ApartmentGroup\Usages\Building $buildingService */
        $buildingService = $this->serviceLocator->get('service_apartment_group_usages_building');

        $buildingsList = $buildingService->getBuildingListForSelectize($selectedId);
        $options = [];

        foreach ($buildingsList as $building) {
            $options[$building['id']] = $building['name'];
        }

        return $options;
    }

    /**
     * @return array
     */
    private function getPartners()
    {
        /**
         * @var \DDD\Service\Partners $partnerService
         */
        $partnerService = $this->serviceLocator->get('service_partners');

        $partnersList = $partnerService->getPartnerlist();
        $options = [];

        foreach ($partnersList as $partner) {
            $options[$partner['id']] = $partner['name'];
        }

        return $options;
    }
}
