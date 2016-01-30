<?php

namespace DDD\Service\Apartel;

use DDD\Service\ServiceBase;

class Calendar extends ServiceBase
{
    /**
     * @param $apartelId
     * @param $roomTypeId
     * @param $year
     * @param $month
     * @return array
     */
    public function getCalendarData($apartelId, $roomTypeId, $year, $month)
    {
        /**
         * @var \DDD\Dao\Apartel\Rate $rateDao
         * @var \DDD\Dao\Apartel\Inventory $inventoryDao
         * @var \DDD\Dao\Apartel\Type $roomTypeDao
         */
        $rateDao = $this->getServiceLocator()->get('dao_apartel_rate');
        $inventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');
        $roomTypeDao = $this->getServiceLocator()->get('dao_apartel_type');

        $weekDays = array (
            'Sunday'    => 0,
            'Monday'    => 1,
            'Tuesday'   => 2,
            'Wednesday' => 3,
            'Thursday'  => 4,
            'Friday'    => 5,
            'Saturday'  => 6
        );
        $givenMonthName = date("F", mktime(0, 0, 0, $month, 10)); // get given month name

        $firstDayOfGivenMonthTimestamp = strtotime('first day of ' . $year . '-' . $month); // get first day of given month in milliseconds
        $firstDayOfGivenMonthDate = getdate($firstDayOfGivenMonthTimestamp); // get date array from timestamp

        $dayOfWeek = $weekDays[$firstDayOfGivenMonthDate['weekday']]; // get day of week for given month first day to correctly render calendar
        $givenMonthDaysCount = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // get room type rates
        $rates = $rateDao->getRoomTypeRates($roomTypeId);
        $rates = iterator_to_array($rates);

        // building inventory array
        $inventory = [];
        foreach ($rates as $rate) {

            $rateID = $rate['id'];
            $firstDay = date('Y-m-d', strtotime('first day of ' . $year . '-' . $month));
            $lastDay = date('Y-m-d', strtotime('last day of ' . $year . '-' . $month));
            $rateAvailability = $inventoryDao->getRateInventoryForRange($rateID, $firstDay, $lastDay);

            foreach ($rateAvailability as $singleDayAvailability) {
                $inventory[$rateID][$singleDayAvailability['date']] = [
                    "availability" => $singleDayAvailability['availability'],
                    "price" => $singleDayAvailability['price'],
                    "isLockPrice" => $singleDayAvailability['is_lock_price'],
                ];
            }
        }

        // check connected cubilis
        $isConnected = $roomTypeDao->getApartelTypeSyncWithCubilis($roomTypeId);

        $date = new \DateTime();
        $date->setDate($year, $month, 1);
        $monthStart = $date->format('Y-m-d');

        if ($monthStart < date('Y-m-d', strtotime('-1 days'))) {
            $monthStart = date('Y-m-d', strtotime('-1 days'));
        }

        $date->setDate($year, $month, $givenMonthDaysCount);
        $monthEnd = $date->format('Y-m-d');

        return [
            'rates' => $rates,
            'dayOfWeek' => $dayOfWeek,
            'givenMonthDaysCount' => $givenMonthDaysCount,
            'inventory' => $inventory,
            'isConnected' => $isConnected,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
        ];
    }
}
