<?php

namespace Console\Controller;

use DDD\Dao\Booking\Booking;
use Library\Constants\Constants;
use Library\Constants\EmailAliases;
use Library\Constants\TextConstants;
use Library\Controller\ConsoleBase;
use Zend\Validator\EmailAddress;
use \DDD\Service\Taxes;
use \DDD\Service\Booking\BankTransaction;

/**
 * Class BookingController
 * @package Console\Controller
 */
class BookingController extends ConsoleBase
{
    /**
     * @var bool
     */
    private $id = false;

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode');

        if ($this->getRequest()->getParam('id')) {
            $this->id = $this->getRequest()->getParam('id');
        }

        switch ($action) {
            case 'firstcharge':
                $this->firstchargeAction();
                break;
            case 'clear-links':
                $this->clearLinksAction();
                break;
            case 'check-reservation-balances':
                $this->checkReservationBalancesAction();
                break;
            default :
                echo '- type true parameter ( booking firstcharge | booking clear-links | booking check-reservation-balances)'.PHP_EOL;
                return false;
        }
    }

    public function firstchargeAction()
    {
        try {
        	/* @var $chargeService \DDD\Service\Booking\Charge */
        	$chargeService = $this->getServiceLocator()->get('service_booking_charge');
            $chargeService->cronFirstCharge();
        } catch (\Exception $e) {
            $this->outputMessage($e->getMessage());
        }
    }

	public function clearLinksAction()
    {
		$currentDate = date('Y-m-d');
		$endDate = date('Y-m-d', strtotime($currentDate . "-6 months"));

        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

		$affectedRows = $bookingDao->clearExpiredEditLinks($endDate);

        $this->outputMessage("Affected Rows: {$affectedRows}");
	}



    /**
     * Check reservation balances
     */
    public function checkReservationBalancesAction()
    {
        $datetime = new \DateTime('now');
        $datetime->modify('-1 month');
        $start = $datetime->format('Y-m-d');

        $this->initCommonParams($this->getRequest());
        $this->outputMessage('[light_blue]Checking reservation balances.');
        /** @var \DDD\Service\Booking\BookingTicket $reservationService */
        $reservationService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');
        $sql = "SELECT r.id, r.res_number, r.guest_balance
                FROM ga_reservations r
                WHERE r.date_from >= '$start'
                GROUP BY r.id;";

        $reservationsWithIncorrectBalances = [];
        $reservations = $dbAdapter->createStatement($sql)->execute();
        foreach ($reservations as $reservation) {
            $correctBalance = $reservationService->getSumAndBalanc($reservation['id'])['ginosiBalanceInApartmentCurrency'];

            if ($correctBalance == $reservation['guest_balance']) {
                $this->outputMessage('[cyan]Balance for reservation [light_cyan] ' .
                    $reservation['res_number'] . ' [cyan]is correct.');
            } else {
                $this->outputMessage('[brown]Balance for reservation [yellow] ' .
                    $reservation['res_number'] . ' [brown]is wrong.');
                $this->outputMessage(
                    '[brown]Saved balance is [yellow] ' .
                    $reservation['guest_balance']
                );
                $this->outputMessage(
                    '[brown]True balance is [yellow] ' .
                    $correctBalance
                );
                array_push($reservationsWithIncorrectBalances, $reservation['res_number']);
            }

            // update balance
            //$chargeService->updateBalance($row['id']);
        }

        if (count($reservationsWithIncorrectBalances)) {
            $this->outputMessage(
                '[light_red]' . count($reservationsWithIncorrectBalances) .
                ' [red]reservations have wrong balances:'
            );
            $this->outputMessage(
                '[light_red]' .
                implode('[red], [light_red]', $reservationsWithIncorrectBalances)
            );
        }

        $this->outputMessage('[light_blue]Done.');
    }
}
