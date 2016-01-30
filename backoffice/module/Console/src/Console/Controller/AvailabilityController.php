<?php
namespace Console\Controller;

use DDD\Service\Apartment\Inventory;
use Library\Constants\TextConstants;
use Library\Controller\ConsoleBase;

/**
 * Class AvailabilityController
 * @package Console\Controller
 */
class AvailabilityController extends ConsoleBase
{
    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', 'help');
        $dateFrom = $this->getRequest()->getParam('date-from', null);
	    $dateTo = $this->getRequest()->getParam('date-to', null);
        $rateId = $this->getRequest()->getParam('rate-id', null);

        switch ($action) {
            case 'help': $this->helpAction();
                break;
            case 'update-monthly': $this->updateMonthlyAction();
                break;
            case 'update-monthly-apartel': $this->updateMonthlyApartelAction();
                break;
	        case 'repair': $this->repairAction($dateFrom, $dateTo, $rateId);
		        break;
            default :
                echo '- type true parameter ( availability update-monthly | availability repair | availability help )'.PHP_EOL;
                return false;
        }
    }

    public function updateMonthlyAction() {
        try {
	        /**
	         * @var Inventory $inventoryService
	         */
	        $inventoryService = $this->getServiceLocator()->get('service_apartment_inventory');

            $this->outputMessage("Please wait until availability update ends.");

	        if ($inventoryService->updateAvailability(false, null)) {
                $this->outputMessage('Monthly availability update successful.');
	        } else {
		        throw new \Exception('Error! Something went wrong. Cannot update availability monthly.');
	        }

	        return true;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Update Monthly Availability Failed');

            $this->outputMessage($e->getMessage());

            return false;
        }
    }

	public function repairAction($dateFrom, $dateTo, $rateId) {
		try {
			/* @var $inventoryService Inventory */
			$inventoryService = $this->getServiceLocator()->get('service_apartment_inventory');

            $this->outputMessage('Please wait until availability update ends.');

			if ($inventoryService->repairAvailability($dateFrom, $dateTo, $rateId)) {
                $this->outputMessage('Repair successful.');
			} else {
				throw new \Exception('Error! Something went wrong. Cannot repair.');
			}

			return true;
		} catch (\Exception $e) {
            $this->gr2logException($e, 'Availability Repair Failed');

            $this->outputMessage($e->getMessage());

			return false;
		}
	}

    public function updateMonthlyApartelAction()
    {
        try {
            /**
             * @var \DDD\Service\Apartel\Inventory $inventoryService
             */
            $inventoryService = $this->getServiceLocator()->get('service_apartel_inventory');

            $this->outputMessage('Start...');

            if (!$inventoryService->updateAvailability(false, null)) {
                throw new \Exception(TextConstants::ERROR_NOT_UPDATE_AVAILABILITY_APARTEL);
            }

            $this->outputMessage('End.');

            return true;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Update Monthly Availability for Apartel Failed.');
            return false;
        }
    }

    public function helpAction() {
        echo '- type "ginosole availability update-monthly" to update availabilities for month.'.PHP_EOL;
        echo 'OR type "ginosole availability repair" update availability by date range and/or rate_id [--date-from=DATE_FROM] [--date-to=DATE_TO] [--rate-id=RATE_ID].'.PHP_EOL;
    }
}

