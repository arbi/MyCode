<?php

namespace Apartment\Controller;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Library\Controller\ControllerBase;
use Library\Constants\Objects;
use Library\Constants\Roles;
use Library\Constants\Constants;

use DDD\Service\Apartment\General as ApartmentGeneralService;
use DDD\Service\Location as LocationService;

use Library\Utility\CsvGenerator;

use Apartment\Form\SearchApartmentForm;
use Apartment\Form\SearchApartmentDocumentForm;

class ApartmentController extends ControllerBase
{
    CONST ONLY_APARTMENT_DOCUMENTS = 1;
    CONST ONLY_BUILDING_DOCUMENTS = 2;

    public function searchAction()
    {
        /* @var $auth \Library\Authentication\BackofficeAuthenticationService */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_APARTMENT_MANAGEMENT)) {
            return $this->redirect()->toRoute('home');
        }

        /**
         * @var \DDD\Service\Accommodations $accommodationsService
         */
        $accommodationsService = $this->getServiceLocator()->get('service_accommodations');

    	$formResources = $accommodationsService->prepareApartmentSearchFormResources();

    	$searchForm = new SearchApartmentForm('search_product', $formResources);
    	$searchForm->get('status')->setValue(Objects::PRODUCT_STATUS_SELLING);

    	return new ViewModel([
            'search_form' => $searchForm,
        ]);
    }

    /**
     * Get products json to use as source for datatable, filtered by params came from datatable
     */
    public function getApartmentSearchJsonAction()
    {
    	// get query parameters
    	$queryParams = $this->params()->fromQuery();

        /**
         * @var \DDD\Service\Accommodations $accommodationsService
         */
        $accommodationsService = $this->getServiceLocator()->get('service_accommodations');

    	// get products data
        $apartments     = $accommodationsService->getProductSearchResult($queryParams);

        /* @var $apartmentGeneralService ApartmentGeneralService */
        $apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');

        $notPermissions = $apartmentGeneralService->permissionChecker();

        // prepare products array
    	$filteredArray = [];

        foreach ($apartments as $apartment) {
            $apartmentUrlParams = ['apartment_id' => $apartment->getId()];
            $navigationItemClass = 'class="btn btn-small btn-link"';

            $documentsLinkDisabled  = 'disabled';
            $ratesLinkDisabled      = 'disabled';
            $calendarLinkDisabled   = 'disabled';
            $inventoryLinkDisabled  = 'disabled';
            $connectionLinkDisabled = 'disabled';
            $costsLinkDisabled      = 'disabled';

            if (!in_array('document', $notPermissions)) {
                $documentsLinkDisabled = '';
            }

            if (!in_array('cost', $notPermissions)) {
                $costsLinkDisabled = '';
            }

            if ($apartment->getStatus() != Objects::PRODUCT_STATUS_DISABLED) {

                $connectionLinkDisabled = '';

                if (!in_array('inventory-range', $notPermissions)) {
                    $ratesLinkDisabled = '';
                }

                if (!in_array('calendar', $notPermissions)) {
                    $calendarLinkDisabled = '';
                }

                if (!in_array('inventory-range', $notPermissions)) {
                    $inventoryLinkDisabled = '';
                }
            }

            $navigation =
                '<a href="'.$this->url()->fromRoute('apartment/general',            $apartmentUrlParams) . '" ' . $navigationItemClass . ' name="general"  title="general"><span class="glyphicon glyphicon-cog"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/details',            $apartmentUrlParams) . '" ' . $navigationItemClass . ' name="details"  title="details"><span class="glyphicon glyphicon-list"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/location',           $apartmentUrlParams) . '" ' . $navigationItemClass . ' name="location" title="location"><span class="glyphicon glyphicon-map-marker"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/media',              $apartmentUrlParams) . '" ' . $navigationItemClass . ' name="media"    title="media"><span class="glyphicon glyphicon-film"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/document',           $apartmentUrlParams) . '" ' . $navigationItemClass . ' ' . $documentsLinkDisabled . '   name="documents"    title="documents"><span class="glyphicon glyphicon-file"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/rate',               $apartmentUrlParams) . '" ' . $navigationItemClass . ' ' . $ratesLinkDisabled . '       name="rates"        title="rates"><span class="glyphicon glyphicon-tasks"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/calendar',           $apartmentUrlParams) . '" ' . $navigationItemClass . ' ' . $calendarLinkDisabled . '    name="calendar"     title="calendar"><span class="glyphicon glyphicon-calendar"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/inventory-range',    $apartmentUrlParams) . '" ' . $navigationItemClass . ' ' . $inventoryLinkDisabled . '   name="inventory"    title="inventory"><span class="glyphicon glyphicon-asterisk"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/channel-connection', $apartmentUrlParams) . '" ' . $navigationItemClass . ' ' . $connectionLinkDisabled . '  name="Connection"   title="Connection"><span class="glyphicon glyphicon-random"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/cost',               $apartmentUrlParams) . '" ' . $navigationItemClass . ' ' . $costsLinkDisabled . '       name="costs"        title="costs"><span class="glyphicon glyphicon-briefcase"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/statistics',         $apartmentUrlParams) . '" ' . $navigationItemClass . ' name="statistics"  title="statistics"><span class="glyphicon glyphicon-signal"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/review',             $apartmentUrlParams) . '" ' . $navigationItemClass . ' name="reviews"     title="reviews"><span class="glyphicon glyphicon-star-empty"></span></a>'.
                '<a href="'.$this->url()->fromRoute('apartment/history',            $apartmentUrlParams) . '" ' . $navigationItemClass . ' name="history"     title="history"><span class="glyphicon glyphicon-list-alt"></span></a>';

            $websiteLinkURL = $apartmentGeneralService->getWebsiteLink($apartment->getId());
            $websiteLinkDisabled = 'disabled';

            if (in_array($apartment->getStatus(), [Objects::PRODUCT_STATUS_LIVEANDSELLIG, Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE, Objects::PRODUCT_STATUS_REVIEW])) {
                $websiteLinkDisabled = '';
            }

            $externalLinks =
                '<a href="' . $this->url()->fromRoute('booking', ['apartment' => $apartment->getId()]) . '" class="btn btn-small btn-link" target="_blank" name="reservations" title="reservations"><span class="glyphicon glyphicon-user"></span></a>'.
                '<a href="' . $websiteLinkURL . '" class="btn btn-small btn-link" ' . $websiteLinkDisabled . ' name="web" title="web" target="_blank"><span class="glyphicon glyphicon-globe"></span></a>';

            $mainUrl = '<a href="' . $this->url()->fromRoute('apartment', $apartmentUrlParams) . '" name="Main View">' .
                $apartment->getName() . ($apartment->getUnitNumber() ? ' (' . $apartment->getUnitNumber() . ')' : '') . '</a>';

            $block = $apartment->getBlock();

            if (!empty($block)) {
                $block = ' (' . $block . ')';
            }

            $result = [
                $apartment->getStatusName(),
                $mainUrl,
                $apartment->getCity(),
                $apartment->getBuilding() . $block,
                date(Constants::GLOBAL_DATE_FORMAT, strtotime($apartment->getCreatedDate())),
                $navigation,
                $externalLinks
            ];

            $filteredArray[] = $result;
        }

    	// build response
    	$responseArray = [
            "aaData" => $filteredArray
    	];

    	return new JsonModel(
    		$responseArray
    	);
    }

    public function getSuppliersAction()
    {
        $request = $this->getRequest();

        try {
            if ($request->isXmlHttpRequest()) {
                $requestedSupplier = $request->getPost('query');

                /* @var $suppliersDao \DDD\Dao\Finance\Supplier */
                $suppliersDao = $this->getServiceLocator()->get('dao_finance_supplier');

                $suppliersList = $suppliersDao->getAllSuppliers($requestedSupplier, false);

                $result['status'] = 'success';

                foreach ($suppliersList as $key => $supplier) {
                    $result['result'][$key]['id']       = $supplier->getId();
                    $result['result'][$key]['name']     = $supplier->getName();
                    $result['result'][$key]['category'] = 'Suppliers';
                }
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['result'] = null;
        }

        return new JsonModel($result);
    }

    public function getAuthorsAction()
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

    public function getAutocompleteResultsAction()
    {
    	$request = $this->getRequest();
    	$query = strip_tags(trim($request->getPost('txt')));

        /**
         * @var \DDD\Service\Accommodations $accommodationsService
         */
        $accommodationsService = $this->getServiceLocator()->get('service_accommodations');

    	$result = [];

    	if (strlen($query) >= 3) {
    		$result = $accommodationsService->getProductsForAutocomplete($query);
    	}

    	return new JsonModel($result);
    }

    public function searchByAddressComponentsAction()
    {
    	$request = $this->getRequest();
    	$query   = strip_tags(trim($request->getPost('txt')));
        $mode    = (int)$request->getPost('mode');

        /**
         * @var \DDD\Service\Accommodations $accommodationsService
         */
        $accommodationsService = $this->getServiceLocator()->get('service_accommodations');

    	$result  = [];

    	if (strlen($query) >= 3) {
    		$result = $accommodationsService->getProductsByFullAddress($query, $mode);
    	}

    	return new JsonModel($result);
    }

    public function searchCountryAction()
    {
        $request = $this->getRequest();
        $result = ['rc' => '00', 'result' => []];

    	try {
    		if ($request->isXmlHttpRequest()) {
    			$txt = strip_tags(trim($request->getPost('txt')));

                /**
                 * @var \DDD\Service\Location $locationService
                 */
                $locationService = $this->getServiceLocator()->get('service_location');

    			$countries = $locationService->searchAutocomplate($txt, LocationService::LOCATION_TYPE_PROVINCE);

                if (!$countries) {
    				throw new \Exception("Bad data");
                }

    			$result['result'] = $countries;
    		}
    	} catch (\Exception $e) {
    		$result['rc'] = $e;
    	}

    	return new JsonModel($result);
    }

    public function searchCountryCityAction()
    {
        $justCity = $this->getRequest()->getQuery('justCity', false);
        $request  = $this->getRequest();
        $result   = ['rc' => '00', 'result' => []];
    	try {
    		if ($request->isXmlHttpRequest()) {
                $txt     = strip_tags(trim($request->getPost('txt')));

                /**
                 * @var \DDD\Service\Location $locationService
                 */
                $locationService = $this->getServiceLocator()->get('service_location');

                $cities  = $locationService->searchAutocomplate($txt, LocationService::LOCATION_TYPE_POI, 1);

                $countries = false;
                if (!$justCity) {
    			    $countries = $locationService->searchAutocomplate($txt, LocationService::LOCATION_TYPE_PROVINCE, 1);
                    $result['result'] = array_merge($countries, $cities);
                } else {
                    $result['result'] = $cities;
                }

                if (!$cities && !$countries) {
                    throw new \Exception("Bad data");
                }
    		}
    	} catch (\Exception $e) {

    		$result['rc'] = $e;
    	}

    	return new JsonModel($result);
    }

    public function getBuildingsAction()
    {
        $request    = $this->getRequest();

        /**
         * @var \DDD\Service\Location $locationService
         * @var \DDD\Service\ApartmentGroup $apartmentGroupService
         */
        $locationService        = $this->getServiceLocator()->get('service_location');
        $apartmentGroupService  = $this->getServiceLocator()->get('service_apartment_group');

        $auth = $this
            ->getServiceLocator()
            ->get('library_backoffice_auth');

        $hasDevTestRole = $auth->hasRole(Roles::ROLE_DEVELOPMENT_TESTING);

        try {
            if ($request->isXmlHttpRequest()) {
                $isApartel = false;
                $query     = $request->getPost('query');
                $isApartel = $request->getPost('isApartel');

                $buildings = $apartmentGroupService->getBuildingsByAutocomplate(
                    $query,
                    $isBuilding = false,
                    $isActive   = true,
                    $object     = true,
                    $isApartel,
                    $hasDevTestRole
                );

                $buildings = $buildings['result'];

                foreach ($buildings as $key => $value) {
                    $category = $isApartel ? 'Apartel' : 'Group';
                    $buildings[$key]['category'] = $category;
                }

                $countries = $locationService->searchAutocomplate(
                    $query,
                    LocationService::LOCATION_TYPE_PROVINCE,
                    true
                );

                $accGroupNames    = $apartmentGroupService->getBuildingsByCountry($countries);
                $result['status'] = 'success';
                $result['result'] = array_merge($accGroupNames, $buildings);

            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['result'] = null;
        }

        return new JsonModel($result);
    }

    public function getAutocompleteBuildingAction()
    {
        $request = $this->getRequest();
        $result  = ['rc' => '00', 'result' => []];

        $auth = $this
            ->getServiceLocator()
            ->get('library_backoffice_auth');

        $hasDevTestRole = $auth->hasRole(Roles::ROLE_DEVELOPMENT_TESTING);

        try {
            if ($request->isXmlHttpRequest()) {
                $query   = strip_tags(trim($request->getPost('txt')));
                $service = $this->getServiceLocator()->get('service_apartment_group');

                $buildings = $service->getBuildingsByAutocomplate(
                    $query,
                    $isBuilding = true,
                    $isActive   = true,
                    $object     = false,
                    $isApartel  = true,
                    $hasDevTestRole
                );

                $buildings = $buildings['result'];

                foreach ($buildings as $key => $value) {
                    $buildings[$key]['category'] = 'building';
                }

                $result['result'] = $buildings;
            }
        } catch (\Exception $e) {
            $result['rc'] = $e;
        }

        return new JsonModel($result);
    }

    public function downloadCsvAction()
    {
        $requestParams = $this->params()->fromQuery();

        /* @var $auth \Library\Authentication\BackofficeAuthenticationService */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        /* @var $apartmentDocumentService \DDD\Service\Apartment\Document */
        $apartmentDocumentService = $this->getServiceLocator()->get('service_apartment_document');
        $documents                = $apartmentDocumentService->getApartmentDocumentSearchResult($requestParams);

        $result = [];

        if (count($documents) > 0) {
            foreach ($documents as $document) {
                $documentDescriptionCleaup = str_replace('&nbsp;', ' ', strip_tags($document->getDescription(), '<br>'));

                $result[] = [
                    'Apartment Name' => $document->getApartmentName(),
                    'Security Level' => $document->getTeamName(),
                    'Document Type'  => $document->getTypeName(),
                    'Supplier'       => $document->getSupplierName(),
                    'Account Number' => $document->getAccountNumber(),
                    'Account Holder' => $document->getAccountHolder(),
                    'Description'    => $documentDescriptionCleaup,
                    'Created Date'   => $document->getDateCreated(),
                    'Has Attachment' => (empty($document->getAttachment())) ? '-' : '+',
                    'Has url'        => (empty($document->getUrl())) ? '-' : '+',
                    'Valid From'     => $document->getValidFrom(),
                    'Valid To'       => $document->getValidTo(),
                    'Legal Entity'   => $document->getLegalEntityName(),
                    'Signatory'      => $document->getSignatoryFullName(),
                ];

            }

            $response = $this->getResponse();
    		$headers  = $response->getHeaders();

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
