<?php

namespace DDD\Service\Apartment;

use DDD\Service\ServiceBase;
use DDD\Dao\Accommodation\Accommodations;
use DDD\Dao\Finance\Expense\Statistics as ExpenseStatistics;
use DDD\Dao\Booking\Booking;
use DDD\Dao\Apartment\Inventory;
use Library\Utility\Helper;
use DDD\Service\Booking\BankTransaction;
use DDD\Service\Booking as ForBookingStatus;

class Statistics extends ServiceBase
{
	const STATISTICS_TYPE_ASC  = 'ASC';
	const STATISTICS_TYPE_DESC = 'DESC';

	public function getBudgedData($apartmentId)
    {
        $accdao = new Accommodations(
        	$this->getServiceLocator(),
        	'DDD\Domain\Apartment\Statistics\ForBudged'
        );

		$accommodation = $accdao->getAccDetail($apartmentId);
        $symbol = $accommodation->getSymbol();

        $expensedao = new ExpenseStatistics($this->getServiceLocator());

        $indicator  = new \stdClass();
        $startup    = $expensedao->getTotalBudgetSummOfStartup($apartmentId);
        $startupSum = round($startup['sum'], 2);
        $running    = $expensedao->getTotalBudgetSummOfRunning($apartmentId);
        $runningSum = round($running['sum'], 2);

        $running_budget = $accommodation->getMonthly_cost() * 12;
        $startupBudget  = $accommodation->getStartup_cost();

        $indicator->running = new \stdClass();
		$indicator->running->data_provided = floor(date('z') / 365 * 100);
		$indicator->running->data_spend = $running_budget ? floor($runningSum / $running_budget * 100) : 0;
		$indicator->running->data_spend_cost = is_null($runningSum) ? 0 : $runningSum;
		$indicator->running->data_cost_overall = $running_budget;
		$indicator->running->data_cost_starting = 0;
		$indicator->running->data_currency_sign = $symbol;

        $indicator->startup = new \stdClass();
		$indicator->startup->data_spend = $startupBudget ? floor($startupSum / $startupBudget * 100) : 0;
		$indicator->startup->data_spend_cost = is_null($startupSum) ? 0 : $startupSum;
		$indicator->startup->data_cost_overall = $startupBudget;
		$indicator->startup->data_cost_starting = 0;
		$indicator->startup->data_currency_sign = $symbol;
        return $indicator;
    }

    public function getBasicData($apartmentId)
    {
        $apartmentDao = new Accommodations(
        	$this->getServiceLocator(),
        	'DDD\Domain\Apartment\Statistics\ForBudged'
        );

		$accommodation = $apartmentDao->getAccDetail($apartmentId);
        $currencyCode  = $accommodation->getCode();
        //Get statistic this year

		$initVars 	= $this->_initVariables(self::STATISTICS_TYPE_ASC);
		$startDate  = date('Y-m-01');
		$endDate 	= date('Y-m-t',strtotime('+1 year',strtotime($startDate)));
		$sale 		= $initVars['sale'];
		$months 	= $initVars['months'];

		$sale = $this->_getSaleArray($apartmentId, $sale, $startDate, $endDate, true);

        //Get statistic for previous year

		$initVarsPre    = $this->_initVariables(self::STATISTICS_TYPE_DESC);
		$salePrevious   = $initVarsPre['sale'];
		$monthsPrevious = $initVarsPre['months'];

		$endDate = date('Y-m-t');

		$startDate = date(
			'Y-m-01',
			strtotime('-1 year',strtotime($endDate))
		);

        $salePrevious = $this->_getSaleArray(
        	$apartmentId,
        	$salePrevious,
        	$startDate,
        	$endDate
        );

        return [
            'sale' 			  => $sale,
            'sale_previous'	  => $salePrevious,
            'months'		  => $months,
            'months_previous' => $monthsPrevious,
            'currencyCode'	  => $currencyCode
        ];
    }

    /**
     * Calculate monthly profit for selected Apartment
     *
     * @param int $apartmentId
     * @return float
     */
    public function getMonthlyProfit($apartmentId)
    {
        try {
            $initPre = $months_previous = $this->_initVariables(
                self::STATISTICS_TYPE_DESC
            );

            $salePre   = $initPre['sale'];
            $months    = $initPre['months'];
            $endDate   = date('Y-m-t');
            $startDate = date(
                'Y-m-01',
                strtotime('-1 year', strtotime($endDate))
            );

            $salePre = $this->_getSaleArray(
                $apartmentId,
                $salePre,
                $startDate,
                $endDate
            );

            return $salePre['profit'];
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param  int $apartment_id
     * @param  float $sale
     * @param  date $startDate
     * @param  date $endDate
     * @var    ginosiColl  ginosiCollectTransactionsSummaryInApartmentCurrency
     * @var    partnerColl  partnerCollectTransactionsSummaryInApartmentCurrency
     * @return Array
     */
    private function _getSaleArray($apartment_id, $sale, $startDate, $endDate, $passYear = false)
    {
		$sale['all_bookings'] = $sale['all_cancelations'] = $sale['all_cancelations'] = $sale['highest_sold_price'] =
        $sale['long_stay'] = $sale['stay_period']['long'] = 0 ;
		$sale['stay_period'] = [];

        $notUsedStatus = [
	        ForBookingStatus::BOOKING_STATUS_CANCELLED_INVALID,
	        ForBookingStatus::BOOKING_STATUS_CANCELLED_EXCEPTION,
	        ForBookingStatus::BOOKING_STATUS_CANCELLED_TEST_BOOKING,
	        ForBookingStatus::BOOKING_STATUS_CANCELLED_MOVED
        ];

        /**
         * @var $bankTransactionService \DDD\Service\Booking\BankTransaction
         * @var \DDD\Dao\Booking\ReservationNightly $reservationNightlyDao
         */

        $bookingDao = new Booking($this->getServiceLocator(), 'DDD\Domain\Apartment\Statistics\ForBasicDataBooking');
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $reservationNightlyDao = $this->getServiceLocator()->get('dao_booking_reservation_nightly');
        $inventoryDao = new Inventory($this->getServiceLocator(), '\ArrayObject');

        $bankTransactionService = $this->getServiceLocator()->get('service_booking_bank_transaction');

        $expenseDao = new ExpenseStatistics($this->getServiceLocator());

		$bookings = $bookingDao->getBookingsForYear(
			$apartment_id,
			$startDate,
			$endDate,
            $notUsedStatus
		);

		$monthlyConst = $expenseDao->getMonthlyCost(
			$apartment_id,
			$startDate,
			$endDate
		);

        // calculate with nightly data
		$fistDate = $startDate;
		$i = 0;
		while ($i <= 12) {
			$nextDate = date('Y-m-01',strtotime('+1 month',strtotime($fistDate)));
            $monthDays = cal_days_in_month(CAL_GREGORIAN, date('m',strtotime( $fistDate)), date('Y', strtotime($fistDate)));
            // reservation day count
            $monthlyData = $reservationNightlyDao->getBookedMonthlyData($apartment_id, $fistDate, $nextDate);
            $bookedDayCount = $monthlyData['count'];
            $monthlySum = $monthlyData['sum'];
            // closed days
			$totalClosed = $inventoryDao->getClosedAv($apartment_id, $fistDate, $nextDate);
			$selectedMonth = date("M_Y",strtotime($fistDate));
			$sale['close_out'][$selectedMonth] = $totalClosed - $bookedDayCount;
            $sale['unsold_days'][$selectedMonth] = $monthDays - $bookedDayCount;
            $sale['monthly_av_price'][$selectedMonth] = number_format(($bookedDayCount ? $monthlySum / $bookedDayCount : 0), 2, '.', '');

            // get highest and lowest sold prices
            if ($passYear) {
                // get highest price
                if ($sale['highest_sold_price'] < $monthlyData['max']) {
                    $sale['highest_sold_price'] = $monthlyData['max'];
                }

                // get lowest price
                if (!array_key_exists('lowest_sold_price', $sale) && $monthlyData['min']) {
                    $sale['lowest_sold_price'] = $monthlyData['min'];
                } elseif(isset($sale['lowest_sold_price']) && $sale['lowest_sold_price'] > $monthlyData['min'] && $monthlyData['min']) {
                    $sale['lowest_sold_price'] = $monthlyData['min'];
                }
            }

			$fistDate = $nextDate;
			$i++;
		}

		// Monthly Booking And Cancelations And Margin
		foreach ($bookings as $book) {
            $month = date("M_Y",strtotime($book->getDate_to()));

            /* @var $ginosiColl $ginosiCollectTransactionsSummaryDomain \DDD\Domain\Booking\TransactionSummary */
            $ginosiColl = $bankTransactionService
                ->getTransactionsSummary(
                    $book->getId(),
                    BankTransaction::TRANSACTION_MONEY_DIRECTION_GINOSI_COLLECT,
                    [BankTransaction::BANK_TRANSACTION_TYPE_PAY]
            );

            $ginosiCollCurrency = $ginosiColl->getSummaryInApartmentCurrency();

            /* @var $partnerCollectTransactionsSummaryDomain \DDD\Domain\Booking\TransactionSummary */
            $partnerColl = $bankTransactionService
                ->getTransactionsSummary(
                    $book->getId(),
                    BankTransaction::TRANSACTION_MONEY_DIRECTION_PARTNER_COLLECT,
                    [BankTransaction::BANK_TRANSACTION_TYPE_PAY]
            );

            $partnerCollCurrency = $partnerColl->getSummaryInApartmentCurrency();

            $transactionsSummary = $ginosiCollCurrency + $partnerCollCurrency;

            if (ForBookingStatus::BOOKING_STATUS_BOOKED == $book->getStatus()) {
                $sale['all_bookings'] += 1;
                $sale['monthly_bookings'][$month] += 1;

                $ginosiksRes = $userManagerDao->getGinosiksReservation(
                    $book->getGuestEmail()
                );

                if ($ginosiksRes) {
                    $sale['free_sold'][$month] += 1;
                }

                $date_diff = Helper::getDaysFromTwoDate($book->getDate_to(), $book->getDate_from() );
                if ($sale['long_stay'] < $date_diff) {
                    $sale['long_stay'] = $date_diff;
                }

            } elseif (ForBookingStatus::BOOKING_STATUS_CANCELLED_BY_CUSTOMER == $book->getStatus()) {
                $sale['all_cancelations'] += 1;
                $month = date("M_Y",strtotime($book->getDate_to()));
                $sale['monthly_cancalations'][$month] += 1;
            }

            $sale = $this->_setMonthlyCost($book, $month, $sale, $transactionsSummary);
		}

		//Calculate final monthly cost
		foreach ($monthlyConst as $month=>$cost) {
            if (isset($sale['monthly_cost'][$month])) {
                $sale['monthly_cost'][$month] += $cost;
                $sale['monthly_cost_total'] += $cost;
                $sale['profit'][$month] = $sale['monthly_revenue'][$month] -$sale['monthly_cost'][$month];
            }
		}

		// Cancellation score
		$total_reservations = ($sale['all_cancelations']+$sale['all_bookings'])*100;
		if (0 != $total_reservations) {
			$sale['cancelation_score'] =(
				  $sale['all_cancelations']
				/ ($sale['all_cancelations'] + $sale['all_bookings'])
				) *100;

			$sale['cancelation_score'] = number_format($sale['cancelation_score'],1);
		}else{
			$sale['cancelation_score'] = 0;
		}

		// Monthly Occupancy
        $bookingReservationData = $bookingDao->getReservationForAccOnDate(
        	$apartment_id,
        	$startDate,
        	$endDate
        );

        $reservationDates = [];
        foreach ($bookingReservationData as $reservation){
            $reservationDates = array_merge(
            	$reservationDates,
            	Helper::getDateListInRange(
            		$reservation->getDate_from(),
	            	date('Y-m-d', strtotime('-1 day', strtotime($reservation->getDate_to())))
	            )
	        );
        }

		//Get Extremes
        $inventoryDao = new Inventory(
        	$this->getServiceLocator(),
        	'DDD\Domain\Apartment\Statistics\ForBasicDataInventory'
        );

		$extremums = $inventoryDao->getExtremums(
			$apartment_id,
			$startDate,
			$endDate
		);

		$sale['max_avilability'] = $extremums->getMax_availability();
		$sale['max_price'] = $extremums->getMax_price();
		$sale['min_price'] = $extremums->getMin_price();

		//Get Review
        $apartmentReview = $this
        	->getServiceLocator()
        	->get('service_apartment_review');
		$apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');
		$reviewScore    = $apartmentGeneralDao->getReviewScore($apartment_id)['score'];
		$sale['review'] =  $reviewScore;
        return $sale;
		//Set All Times Statistics
    }

    private function _initVariables($type)
    {
		$months = [];

		if ($type == self::STATISTICS_TYPE_ASC) {
			$currentDate= date('Y-m-d');
		} elseif ($type == self::STATISTICS_TYPE_DESC) {
			$currentDate = date(
				'Y-m-d',
				strtotime('-1 year',strtotime(date('Y-m-d')))
			);
		}
		$months[] = date("M Y",strtotime($currentDate));
		$month_key = date("M_Y",strtotime($currentDate));
		$sale['monthly_bookings'][$month_key] = 0;
		$sale['monthly_cancalations'][$month_key] = 0;
		$sale['unsold_days'][$month_key] = 0;
		$sale['monthly_av_price'][$month_key] = 0;
		$sale['monthly_revenue'][$month_key] = 0;
		$sale['monthly_revenue_total'] = 0;
		$sale['monthly_cost'][$month_key] = 0;
		$sale['free_sold'][$month_key] = 0;
		$sale['close_out'][$month_key] = 0;
		$sale['monthly_cost_total'] = 0;
		$sale['profit'][$month_key] = 0;
		$sale['period'] = "From ".date("F Y",strtotime($currentDate));
		$dateOneMonthAdded = date(
			'Y-m-d',
			strtotime(date("Y-m-d", strtotime($currentDate)) . " +1 month")
		);

		for($i=0; $i<12; $i++) {
			$year_f = date("Y", strtotime($currentDate));
			$month_f = date('m', strtotime($currentDate));
			$dateOneMonthAdded = date('Y-m-d',strtotime($year_f."-".$month_f."-01 +1 months"));
			$currentDate = $dateOneMonthAdded;
			$months[] = date("M Y",strtotime($currentDate));
			$month_key = date("M_Y",strtotime($currentDate));
			$sale['monthly_bookings'][$month_key] = 0;
			$sale['monthly_cancalations'][$month_key] = 0;
			$sale['unsold_days'][$month_key] = 0;
			$sale['monthly_av_price'][$month_key] = 0;
			$sale['monthly_revenue'][$month_key] = 0;
			$sale['free_sold'][$month_key] = 0;
			$sale['monthly_cost'][$month_key] = 0;
			$sale['profit'][$month_key] = 0;
			$sale['close_out'][$month_key] = 0;

			if ($i == 11) {
				$sale['period'] .=" To ".date("F Y",strtotime($currentDate));
			}
		}
		$return = [
			'sale'   => $sale,
			'months' => $months
		];

		return $return;
	}

    private function _setMonthlyCost($value, $month, $sale, $price)
    {
		$fixed_profit = ($price * $value->getTransaction_fee_percent())/100;
		$cost = $fixed_profit;
		$sale['monthly_cost'][$month]    += $cost;
		$sale['monthly_cost_total']      += $cost;
		$sale['monthly_revenue'][$month] += $price;
		$sale['monthly_revenue_total']   += $price;
		$sale['profit'][$month] =
			  $sale['monthly_revenue'][$month]
			- $sale['monthly_cost'][$month];

		return $sale;
	}
}
