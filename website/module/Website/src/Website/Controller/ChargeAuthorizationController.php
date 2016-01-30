<?php

namespace Website\Controller;

use DDD\Dao\Booking\Booking as ReservationsDAO;
use DDD\Dao\Booking\Charge as ChargeDAO;
use DDD\Service\Reservation\ChargeAuthorization as ChargeAuthorizationService;
use DDD\Service\Reservation\ChargeAuthorization;
use DDD\Service\Website\Apartment as WebsiteApartmentService;
use Library\Controller\WebsiteBase;
use Symfony\Component\Console\Helper\Helper;
use Website\Form\ChargeAuthorizationForm;
use Zend\View\Model\ViewModel;
use Zend\View\View;

/**
 * Class ChargeAuthorization
 * @package Website\Controller
 */
class ChargeAuthorizationController extends WebsiteBase
{
    /**
     * @return array|\Zend\Http\Response|ViewModel
     */
    public function indexAction()
    {
        /**
         * @var ReservationsDAO $reservationsDao
         */
        $reservationsDao = $this->getServiceLocator()->get('dao_booking_booking');

        $pageToken = $this->params()->fromQuery('token');

        if ($pageToken) {
            $reservationData = $reservationsDao->getReservationDataForChargeAuthorizationPage($pageToken);
            if ($reservationData) {

                $now3d = new \DateTime(null, new \DateTimeZone($reservationData->getTimezone()));
                $now3d = strtotime($now3d->modify('-3 day')->format('Y-m-d'));

                if ($now3d > strtotime($reservationData->getReservationDateTo()) && $now3d > strtotime($reservationData->getCccaCreationDate())) {
                    return $this->redirect()->toRoute('home')->setStatusCode('301');
                }
            } else  {
                return $this->redirect()->toRoute('home')->setStatusCode('301');
            }
        } else {
            return $this->redirect()->toRoute('home')->setStatusCode('301');
        }

        /**
         * @var ChargeAuthorizationService $chargeAuthorizationService
         */
        $chargeAuthorizationService = $this->getServiceLocator()->get('service_reservation_charge_authorization');
        $info = $chargeAuthorizationService->getInfoForCCCAPage($pageToken);
        $amount = $info->getAmount();
        $chargeAuthorizationService->changeChargeAuthorizationPageStatus($reservationData->getReservationId(), $pageToken, ChargeAuthorization::CHARGE_AUTHORIZATION_PAGE_STATUS_VIEWED);

        $cccaForm = new ChargeAuthorizationForm();
        $cccaForm->prepare();

        /**
         * @var ChargeDAO $chargesDao
         */
        $chargesDao = $this->getServiceLocator()->get('dao_booking_charge');
        $chargesSummary = $chargesDao->calculateChargesSummary($reservationData->getReservationId(), \DDD\Service\Booking\Charge::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT);

        $cancellationPolicyData = [
            'is_refundable'           => $reservationData->getIsRefundable(),
            'refundable_before_hours' => $reservationData->getRefundableBeforeHours(),
            'penalty_type'            => $reservationData->getPenaltyType(),
            'penalty_percent'         => $reservationData->getPenaltyValue(),
            'penalty_fixed_amount'    => $reservationData->getPenaltyValue(),
            'penalty_nights'          => $reservationData->getPenaltyValue(),
            'night_count'             => \Library\Utility\Helper::getDaysFromTwoDate($reservationData->getReservationDateFrom(), $reservationData->getReservationDateTo()),
            'code'                    => $reservationData->getCustomerCurrency()
        ];
        /**
         * @var WebsiteApartmentService $websiteApartmentService
         */
        $websiteApartmentService = $this->getServiceLocator()->get('service_website_apartment');
        $cancellationPolicyDescriptionArray = $websiteApartmentService->cancelationPolicy($cancellationPolicyData);
        $cancellationPolicyDescription = $cancellationPolicyDescriptionArray['description'];

        $reservationData->setCancellationPolicy($cancellationPolicyDescription);

        $creditCardData = $chargeAuthorizationService->getCreditCardDataForAuthorizationPage($reservationData->getCreditCardId());
        if (is_null($amount)){
            //for backup compatibility
            $amount = $chargesSummary->getSummaryInApartmentCurrency();
        }

        $this->layout()->userTrackingInfo = [
            'res_number' => $reservationData->getReservationNumber(),
            'partner_id' => $reservationData->getPartnerId(),
        ];

        return new ViewModel([
            'cccaForm'        => $cccaForm,
            'reservationData' => $reservationData,
            'creditCardData'  => $creditCardData,
            'chargesSummary'  => $chargesSummary,
            'amount'          => $amount
        ]);
    }
}