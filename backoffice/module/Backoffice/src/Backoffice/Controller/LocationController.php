<?php

namespace Backoffice\Controller;

use DDD\Domain\Geolocation\Details;
use Zend\Form\Form;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;

use Library\Constants\DomainConstants;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Library\Constants\DbTables;
use Library\ActionLogger\Logger;
use Library\Constants\Constants;

use Backoffice\Form\Location as LocationForm;
use Backoffice\Form\InputFilter\LocationFilter as LocationFilter;

use DDD\Service\Location as LocationService;

class LocationController extends ControllerBase
{
	/**
	 * @access protected
	 * @var \DDD\Service\Location
	 */
    protected $locationService = null;

    public function indexAction()
    {
        /**
         * @var \DDD\Service\Location $locationService
         */
        $locationService = $this->getServiceLocator()->get('service_location');

	    $countries = $locationService->getAllActiveCountries();

	   return array(
		   'activeCountries' => $countries,
	   );
    }

    public function ajaxsearchAction()
    {
        $result = array('result'=>array(), 'status'=>'success', 'msg'=>'');
        $request = $this->getRequest();

        try {
            if ($request->isXmlHttpRequest()) {
                /**
                 * @var \DDD\Service\Location $locationService
                 */
                $locationService = $this->getServiceLocator()->get('service_location');

                $txt = strip_tags(trim($request->getPost('txt')));

                $response = $locationService->getLocationByTxt($txt);
               $result['result'] = $response;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function searchCountryAction()
    {
    	$result = array('rc'=>'00', 'result'=>array());
    	$request = $this->getRequest();
    	try{
    		if($request->isXmlHttpRequest()) {
    			$txt     = strip_tags(trim($request->getPost('txt')));

                /**
                 * @var \DDD\Service\Location $locationService
                 */
                $locationService = $this->getServiceLocator()->get('service_location');

    			$countries  = $locationService->searchAutocomplate($txt, LocationService::LOCATION_TYPE_PROVINCE);
    			if (!$countries) {
                    throw new \Exception("Bad data");
                }
                $result['result'] = $countries;
    		}
    	} catch (\Exception $e) {
    		$result['rc'] = '01';
    	}
    	return new JsonModel($result);
    }

    /**
     * @param int $id
     * @param int $type
     * @return array
     */
    protected function getForm($id, $type)
    {
        /**
         * @var \DDD\Service\Location $locationService
         */
        $locationService = $this->getServiceLocator()->get('service_location');

        $location_data = '';

	    if ($id > 0) {
            $location_data = $locationService->getLocationById((int)$id, $type);
        }

        $location_option = $locationService->getLocationType();

        return [
            'form' => new LocationForm('location', $location_data, $location_option, $type),
            'data' => $location_data,
        ];
    }

    public function editAction()
    {
        /**
         * @var Form $form
         * @var Details $parent
         * @var \DDD\Service\Location $locationService
         */
        $locationService = $this->getServiceLocator()->get('service_location');

        $id              = $this->params()->fromRoute('id', 0);
        $type            = '';
        $type_view       = '';
        $parent_view     = '';
        $location_id     = 0;
        $url             = '';
        $response        = [
            'parentTxt'   => '',
            'searchArray' => [],
        ];

        if ($id != 0) {
            $idType      = explode('-', $id);
            $id          = (isset($idType[0]) && $idType[0] > 0) ? $idType[0] : 0;
            $type        = (isset($idType[2])) ? $idType[2] : '';
            $location_id = (isset($idType[1]) && $idType[1] > 0) ? $idType[1] : 0;

            if (!$id || !$location_id || !$this->getServiceLocator()->get('dao_geolocation_details')->checkRowExist(DbTables::TBL_LOCATION_DETAILS, 'id', $id)) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                return $this->redirect()->toRoute('backoffice/default', ['controller' => 'location']);
            }

            $url = '//' . DomainConstants::WS_DOMAIN_NAME;
            $response = $locationService->getOptions($location_id, $type);

            switch ($type) {
                case LocationService::LOCATION_TYPE_COUNTRY:
                    $type_view   = 'Provinces';
                    $parent_view = 'Continent';

                    break;
                case LocationService::LOCATION_TYPE_PROVINCE:
                    $type_view   = 'Cities';
                    $parent_view = 'Country';

                    /* @var $parent \DDD\Domain\Geolocation\Details */
                    $parent = $this->getServiceLocator()->get('dao_geolocation_details')->getParentDetail(
                        $location_id,
                        'country_id',
                        DbTables::TBL_PROVINCES,
                        DbTables::TBL_COUNTRIES
                    );

                    if (is_object($parent)) {
                        $url .= '/'.  $parent->getSlug();
                    }

                    break;
                case LocationService::LOCATION_TYPE_CITY:
                    $type_view       = 'Points of Interest';
                    $parent_view     = 'Province';
                    $urlResp         = $locationService->getProcCountByCityId($location_id);
                    $url             .= $urlResp;
                    $parentCurrency  = $locationService->getParentCountryCurrencyByCityId($location_id);

                    break;
                case LocationService::LOCATION_TYPE_POI:
                    $type_view   = 'Points of Interest';
                    $parent_view = 'City';
                    $urlResp     = $locationService->getProcvincCountryCityByPoiId($location_id);
                    $url         .= $urlResp;

                    break;
            }
        }

        $formData   = $this->getForm($id, $type);
        $form       = $formData['form'];
        $logsAaData = [];

        if ($location_id > 0) {
            $locationLogs   = $locationService->getLocationLogs($id);
            $editLocationId = $form->get('edit_location_id');
            $editLocationId->setValue($location_id);
            $locationLogsArray = [];

            if (count($locationLogs) > 0) {
                foreach ($locationLogs as $log) {
                    $rowClass = '';

                    if ($log['user_name'] == TextConstants::SYSTEM_USER) {
                        $rowClass = "warning";
                    }

                    $locationLogsArray[] = [
                        date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($log['timestamp'])),
                        $log['user_name'],
                        $this->identifyLocationAction($log['action_id']),
                        $log['value'],
                        "DT_RowClass" => $rowClass
                    ];
                }
            }

            $logsAaData = $locationLogsArray;
        }

        $logsAaData = json_encode($logsAaData);

        return [
            'locationForm'    => $form,
            'edit'            => $id,
            'type'            => $type,
            'type_view'       => $type_view,
            'parent_view'     => $parent_view,
            'parent_text'     => $response['parentTxt'],
            'sub'             => $response['searchArray'],
            'imgDomain'       => DomainConstants::IMG_DOMAIN_NAME,
            'urlSie'          => $url,
            'logsAaData'      => $logsAaData,
            'parentCurrency'  => (isset($parentCurrency))
                ? $parentCurrency
                : null,
        ];
    }

    public function ajaxUploadImageAction() {
        $result = [
            'src'    => '',
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE
        ];

        try {
            /**
             * @var \DDD\Service\Location $locationService
             */
            $locationService = $this->getServiceLocator()->get('service_location');

            $request = $this->getRequest();

            if ($request->isXmlHttpRequest()) {
                $logoFile = $request->getFiles();
                $img_type = $request->getPost('img');
                $tempName = $locationService->saveToTemp([$logoFile['file']]);
                $result   = $tempName;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxsaveAction() {
	    /** @var Request $request */
        $request = $this->getRequest();
	    $result  = [
            'result' => array(),
            'id'     => 0,
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE
	    ];

        try {
            if ($request->isXmlHttpRequest()) {
                $id = (int)$request->getPost('edit_id');

                if ($id > 0) {
                    $type = $request->getPost('type_location');
                } else {
                    $type = $request->getPost('add_type');
                }

                $formData = $this->getForm($id, $type);
                /** @var LocationForm $form */
                $form     = $formData['form'];
                $messages = '';
                $form->setInputFilter(new LocationFilter($type, $id));

                if ($request->isPost()) {

                    $filter = $form->getInputFilter();
                    $form->setInputFilter($filter);
                    $data = $request->getPost();
                    $form->setData($data);
                    if ($form->isValid()) {
                        $vData = $form->getData();

                        $slugRegenerate = $request->getPost('slug') ? true : false;

                        /** @var \DDD\Service\Location $locationService */
                        $locationService = $this->getServiceLocator()->get('service_location');

                        $saveResult = $locationService->locationSave($vData, $id, $type, $slugRegenerate);

                        if (is_array($saveResult)) {
                            if (isset($saveResult['status']) && $saveResult['status'] == 'error') {
                                $result = $saveResult;
                            }

                            if (!$id) {
                                $result['id']          = $saveResult[0];
                                $result['type']        = $type;
                                $result['location_id'] = $saveResult[1];
                                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                            }
                        } else {
                            $result['status'] = 'error';
                            $result['msg']    = TextConstants::SERVER_ERROR;
                        }

                    } else {
                        $errors = $form->getMessages();
                        foreach ($errors as $key => $row) {
                            if (!empty($row)) {
                                if ($key == 'autocomplete_id') {
                                    switch ($type) {
                                        case LocationService::LOCATION_TYPE_COUNTRY:
                                            $key = 'Continent';
                                            break;
                                        case LocationService::LOCATION_TYPE_PROVINCE:
                                            $key = 'Country';
                                            break;
                                        case LocationService::LOCATION_TYPE_CITY:
                                            $key = 'Province';
                                            break;
                                        case LocationService::LOCATION_TYPE_POI:
                                            $key = 'City';
                                            break;
                                    }
                                }

                                $messages .= ucfirst($key) . ' ';
                                $messages_sub = '';

                                foreach ($row as $keyer => $rower) {
                                    $messages_sub .= $rower;
                                }

                                $messages .= $messages_sub . '<br>';
                            }
                        }
                        $result['status'] = 'error';
                        $result['msg']    = $messages;
                    }
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    public function ajaxremoveAction()
    {
        $result = [
            'src'    =>'',
            'status' =>'success',
            'msg'    =>'Image successfully removed'
        ];

        try
        {
            $request = $this->getRequest();
            $val     = (int)$request->getPost('val');
            $id      = (int)$request->getPost('id');

            if($request->isXmlHttpRequest() && $val > 0 && $id > 0) {
                /**
                 * @var \DDD\Service\Location $locationService
                 */
                $locationService = $this->getServiceLocator()->get('service_location');

                $locationService->removeImage($val, $id);
            } else {
                throw new \Exception("Bad data");
            }
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    public function ajaxgetparentAction()
    {
        $result = array('rc'=>'00', 'result'=>array());
        $request = $this->getRequest();
        try{
            if($request->isXmlHttpRequest()) {
                $txt           = strip_tags(trim($request->getPost('txt')));
                $edit_id       = (int)$request->getPost('edit_id');
                $type          = $request->getPost('type');
                $type_location = $request->getPost('type_location');
                $use_type      = '';
                if($edit_id > 0 && in_array($type_location, LocationService::$locationTypes)){
                    $use_type = $type_location;
                } elseif(in_array($type, LocationService::$locationTypes)) {
                    $use_type = $type;
                } else {
                    throw new \Exception("Bad data");
                }

                /**
                 * @var \DDD\Service\Location $locationService
                 */
                $locationService = $this->getServiceLocator()->get('service_location');

                $parents  = $locationService->searchAutocomplate($txt, $use_type);
                if (!$parents) {
                    throw new \Exception("Bad data");
                }

                $result['result'] = $parents;
            }
        } catch (\Exception $e) {
            $result['rc'] = '01';
        }
        return new JsonModel($result);
    }

    public function ajaxDeleteCheckAction()
    {
        $result = array('status'=>'success', 'msg'=>TextConstants::SUCCESS_UPDATE);
        try
        {
            $request = $this->getRequest();
            $type    = $request->getPost('type');
            $id      = (int)$request->getPost('id');
            if($request->isXmlHttpRequest() && $id > 0) {
                /**
                 * @var \DDD\Service\Location $locationService
                 */
                $locationService = $this->getServiceLocator()->get('service_location');

                $childExist = $locationService->checkChildExist($id, $type);
                if ($childExist) {
                    $result['status'] = 'error';
                    $result['msg']    = 'Cannot delete location because this location has child locations';
                }
            } else {
                throw new \Exception("Bad data");
            }
        } catch (Exception $e){
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    public function ajaxDeleteLocationAction()
    {
        $result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_DELETE
        ];

        try
        {
            $request   = $this->getRequest();
            $type      = $request->getPost('type');
            $id        = (int)$request->getPost('id');
            $detail_id = (int)$request->getPost('detail_id');

            if($request->isXmlHttpRequest() && $id > 0 && $detail_id > 0) {
                /**
                 * @var \DDD\Service\Location $locationService
                 */
                $locationService = $this->getServiceLocator()->get('service_location');

                $response = $locationService->deleteLocation($id, $type, $detail_id);
                if ($response) {
                    Helper::setFlashMessage(['success'=>  TextConstants::SUCCESS_DELETE]);
                } else {
                   $result['status'] = 'error';
                   $result['msg']    = TextConstants::SERVER_ERROR;
                }
            } else {
                throw new \Exception("Bad data");
            }
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    /**
     *
     * @param int $actionId
     * @return string
     */
    private function identifyLocationAction($actionId)
    {
        $locationActions = [
            Logger::ACTION_LOCATION_SEARCHABLE  => 'Is Searchable',
            Logger::ACTION_LOCATION_NAME        => 'Location Name',
            Logger::ACTION_LOCATION_INFORMATION => 'Information Text',
        ];

        if (isset($locationActions[$actionId])) {
            return $locationActions[$actionId];
        }

        return 'not defined';
    }
}
