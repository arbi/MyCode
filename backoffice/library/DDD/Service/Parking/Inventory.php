<?php

namespace DDD\Service\Parking;

use DDD\Service\ServiceBase;
use Library\Utility\Helper;

class Inventory extends ServiceBase
{
    const cellWidth = 60;
    const cellHeight = 25;
    const cellHeightPrint = 42;
    const cellThHeight = 50;
    const paddingH = 2;
    const paddingV = 2;
    const borderSize = 1;
    const bgColor = '81C784';

    /**
     * @return array
     */
    public function getIndexData()
    {
        /** @var \DDD\Dao\Parking\General $lotDao */
        $lotDao = $this->getServiceLocator()->get('dao_parking_general');
        $lots = $lotDao->getAllLots();

        return [
            'lots' => $lots
        ];
    }

    /**
     * @param $lotId
     * @param $dateRange
     * @return array
     */
    public function getViewData($lotId, $dateRange)
    {
        // date range convert to date from to
        $dateRangeArray = explode(' - ', $dateRange);
        $from = (isset($dateRangeArray[0]) && $dateRangeArray[0] != '') ? $dateRangeArray[0] : date('Y-m-d');
        $to = (isset($dateRangeArray[1]) && $dateRangeArray[1] != '') ? $dateRangeArray[1] : date('Y-m-d', strtotime('today + 7 days'));

        /**
         * @var \DDD\Dao\Parking\Spot $spotDao
         */
        $spotDao = $this->getServiceLocator()->get('dao_parking_spot');

        // spot list
        $spots = $spotDao->getAllSpotsByLot($lotId);
        $spots = iterator_to_array($spots);

        // spot order
        $spotsOrder = [];
        foreach ($spots as $key => $spot) {
            $spotsOrder[$spot['id']] = $key;
        }

        // day count
        $currDate = $from;
        $daysOrder = [];
        $dayCount = 0;
        while ($currDate <= $to) {
            $daysOrder[$currDate] = $dayCount;
            $dayCount++;
            $currDate = date('Y-m-d', strtotime($currDate . ' +1 day'));
        }

        // table width
        $tableWidth = $dayCount*self::cellWidth + ($dayCount - 1) * self::borderSize;

        // reservation and close days
        $reservedBeforeDay = $closeDay = [];
        $reservedSpots = $spotDao->getSpotsForInventory($lotId, $from, $to);
        $key = $reservationId = $previousDate = $spotId = 0;
        foreach ($reservedSpots as $reserved) {
            $spotPosition = $spotsOrder[$reserved['id']];
            $dayPosition = $daysOrder[$reserved['date']];
            // reservation
            if ($reserved['reservation_id']) {
                if ($reserved['reservation_id'] != $reservationId) {
                    $key++;
                    $keyReserved = $key;
                    $previousDate = $reserved['date'];
                } else {
                    if ($reserved['date'] == date('Y-m-d', strtotime($previousDate . ' +1 days'))) {
                        if ($spotId != $reserved['id']) {
                            $key++;
                            $keyReserved = $key;
                        } else {
                            $keyReserved = $key;
                        }
                    } else {
                        $key++;
                        $keyReserved = $key;
                    }
                    $previousDate = $reserved['date'];
                }

                $reservedBeforeDay[$keyReserved][] = [
                    'spot'       => $reserved['id'],
                    'date'       => $reserved['date'],
                    'res_number' => $reserved['res_number'],
                    'res_id'     => $reserved['reservation_id']
                ];
                $reservationId = $reserved['reservation_id'];
                $spotId = $reserved['id'];
            // close day
            } else {
                // for web view
                $top = $spotPosition * self::cellHeight + self::cellThHeight + self::borderSize;
                $left = $dayPosition * self::cellWidth + ($dayPosition - 1) * self::borderSize;
                $width = self::cellWidth;
                $height = self::cellHeight - self::borderSize;

                // for print view
                $topPrint = $spotPosition * self::cellHeightPrint + self::cellThHeight + self::borderSize;
                $heightPrint = self::cellHeightPrint - self::borderSize;

                $closeDay[] = [
                    'style' => "width: {$width}px;height: {$height}px;top: {$top}px;left: {$left}px;",
                    'style_print' => "width: {$width}px;height: {$heightPrint}px;top: {$topPrint}px;left: {$left}px;"
                ];
            }
        }

        // correct reserved day
        $reservedDay = [];
        foreach ($reservedBeforeDay as $reservation) {
            $days = count($reservation);
            if ($days) {
                // for web view
                $width = $days * self::cellWidth - self::paddingV * 2 + $days * self::borderSize;
                $height = self::cellHeight - self::borderSize -  self::paddingV * 2;

                $first = current($reservation);
                $spotPosition = $spotsOrder[$first['spot']];
                $dayPosition = $daysOrder[$first['date']];

                $top = self::cellThHeight + $spotPosition * self::cellHeight  - self::borderSize + self::paddingH;
                $left = $dayPosition * self::cellWidth + ($dayPosition - 1) * self::borderSize;

                $margin = self::paddingV;
                $bgColor = self::bgColor;

                // for print view
                $topPrint = self::cellThHeight + $spotPosition * self::cellHeightPrint  - self::borderSize + self::paddingH;
                $heightPrint = self::cellHeightPrint - self::borderSize -  self::paddingV * 2;

                $reservedDay[] = [
                    'style'       => " background:#{$bgColor};width: {$width}px;height: {$height}px;line-height     : {$height}px;margin     : {$margin}px;top: {$top}px;left     : {$left}px;",
                    'style_print' => " background:#{$bgColor};width: {$width}px;height: {$heightPrint}px;line-height: {$heightPrint}px;margin: {$margin}px;top: {$topPrint}px;left: {$left}px;",
                    'res_number'  => $first['res_number'],
                    'res_id'      => $first['res_id']
                ];
            }
        }

        return [
            'spots'       => $spots,
            'from'        => $from,
            'to'          => $to,
            'tableWidth'  => $tableWidth,
            'dayCount'    => $dayCount,
            'reservedDay' => $reservedDay,
            'closeDay'    => $closeDay,
        ];
    }
}
