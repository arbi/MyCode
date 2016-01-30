<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Apartment\Form\Location as LocationForm;
use League\Flysystem\Exception;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Apartment\Form\InputFilter\LocationFilter;
use DDD\Service\Location as LocationService;
use Library\Utility\Helper;
use Library\Constants\Constants;
use Library\Constants\DomainConstants;
use Library\Constants\TextConstants;

class Location extends ApartmentBaseController
{
	public function indexAction()
    {
        /**
         * @var \DDD\Service\Apartment\Location $apartmentLocationService
         */
        $apartmentLocationService  = $this->getServiceLocator()->get('service_apartment_location');

        /* @var $location \DDD\Domain\Apartment\Location\Location */
        $location                  = $apartmentLocationService->getApartmentLocation($this->apartmentId);
        $preparedData              = $this->prepareFormContent($location->getCountryID(), $location->getProvinceID(), $location->getBuildingID());
        $preparedData['countryId'] = $location->getCountryID();
        $form                      = new LocationForm('apartment_location', $preparedData);

		// Google map configuration
		$config = array (
            'sensor'    => 'true', // true or false
            'div_id'    => 'map', // div id of the google map
            'div_class' => '', // div class of the google map
            'zoom'      => 10, // zoom level
            'width'     => "", // width of the div
            'height'    => "300px", // height of the div
            'lat'       => $location->getX_pos(), // lattitude
            'lon'       => $location->getY_pos()  //longitude
    	);

		$map = $this->getServiceLocator()->get('GMaps\Service\GoogleMapDragableMarker'); //getting the google map object using service manager
		$map->initialize($config); //loading the config
		$mapHTML = $map->generate(); //generating the html map content

		$form->populateValues([
            'longitude'            => $location->getY_pos(),
            'latitude'             => $location->getX_pos(),

            'location_description' => $location->getDescriptionText(),
            'directions'           => $location->getDirectionsText(),
            'description_textline' => $location->getDescriptionTextlineID(),
            'directions_textline'  => $location->getDirectionsTextlineID(),

            'country_id'           => $location->getCountryID(),
            'province_id'          => $location->getProvinceID(),
            'city_id'              => $location->getCityID(),
            'building'             => $location->getBuildingID(),
            'building_section'     => $location->getBuildingSectionId(),
            'address'              => $location->getAddress(),
            'postal_code'          => $location->getPostalCode(),
            'block'                => $location->getBlock(),
            'floor'                => $location->getFloor(),
            'unit_number'          =>$location->getUnitNumber()
		]);

    	// set form type and template
		$formTemplate = 'form-templates/location';

		// passing form and map to the view
		$viewModelForm = new \Zend\View\Model\ViewModel();
		$viewModelForm->setVariables([
            'form'            => $form,
            'mapHTML'         => $mapHTML,
            'apartmentId'     => $this->apartmentId,
            'apartmentStatus' => $this->apartmentStatus,
            'countryId'       => $location->getCountryID(),
            'building'        => $location->getBuilding(),
            'buildingSectionsShow' => (isset($preparedData['buildingSectionOptions']) && $preparedData['buildingSectionOptions'] > 1 ? true : false)
		]);

        //Country Currency List
        $locationService         = $this->getServiceLocator()->get('service_location');
        $listCountryWithCurrecny = $locationService->getCountriesWithCurrecny();
		$viewModelForm->setTemplate($formTemplate);

		$viewModel = new \Zend\View\Model\ViewModel();
		$viewModel->setVariables([
            'apartmentId'             => $this->apartmentId,
            'apartmentStatus'         => $this->apartmentStatus,
            'listCountryWithCurrecny' => $listCountryWithCurrecny
		]);

		// child view to render form
		$viewModel->addChild($viewModelForm, 'formOutput');
		$viewModel->setTemplate('apartment/location/index');

		return $viewModel;
	}

	public function saveAction()
    {
        /**
         * @var \DDD\Service\Apartment\Location $locationService
         */
        $inputFilter = new LocationFilter();
		$request = $this->getRequest();

        if ($request->isPost()) {
            $postData = $request->getPost();
            $form = new LocationForm('apartment_location');

            $form->setInputFilter($inputFilter->getInputFilter());
            $form->setData($postData);
            $form->prepare();

            if ($form->isValid()) {
                $data = $form->getData();
                $locationService = $this->getServiceLocator()->get('service_apartment_location');
                $locationService->saveApartmentLocation($this->apartmentId, $data);
                $flash['success'] = 'Location was successfully updated.';
            } else {
                $messages = '';
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

                $result['status'] = 'error';
                $result['msg'] = $messages;
                $flash['error'] = $messages;
            }

            Helper::setFlashMessage($flash);
        }

        $this->redirect()->toRoute('apartment/location', ['apartment_id' => $this->apartmentId, 'action' => 'save']);
	}

	public function getProvinceOptionsAction()
    {
        $countryID         = (int)$this->params()->fromRoute('country', 0);
        $provinceOptions   = [];
        $provinceOptions[] = [
            'id'   => 0,
            'name' => '--',
		];

		if ($countryID) {
			$generalLocationService = $this->getServiceLocator()->get('service_location');

			// province options
            $provinces       = $generalLocationService->getActiveChildLocations(LocationService::LOCATION_TYPE_PROVINCE, $countryID);
            $provinceOptions = [];

			foreach ($provinces as $province) {
				$provinceOptions[] = [
                    'id'   => $province->getID(),
                    'name' => $province->getName(),
				];
			}
		}

		return new JsonModel($provinceOptions);
	}

	public function getCityOptionsAction()
    {
        $provinceID    = (int)$this->params()->fromRoute('province', 0);
        $cityOptions   = [];
        $cityOptions[] = [
            'id'   => 0,
            'name' => '--',
		];

		if ($provinceID) {
			$generalLocationService = $this->getServiceLocator()->get('service_location');

			// city options
			$cities = $generalLocationService->getActiveChildLocations(LocationService::LOCATION_TYPE_CITY, $provinceID);
			$cityOptions = [];

			foreach ($cities as $city) {
				$cityOptions[] = [
                    'id'   => $city->getID(),
                    'name' => $city->getName(),
				];
			}
		}

		return new JsonModel($cityOptions);
	}

	/**
	 * Prepare needed data before form construction, especially options for select elements
	 *
	 * @param int $countryID
	 * @param int $provinceID
	 * @param int $buildingId
	 * @return array
	 */
	private function prepareFormContent($countryID, $provinceID, $buildingId)
    {
        /* @var $apartmentGroupService \DDD\Service\ApartmentGroup */
        $apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');
        $generalLocationService = $this->getServiceLocator()->get('service_location');

        // country options
        $countries = $generalLocationService->getAllActiveCountries();
        $countryOptions = ['-- Choose --'];
        $content = [];

        foreach ($countries as $country) {
            if ($country->getChildrenCount() != '') {
                $countryOptions[$country->getID()] = $country->getName();
            }
		}

		$content['countryOptions'] = $countryOptions;

		// province options
		$provinces = $generalLocationService->getActiveChildLocations(LocationService::LOCATION_TYPE_PROVINCE, $countryID);
		$provinceOptions = [];

		foreach ($provinces as $province) {
			$provinceOptions[$province->getID()] = $province->getName();
		}

		$content['provinceOptions'] = $provinceOptions;

        // city options
        $cities = $generalLocationService->getActiveChildLocations(LocationService::LOCATION_TYPE_CITY, $provinceID);
        $cityOptions = [];

        foreach ($cities as $city) {
        $cityOptions[$city->getID()] = $city->getName();
        }

		$content['cityOptions'] = $cityOptions;

		// building options
		$buildings = $apartmentGroupService->getBuildingsListForSelect();
		$buildingOptions = ['-- Choose Building --'];

		foreach ($buildings as $building) {
			$buildingOptions[$building['id']] = $building['name'];
		}

		$content['buildingOptions'] = $buildingOptions;

        /** @var \DDD\Dao\ApartmentGroup\BuildingSections $buildingSectionsDao */
        $buildingSectionsDao = $this->getServiceLocator()->get('dao_apartment_group_building_sections');
        $sections = $buildingSectionsDao->getSectionForBuilding($buildingId);

        $buildingSectionOptions = [];

        if (count($sections) > 1) {
            $buildingSectionOptions = ['-- Choose Section --'];
        }

        foreach ($sections as $section) {
            $buildingSectionOptions[$section['id']] = $section['name'];
        }

        $content['buildingSectionOptions'] = $buildingSectionOptions;
		return $content;
	}

    public function getBuildingSectionAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Dao\ApartmentGroup\BuildingSections $buildingSectionsDao
         */
        $request = $this->getRequest();
        $result = [];

        try {
            if ($request->isXmlHttpRequest()) {
                $buildingId = (int)$this->params()->fromRoute('building_id', 0);
                $buildingSectionsDao = $this->getServiceLocator()->get('dao_apartment_group_building_sections');

                if (!$buildingId) {
                    throw new \Exception('Bed Data');
                }

                $sections = $buildingSectionsDao->getSectionForBuilding($buildingId);
                foreach ($sections as $section) {
                    $result[] = [
                        'id'   => $section['id'],
                        'name' => $section['name'],
                    ];
                }
            }
        } catch (\Exception $e) {

        }

        return new JsonModel($result);
    }
}
