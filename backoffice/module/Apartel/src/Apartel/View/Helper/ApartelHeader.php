<?php

namespace Apartel\View\Helper;

use DDD\Service\Apartel\General;
use Library\Constants\TextConstants;
use Library\Constants\DomainConstants;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;


class ApartelHeader extends AbstractHelper {
    use ServiceLocatorAwareTrait;

    /**
     * @param $apartelId
     * @return string
     */
    public function __invoke($apartelId)
    {
        /**
         * @var \DDD\Dao\Apartel\General $generalDao
         * @var \DDD\Dao\Apartel\General $apartelDao
         */
        $generalDao = $this->getServiceLocator()->get('dao_apartel_general');
        $apartelDao = $this->getServiceLocator()->get('dao_apartel_general');

        // apartel header data
        $data  = $generalDao->getApartelDataForHeader($apartelId);
        if ($data) {
            if ($data['slug'] && $data['city_slug'] && $data['status'] == General::APARTEL_STATUS_ACTIVE) {
                $data['ws_link'] = '//' . DomainConstants::WS_DOMAIN_NAME . '/apartel/' . $data['slug'] . '--' . $data['city_slug'];
            }

            if ($data['apartment_group_id']) {
                $data['reservations_link'] = '//' . DomainConstants::BO_DOMAIN_NAME . '/booking?group=' . $data['apartment_group_id'];
            }
        }
        // cubilis connected
        $data['badges']['cubilis_connected'] = ['text' => TextConstants::CUBILIS_NOT_CONNECTED, 'class' => 'label-default'];
        if ($data['sync_cubilis']) {
            $data['badges']['cubilis_connected'] = ['text' => TextConstants::CUBILIS_CONNECTED, 'class' => 'label-success'];
        }

        // get currency
        $firstApartment = $apartelDao->getApartelCurrency($apartelId);
        if ($firstApartment) {
            $data['badges']['currency'] = ['text' => $firstApartment['code'], 'class' => 'label-warning'];
        }

		return $this->getView()->render('partial/header', $data);
	}
}
