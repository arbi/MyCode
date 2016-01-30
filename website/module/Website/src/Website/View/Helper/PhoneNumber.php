<?php

namespace Website\View\Helper;

use Website\View\Helper\BaseHelper;
use Zend\Session\Container as SessionContainer;

class PhoneNumber extends BaseHelper
{
    public function __invoke()
    {
        $session = new SessionContainer('visitor');
        $locationService = $this->serviceLocator->get('service_website_location');

        $countryId = $session->country_id;
        $phone = $locationService->getPhoneByCountryId($countryId);

        return $phone;
    }
}