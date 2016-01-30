<?php

namespace Parking\Controller;

use Library\Constants\TextConstants;
use Parking\Controller\Base as ParkingBaseController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Library\Utility\Helper;

class Calendar extends ParkingBaseController
{
    public function indexAction()
    {
        /**
         * @var \DDD\Service\Parking\Spot $parkingSpotService
         * @var \DDD\Service\Parking\Spot\Inventory $spotInventoryService
         */
        $parkingSpotService   = $this->getServiceLocator()->get('service_parking_spot');
        $spotInventoryService = $this->getServiceLocator()->get('service_parking_spot_inventory');

        /*
         * @todo move out this
         */
        $weekDays = array (
            'Sunday'    => 0,
            'Monday'    => 1,
            'Tuesday'   => 2,
            'Wednesday' => 3,
            'Thursday'  => 4,
            'Friday'    => 5,
            'Saturday'  => 6
        );

        // get main params from route, month and year
        $year = $this->params()->fromRoute('year', 0);
        $month = $this->params()->fromRoute('month', 0);

        if ($year && $month) {
            $givenMonthName = date("F", mktime(0, 0, 0, $month, 10)); // get given month name

            // get first day of given month in miliseconds
            $firstDayOfGivenMonthTimestamp = strtotime('first day of ' . $year . '-' . $month);

            // get date array from timestamp
            $firstDayOfGivenMonthDate = getdate($firstDayOfGivenMonthTimestamp);

            // get day of week for given month first day to correctly render calendar
            $dayOfWeek = $weekDays[$firstDayOfGivenMonthDate['weekday']];

            $givenMonthDaysCount = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            $spots = $parkingSpotService->getParkingSpots($this->parkingLotId)->buffer();

            // building inventory array
            $inventory = [];

            foreach ($spots as $spot) {
                $spotId = $spot->getId();
                $spotAvailability = $spotInventoryService->getSpotAvailabilityForMonth($spotId, $year, $month);

                foreach ($spotAvailability as $singleDayAvailability) {
                    $inventory[$spotId][$singleDayAvailability->getDate()] = [
                        "availability" => $singleDayAvailability->getAvailability(),
                        "price" => $singleDayAvailability->getPrice(),
                    ];
                }
            }

            $urlUpdateAvailabilities = $this->url()->fromRoute(
                'parking/calendar/update-availabilities',
                ['parking_lot_id' => $this->parkingLotId,
                'year' => date('Y'), 'month' => date('m')]
            );

            $date = new \DateTime();
            $date->setDate($year, $month, 1);
            $monthStart = $date->format('Y-m-d');

            if ($monthStart < date('Y-m-d', strtotime('-1 days'))) {
                $monthStart = date('Y-m-d', strtotime('-1 days'));
            }

            $date->setDate($year, $month, $givenMonthDaysCount);
            $monthEnd = $date->format('Y-m-d');

            return new ViewModel([
                'parkingLotId'            => $this->parkingLotId,
                'year'                    => $year,
                'month'                   => $month,
                'givenMonthName'          => $givenMonthName,
                'givenMonthDaysCount'     => $givenMonthDaysCount,
                'dayOfWeek'               => $dayOfWeek,
                'spots'                   => $spots,
                'inventory'               => $inventory,
                'urlUpdateAvailabilities' => $urlUpdateAvailabilities,
                'monthStart'              => $monthStart,
                'monthEnd'                => $monthEnd,
            ]);
        } else {
            return $this->redirect()->toRoute('parking/calendar', [
                "year" => date('Y'),
                "month" => date('m')
            ], [], true);
        }
    }

    public function ajaxUpdateSpotAvailabilitiesAction()
    {
        /**
         * @var \DDD\Service\Parking\Spot\Inventory $spotInventoryService
         * @var \DDD\Dao\Parking\Spot\Inventory $inventoryDao
         */
        $inventoryDao = $this->getServiceLocator()->get('dao_parking_spot_inventory');
        $request = $this->getRequest();

        $output = [
            'bo' => ['status' => 'success', 'msg' => TextConstants::SUCCESS_UPDATE],
        ];

        try {
            $date = $request->getPost('date', null);
            $availability = $request->getPost('availability', null);
            if ($date) {
                $date = current(explode(' ', $date));
            }

            if ($request->isPost() && $request->isXmlHttpRequest()) {
                if (strtotime($date) !== false && is_array($availability)) {
                    foreach($availability as $spotId => $spotAvailability) {
                        $inventoryDao->save(
                            ['availability' => (int)$spotAvailability],
                            [
                                'spot_id' => $spotId, 'date' => $date
                            ]);
                        $message = ['success' => TextConstants::SUCCESS_UPDATE];
                        Helper::setFlashMessage($message);
                    }
                } else {
                    $output['bo']['msg'] = 'Bad parameters.';
                }
            } else {
                $output['bo']['msg'] = 'Bad request.';
            }
        } catch (\Exception $ex) {
            $output['bo']['msg'] = $ex->getMessage();
        }

        return new JsonModel($output);
    }

    public function ajaxToggleAvailabilityAction()
    {
        /**
         * @var \DDD\Service\Parking\Spot\Inventory $spotInventoryService
         * @var \DDD\Dao\Parking\Spot\Inventory $inventoryDao
         */
        $inventoryDao = $this->getServiceLocator()->get('dao_parking_spot_inventory');
        $request = $this->getRequest();

        $output = [
            'bo' => ['status' => 'success', 'msg' => TextConstants::SUCCESS_UPDATE],
        ];

        try {
            $date = $request->getPost('date', null);
            $action = $request->getPost('action', null);
            if ($date) {
                $date = current(explode(' ', $date));
            }

            if ($request->isPost() && $request->isXmlHttpRequest()) {
                if (strtotime($date) !== false && in_array($action, ['open', 'close'])) {
                    $parkingLotId = $this->parkingLotId;
                    $availability = ($action == 'open' ? 1 : 0);

                    $inventoryDao->updateParkingLotAvailability($parkingLotId, $date, $availability);
                } else {
                    $output['bo']['msg'] = 'Bad parameters.';
                }
            } else {
                $output['bo']['msg'] = 'Bad request.';
            }
        } catch (\Exception $ex) {
            $output['bo']['msg'] = $ex->getMessage();
        }

        return new JsonModel($output);
    }
}
