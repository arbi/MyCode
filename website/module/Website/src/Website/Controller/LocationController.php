<?php

namespace Website\Controller;

use Library\Controller\WebsiteBase;
use Zend\View\Model\ViewModel;
use Library\Utility\Helper;

class LocationController extends WebsiteBase
{

    public function indexAction()
    {
        try {
            /* @var $locationService \DDD\Service\Website\Location */
            $locationService = $this->getServiceLocator()->get('service_website_location');

            $cities = $locationService->getCityForLocation();
        } catch (\Exception $exc) {
            $cities = [];
        }

        $viewModel = new ViewModel([
            'cities' => $cities,
        ]);
        return $viewModel;
    }

    public function locationAction()
    {

        if (!$cityProvince = $this->params()->fromRoute('cityProvince')) {
            $this->getResponse()->setStatusCode(404);
            $viewModel = new ViewModel();

            return $viewModel->setTemplate('error/404');
        }

        /**
         * @var \DDD\Service\Website\Location $locationService
         */
        $locationService = $this->getServiceLocator()->get('service_website_location');

        // City data
        $city = $locationService->getCityByProvincCity($cityProvince);

        if (!$city['city_data']) {
            $this->getResponse()->setStatusCode(404);
            $viewModel = new ViewModel();

            return $viewModel->setTemplate('error/404');
        }

        $city_data = $city['city_data'];

        // City apartment
        $apartmentList = $locationService->getApartmentListByCity($city_data['id'], $city['city_url']);

        // Poi list
        $poiList = $locationService->getPoiListByCity($city_data['id'], $cityProvince);

        if ($poi = $this->params()->fromRoute('poi')) {
            $isCity = false;
            $individualData = $locationService->getPoiData($city_data['id'], $poi);
        } else {
            $isCity = true;
            $individualData = $locationService->getCityData($city_data['detail_id']);
        }

        if (!$individualData) {
            $this->getResponse()->setStatusCode(404);
            $viewModel = new ViewModel();

            return $viewModel->setTemplate('error/404');
        }

        $options = $locationService->getOptions($city);
        $options['city_id']          = $city['city_data']['id'];
        $options['city_url']         = $city['city_url'];
        $options['city_name']        = $city_data['city_name'];
        $options['city_name_as_url'] = $city['city_url'];
        $options['province_short_name'] = isset($city['city_data']['province_short_name']) ? $city['city_data']['province_short_name'] : '';

        $viewModel = new ViewModel([
            'apartmentList'  => $apartmentList,
            'poiList'        => $poiList,
            'individualData' => $individualData,
            'options'        => $options,
            'isCity'         => $isCity,
        ]);

        if ($isCity) {
            return $viewModel->setTemplate('website/location/location.phtml');
        } else {
            return $viewModel->setTemplate('website/location/poi.phtml');
        }
    }
}
