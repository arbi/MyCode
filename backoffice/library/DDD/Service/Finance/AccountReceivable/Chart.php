<?php

namespace DDD\Service\Finance\AccountReceivable;

use DDD\Dao\Booking\Charge;
use DDD\Dao\Booking\ChargeTransaction;
use DDD\Service\Booking\BookingAddon;
use DDD\Service\Booking;
use DDD\Service\Currency\Currency;
use DDD\Service\ServiceBase;
use Library\Constants\DbTables;
use Library\Constants\Objects;
use Library\Utility\Debug;
use Zend\Db\Sql\Where;

class Chart extends ServiceBase {
    public function getChargeSummary($data)
    {
        /**
         * @var Currency $currencyService
         */
        $chargeDao = new Charge($this->getServiceLocator());
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');
        $statement = $this->prepareChargeStatement($data);

        $charges = $chargeDao->getChargeSummary($statement, (bool)$data['group']);

        $chargesList = [];
        $baseCurrency = 'EUR';

        if ($charges) {
            // Group by addon type
            foreach ($charges as $charge) {
                if (!isset($chargesList[$charge['addon_type']])) {
                    $chargesList[$charge['addon_type']] = [
                        'amount' => 0,
                        'count' => 0,
                        'currency' => $baseCurrency,
                        'addon' => $charge['addon'],
                    ];
                }

                if ($charge['currency'] != $baseCurrency) {
                    if (isset($chargesList[$charge['addon_type']])) {
                        $convertedRevenue = (
                            (int)$charge['amount']
                                ? $currencyService->convertCurrency($charge['amount'], $charge['currency'], $baseCurrency)
                                : 0
                        );

                        $chargesList[$charge['addon_type']]['amount'] = intval($chargesList[$charge['addon_type']]['amount'] + $convertedRevenue);
                        $chargesList[$charge['addon_type']]['count'] = $chargesList[$charge['addon_type']]['count'] + $charge['count'];
                    }
                } else {
                    $chargesList[$charge['addon_type']]['amount'] = intval($chargesList[$charge['addon_type']]['amount'] + $charge['amount']);
                    $chargesList[$charge['addon_type']]['count'] = $chargesList[$charge['addon_type']]['count'] + $charge['count'];
                }
            }
        }

        return $chargesList;
    }

    public function getTransactionSummary($data)
    {
        /**
         * @var Currency $currencyService
         */
        $transactionDao = new ChargeTransaction($this->getServiceLocator());
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');
        $statement = $this->prepareTransactionStatement($data);
        $transactions = $transactionDao->getTransactionSummary($statement, (bool)$data['group']);

        $transactionList = [];
        $baseCurrency = 'EUR';

        if ($transactions) {
            // Group by addon type
            foreach ($transactions as $transaction) {
                if (!isset($transactionList[$transaction['type']])) {
                    $transactionList[$transaction['type']] = [
                        'amount' => 0,
                        'count' => 0,
                        'currency' => $baseCurrency,
                        'type' => Objects::getTransactionTypeById($transaction['type']),
                    ];
                }

                if ($transaction['currency'] != $baseCurrency) {
                    if (isset($transactionList[$transaction['type']])) {
                        $convertedAmount = (
                            (int)$transaction['amount']
                                ? $currencyService->convertCurrency($transaction['amount'], $transaction['currency'], $baseCurrency)
                                : 0
                        );

                        $transactionList[$transaction['type']]['amount'] = intval($transactionList[$transaction['type']]['amount'] + $convertedAmount);
                        $transactionList[$transaction['type']]['count'] = $transactionList[$transaction['type']]['count'] + $transaction['count'];
                    }
                } else {
                    $transactionList[$transaction['type']]['amount'] = intval($transactionList[$transaction['type']]['amount'] + $transaction['amount']);
                    $transactionList[$transaction['type']]['count'] = $transactionList[$transaction['type']]['count'] + $transaction['count'];
                }
            }
        }

        return $transactionList;
    }

    public function getChargeDownloadable($data)
    {
        /**
         * @var Currency $currencyService
         */
        $chargeDao = new Charge($this->getServiceLocator());
        $statement = $this->prepareChargeStatement($data);

        return $chargeDao->getChargeDownloadable($statement, (bool)$data['group']);
    }

    public function getTransactionDownloadable($data)
    {
        /**
         * @var Currency $currencyService
         */
        $chargeDao = new ChargeTransaction($this->getServiceLocator());
        $statement = $this->prepareTransactionStatement($data);

        return $chargeDao->getTransactionDownloadable($statement, (bool)$data['group']);
    }

    private function prepareChargeStatement($data)
    {
        $statement = new Where();

        // Basic configuration
        $types = [];
        foreach ($data['charge_type'] as $type => $typeValue) {
            if ((int)$typeValue == 1) {
                array_push($types, $type);
            }
        }

        if (count($types)) {
            $statement->in('addons_type', $types);
        }

        $statement->equalTo(DbTables::TBL_CHARGE . '.status', 0);

        // Charge specific
        if (!empty($data['charge_date'])) {
            list($dateFrom, $dateTo) = explode(' - ', $data['charge_date']);

            $statement->between(DbTables::TBL_CHARGE . '.date', $dateFrom, date('Y-m-d', strtotime('+1 day', strtotime($dateTo))));
        }

        // Reservation specific
        if ($data['status'] != 0) {
            if ($data['status'] == 111) {
                $statement->notEqualTo(DbTables::TBL_BOOKINGS . '.status', Booking::BOOKING_STATUS_BOOKED);
            } else {
                $statement->equalTo(DbTables::TBL_BOOKINGS . '.status', $data['status']);
            }
        }

        if ($data['payment_model'] != 0) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.model', $data['payment_model']);
        }

        if ($data['partner_id'] != 0) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.partner_id', $data['partner_id']);
        }

        if ($data['no_collection'] != 1) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.no_collection', ($data['no_collection'] == 2 ? 1 : 0));
        }

        if (!empty($data['booking_date'])) {
            list($dateFrom, $dateTo) = explode(' - ', $data['booking_date']);

            $statement->between(DbTables::TBL_BOOKINGS . '.timestamp', $dateFrom, date('Y-m-d', strtotime('+1 day', strtotime($dateTo))));
        }

        if (!empty($data['arrival_date'])) {
            list($dateFrom, $dateTo) = explode(' - ', $data['arrival_date']);

            $statement->between(DbTables::TBL_BOOKINGS . '.date_from', $dateFrom, $dateTo);
        }

        if (!empty($data['departure_date'])) {
            list($dateFrom, $dateTo) = explode(' - ', $data['departure_date']);

            $statement->between(DbTables::TBL_BOOKINGS . '.date_to', $dateFrom, $dateTo);
        }

        if (!empty($data['product_id'])) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.apartment_id_origin', $data['product_id']);
        }

        if (!empty($data['assigned_product_id'])) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.apartment_id_assigned', $data['assigned_product_id']);
        }

        if (!empty($data['city'])) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.acc_city_id', $data['city']);
        }

        // Apartment Group specific
        if ((int)$data['group']) {
            $statement->equalTo(DbTables::TBL_APARTMENT_GROUP_ITEMS . '.apartment_group_id', $data['group']);
        }

        return $statement;
    }

    private function prepareTransactionStatement($data)
    {
        $statement = new Where();

        // Basic configuration
        $types = [];
        foreach ($data['transaction_type'] as $type => $typeValue) {
            if ((int)$typeValue == 1) {
                array_push($types, $type);
            }
        }

        if (count($types)) {
            $statement->in('type', $types);
        }

        // Transaction specific
        if (!empty($data['transaction_date'])) {
            list($dateFrom, $dateTo) = explode(' - ', $data['transaction_date']);

            $statement->between(DbTables::TBL_CHARGE_TRANSACTION . '.date', $dateFrom, date('Y-m-d', strtotime('+1 day', strtotime($dateTo))));
        }

        // Reservation specific
        if ($data['status']) {
            if ($data['status'] == 111) { // Canceled
                $statement->notEqualTo(DbTables::TBL_BOOKINGS . '.status', Booking::BOOKING_STATUS_BOOKED);
            } else {
                $statement->equalTo(DbTables::TBL_BOOKINGS . '.status', $data['status']);
            }
        }

        if ($data['payment_model']) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.model', $data['payment_model']);
        }

        if ($data['partner_id']) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.partner_id', $data['partner_id']);
        }

        if ($data['no_collection'] != -1) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.no_collection', $data['no_collection']);
        }

        if (!empty($data['booking_date'])) {
            list($dateFrom, $dateTo) = explode(' - ', $data['booking_date']);

            $statement->between(DbTables::TBL_BOOKINGS . '.timestamp', $dateFrom, date('Y-m-d', strtotime('+1 day', strtotime($dateTo))));
        }

        if (!empty($data['arrival_date'])) {
            list($dateFrom, $dateTo) = explode(' - ', $data['arrival_date']);

            $statement->between(DbTables::TBL_BOOKINGS . '.date_from', $dateFrom, $dateTo);
        }

        if (!empty($data['departure_date'])) {
            list($dateFrom, $dateTo) = explode(' - ', $data['departure_date']);

            $statement->between(DbTables::TBL_BOOKINGS . '.date_to', $dateFrom, $dateTo);
        }

        if (!empty($data['product_id'])) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.apartment_id_origin', $data['product_id']);
        }

        if (!empty($data['assigned_product_id'])) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.apartment_id_assigned', $data['assigned_product_id']);
        }

        if ($data['city']) {
            $statement->equalTo(DbTables::TBL_BOOKINGS . '.acc_city_id', $data['city']);
        }

        if ($data['psp']) {
            $statement->equalTo(DbTables::TBL_CHARGE_TRANSACTION . '.psp_id', $data['psp']);
        }

        // Apartment Group specific
        if ($data['group']) {
            $statement->equalTo(DbTables::TBL_APARTMENT_GROUP_ITEMS . '.apartment_group_id', $data['group']);
        }

        // Transaction Status
        if ($data['transaction_status']) {
            $statement->equalTo(DbTables::TBL_CHARGE_TRANSACTION . '.status', $data['transaction_status']);
        }

        return $statement;
    }
}
