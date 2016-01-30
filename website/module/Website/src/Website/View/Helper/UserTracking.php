<?php

namespace Website\View\Helper;

use DDD\Dao\User\UserManager;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;
use Library\Utility\Helper;


class UserTracking extends AbstractHelper
{
    use ServiceLocatorAwareTrait;

    private $_textLineSite = null;

    public function __invoke($data = [])
    {
        /**
         * @var UserManager $userDao
         */
        $userId = false;
        $result = [
            'boUser' => 'no',
        ];

        if (isset($_COOKIE['backoffice_user'])) {
            $userId = (int)$_COOKIE['backoffice_user'];
        }

        if ($userId) {
            $userDao = $this->serviceLocator->get('dao_user_user_manager');
            $userInfo = $userDao->getUserTrackingInfo($userId);

            if ($userInfo) {
                $result = [
                    'boUser'   => 'yes',
                    'uid'      => strval($userId),
                    'userCity' => $userInfo['city'],
                    'userDept' => $userInfo['department'],
                ];
            }
        }

        $router = $this->getServiceLocator()->get('application')->getMvcEvent()->getRouteMatch();
        if ($router) {
            $action = $router->getParam('action', 'index');
            $controller = $router->getParam('controller', 'index');

            $controllerArray = explode("\\", $controller);
            $controller = strtolower(array_pop($controllerArray));
            if ($controller == 'booking' && $action == 'thank-you') {
                $session_booking = Helper::getSessionContainer('booking');
                if ($session_booking->offsetExists('reservation') && $session_booking->offsetExists('tankyou') && isset($_SERVER['HTTPS'])) {
                    $reservation = $session_booking->reservation;
                    $thankYou    = $session_booking->tankyou;

                    $variant  = ($reservation['bedroom_count'] > 0) ? $reservation['bedroom_count'] . ' ' .$this->getTextline(1446) : $this->getTextline(1190);
                    $cityName = $this->getServiceLocator()->get('service_textline')->getCityName($reservation['city_id']);

                    $result['page_httpResponseCode'] = "200";
                    $result['transactionAffiliation'] = $thankYou['partner'];
                    $result['transaction_currency'] = "USD";
                    $result['transaction_subtotal_include_tax'] = "no";
                    $result['transactionId'] = $thankYou['res_number'];
                    $result['transactionTotal'] = $thankYou['totalWithoutTax'];
                    $result['transactionProducts'] = [
                        'sku' => $reservation['prod_id'],
                        'name' => $reservation['prod_name'],
                        'category' => $cityName,
                        'variant' => $variant,
                        'price' => $thankYou['totalWithoutTax'],
                        'quantity' => "1"
                    ];

                    $result['ticketID']  = $thankYou['res_number'];
                    $result['PartnerID'] = $data['partner_id'];
                }
            } elseif (($controller == 'key' && $action == 'index')
                      || ($controller == 'booking' && $action == 'update-cc-details')
                      || ($controller == 'review' && $action == 'index')
                      || ($controller == 'chargeauthorization' && $action == 'index')
            ){
                $result['ticketID']  = $data['res_number'];
                $result['PartnerID'] = $data['partner_id'];
            }
        }

        return '<script>dataLayer = [' . json_encode($result) . '];</script>';
    }

    private function getTextline($id){
        if ($this->_textLineSite === null) {
            $helperTextLine = new Textline();
            $helperTextLine->setServiceLocator($this->getServiceLocator());
            $this->_textLineSite = $helperTextLine;
        }
        return $this->_textLineSite->getFromCache($id);
    }
}
