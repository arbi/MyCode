<?php
namespace Console\Controller;

use Library\Controller\ConsoleBase;
use \DDD\Service\Parking\Spot\Inventory;

/**
 * Class ParkingController
 * @package Console\Controller
 */
class ApiRequestController extends ConsoleBase
{
    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', false);

        switch ($action) {
            case 'delete-expired-request': $this->DeleteExpiredRequestAction();
                break;
            default :
                echo '- type delete-expired-request'.PHP_EOL;
                return false;
        }
    }

    public function DeleteExpiredRequestAction()
    {
        try {
            $apirequestDao = $this->getServiceLocator()->get('dao_api_api_requests');

            $apirequestDao->deleteExpiredRequest();
            $this->outputMessage('Expired API request has been deleted.');

            return true;
        } catch (\Exception $e) {

            $this->outputMessage($e->getMessage());

            return false;
        }
    }
}
