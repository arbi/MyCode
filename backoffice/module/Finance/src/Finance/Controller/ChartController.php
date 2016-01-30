<?php

namespace Finance\Controller;

use DDD\Dao\Geolocation\City;
use DDD\Dao\Psp\Psp;
use DDD\Service\Booking\BookingAddon;
use DDD\Service\Booking\BookingManagement;
use Finance\Form\Charge;
use DDD\Service\Finance\AccountReceivable\Chart as ChartService;
use Finance\Form\Transaction;
use Library\Constants\Objects;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Debug;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class ChartController extends ControllerBase
{
    public function chargeAction()
    {
        /**
         * @var BookingManagement $bookingManagementService
         * @var BookingAddon $addonsService
         * @var City $cityDao
         */
        $bookingManagementService = $this->getServiceLocator()->get('service_booking_management');
        $addonsService = $this->getServiceLocator()->get('service_booking_booking_addon');
        $cityDao = $this->getServiceLocator()->get('dao_geolocation_city');
        $formResources = $bookingManagementService->prepareSearchFormResources();
        $addons = $addonsService->getAddonsInArray();
        $cities = $cityDao->getSearchableCities();

        $form = new Charge($formResources, $addons, $cities);

        return new ViewModel([
            'form' => $form,
            'addons' => $addons,
        ]);
    }

    public function getChargeAction()
    {
        /**
         * @var ChartService $chartService
         * @var BookingManagement $bookingManagementService
         */
        $request = $this->getRequest();
        $chartService = $this->getServiceLocator()->get('service_finance_account_receivable_chart');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $data = $request->getPost();

            if (!is_null($data)) {
                $result = [
                    'status' => 'success',
                    'message' => TextConstants::SUCCESS_FOUND,
                    'data' => $chartService->getChargeSummary($data),
                ];
            } else {
                $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function downloadChargeAction()
    {
        /**
         * @var ChartService $chartService
         * @var BookingManagement $bookingManagementService
         */
        $request = $this->getRequest();
        $chartService = $this->getServiceLocator()->get('service_finance_account_receivable_chart');
        $charges = $chartService->getChargeDownloadable($request->getQuery());

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Charges.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, ['Res. Number', 'Type', 'Acc Amount', 'Acc Currency', 'Charge Date', 'Commission (%)']);

        foreach ($charges as $charge) {
            fputcsv($output, [
                $charge['res_number'],
                $charge['addon'],
                $charge['acc_amount'],
                $charge['acc_currency'],
                $charge['date'],
                $charge['commission'],
            ]);
        }

        exit;
    }

    public function transactionAction()
    {
        /**
         * @var ChartService $chartService
         * @var BookingManagement $bookingManagementService
         * @var Psp $pspDao
         */
        $bookingManagementService = $this->getServiceLocator()->get('service_booking_management');
        $cityDao = $this->getServiceLocator()->get('dao_geolocation_city');
        $pspDao = $this->getServiceLocator()->get('dao_psp_psp');
        $formResources = $bookingManagementService->prepareSearchFormResources();
        $cities = $cityDao->getSearchableCities();
        $psps = $pspDao->getPsps();
        $form = new Transaction($formResources, $cities, $psps);

        return new ViewModel([
            'form' => $form,
        ]);
    }

    public function getTransactionAction()
    {
        /**
         * @var ChartService $chartService
         * @var BookingManagement $bookingManagementService
         */
        $request = $this->getRequest();
        $chartService = $this->getServiceLocator()->get('service_finance_account_receivable_chart');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $data = $request->getPost();

            if (!is_null($data)) {
                $result = [
                    'status' => 'success',
                    'message' => TextConstants::SUCCESS_FOUND,
                    'data' => $chartService->getTransactionSummary($data),
                ];
            } else {
                $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function downloadTransactionAction()
    {
        /**
         * @var ChartService $chartService
         * @var BookingManagement $bookingManagementService
         */
        $request = $this->getRequest();
        $chartService = $this->getServiceLocator()->get('service_finance_account_receivable_chart');
        $transactions = $chartService->getTransactionDownloadable($request->getQuery());

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Transactions.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, ['Res. Number', 'Type', 'Acc Amount', 'Acc Currency', 'Transaction Date', 'PSP']);

        foreach ($transactions as $transaction) {
            fputcsv($output, [
                $transaction['res_number'],
                Objects::getTransactionTypeById($transaction['type']),
                $transaction['acc_amount'],
                $transaction['currency'],
                $transaction['date'],
                $transaction['psp_name'],
            ]);
        }

        exit;
    }
}
