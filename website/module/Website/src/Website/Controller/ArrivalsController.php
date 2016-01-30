<?php

namespace Website\Controller;

use DDD\Service\Website\Arrivals;
use Library\Controller\WebsiteBase;
use Zend\View\Model\ViewModel;
use Library\Constants\Constants;

/**
 * Class ArrivalsController
 * @package Website\Controller
 *
 * @author Tigran Petrosyan
 */
class ArrivalsController extends WebsiteBase
{
    public function indexAction()
    {

    }

    public function conciergeAction()
    {
        /**
         * @var Arrivals $arrivalsService
         */
        $arrivalsService = $this->getServiceLocator()->get('service_website_arrivals');
        $apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');

        $apartmentGroupId = $this->params()->fromRoute('apartment_group_id');
        $date             = $this->params()->fromRoute('date');

        $hashRequest = $this->params()->fromQuery('key' , 0);

        $groupInfo = $apartmentGroupService->getConciergeByGroupId($apartmentGroupId);
        $now       = new \DateTime(null, new \DateTimeZone($groupInfo->getTimezone()));
        $now       = strtotime($now->format('Y-m-d'));
        $yesterday = strtotime('-1 day', $now);

        $groupName = strtolower(preg_replace('/\s/', '', $groupInfo->getName()));

        if (strlen($groupName) >= 4) {
            $groupName = substr($groupName, 0, 4);
        }

        $key = $groupName . $groupInfo->getId() . $date;

        $ivSize        = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $ciphertextDec = base64_decode($hashRequest);
        $ivDec         = substr($ciphertextDec, 0, $ivSize);
        $ciphertextDec = substr($ciphertextDec, $ivSize);

        $requestDate = rtrim(
            mcrypt_decrypt(
                MCRYPT_RIJNDAEL_128,
                $key,
                $ciphertextDec,
                MCRYPT_MODE_CBC,
                $ivDec
            )
        );

        $validDate = [$now, $yesterday];
        $isExpired = false;
        $arrivals  = null;
        $when      = null;

        if (!in_array(strtotime($requestDate), $validDate)) {
            $isExpired = true;
        } else {
            if (strtotime($requestDate) == $now) {
               $when = 'Today';
            } else {
                $when = 'Yesterday';
            }

            $arrivals = $arrivalsService->getArrivals($apartmentGroupId, $requestDate);
        }

        $viewModel = new ViewModel(
            [
                'isExpired' => $isExpired,
                'arrivals'  => $arrivals,
                'title'     => 'The Guests of ' . $groupInfo->getName() . ' for ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($date)),
                'when'      => $when
            ]
        );
        return $viewModel;
    }

}
