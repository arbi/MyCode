<?php

namespace DDD\Service\Finance;

use DDD\Service\ServiceBase;
use Library\ActionLogger\Logger;
use Library\Constants\Constants;
use Library\Utility\Helper;

class Espm extends ServiceBase
{

    const TYPE_SALARY = 1;
    const TYPE_BONUS = 2;
    const TYPE_COMPENSATION = 3;
    const TYPE_LOAN = 4;
    const TYPE_PAYMENT = 5;
    const TYPE_SALE = 6;
    const TYPE_ADJUSTMENT = 7;

    public static $espmTypes = [
        self::TYPE_SALARY => 'Salary',
        self::TYPE_BONUS => 'Bonus',
        self::TYPE_COMPENSATION => 'Compensation',
        self::TYPE_LOAN=> 'Loan',
        self::TYPE_PAYMENT => 'Payment',
        self::TYPE_SALE => 'Sale',
        self::TYPE_ADJUSTMENT => 'Adjustment',
    ];

    const STATUS_PENDING = 1;
    const STATUS_HOLD = 2;
    const STATUS_CANCELLED = 3;
    const STATUS_DONE = 4;

    public static $espmStatuses = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_HOLD => 'Hold',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_DONE => 'Done'
    ];

    const IS_ARCHIVED = 1;
    const UNARCHIVED = 0;

    /**
     * @param array $params
     * @return array
     */
    public function getDatatableData(array $params)
    {
        /**
         * @var \DDD\Dao\Finance\Espm\Espm $espmDao
         */
        $espmDao    = $this->getServiceLocator()->get('dao_finance_espm');
        $result     = $espmDao->getAllEspms($params);
        $data       = [];
        $espmList   = $result['result'];
        $total      = $result['total'];

        if ($espmList->count()) {
            foreach ($espmList as $espm) {
                array_push($data, [
                    Espm::$espmStatuses[$espm['status']],
                    Espm::$espmTypes[$espm['type']],
                    $espm['supplier_name'],
                    $espm['external_account_name'],
                    $espm['amount'] . ' ' . $espm['currency_code'],
                    $espm['creator'],
                    '<a class="btn btn-xs btn-primary" href="/finance/espm/edit/' . $espm['id'] . '" data-html-content="Edit"></a>'
                ]);
            }
        }

        return [
            'iTotalRecords'        => $total,
            'iTotalDisplayRecords' => $total,
            'iDisplayStart'        => $params['iDisplayStart'],
            'iDisplayLength'       => $params['iDisplayLength'],
            'aaData'               => $data,
        ];
    }

    /**
     * @param $espmId
     * @return array
     */
    public function getOptions()
    {
        /**
         * @var \DDD\Service\Currency\Currency $currencyService
         * @var \DDD\Dao\Finance\Espm\Espm $espmDao
         */

        $currencyService = $this->getServiceLocator()->get('service_currency_currency');
        $espmDao = $this->getServiceLocator()->get('dao_finance_espm');

        // get currency list
        $currencyList = $currencyService->getSimpleCurrencyList();

        // statuses
        $statuses = self::$espmStatuses;

        // types
        $types = self::$espmTypes;

        return [
            'currencyList' => $currencyList,
            'statuses' => $statuses,
            'types' => $types
        ];
    }

    /**
     * @param $espmId
     * @return array|\ArrayObject|null
     */
    public function getEspmData($espmId)
    {
        /**
         * @var \DDD\Dao\Finance\Espm\Espm $espmDao
         */
        $espmDao    = $this->getServiceLocator()->get('dao_finance_espm');

        // get espm data
        $espmData = $espmDao->getEspmData($espmId);
        if ($espmData) {
            $espmData['created_date'] = date(Constants::GLOBAL_DATE_FORMAT, strtotime($espmData['created_date']));
            $espmData['currency'] = $espmData['currency_id'];
            $espmData['action_date'] = $espmData['date'] ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($espmData['date'])) : '';
        }

        return $espmData ? $espmData : [];
    }

    /**
     * @param $data
     * @param $espmId
     * @return int
     */
    public function saveEspm($data, $espmId)
    {
        /**
         * @var \DDD\Dao\Finance\Espm\Espm $espmDao
         * @var Logger $logger
         */
        $espmDao = $this->getServiceLocator()->get('dao_finance_espm');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $params = [];

        // set amount
        if (isset($data['amount']) && $data['amount']) {
            $params['amount'] = $data['amount'];
        }

        // set currency
        if (isset($data['currency']) && $data['currency']) {
            $params['currency_id'] = $data['currency'];
        }

        // set supplier
        if (isset($data['transaction_account']) && $data['transaction_account']) {
            $params['transaction_account_id'] = $data['transaction_account'];
        }

        // set account
        if (isset($data['account']) && $data['account']) {
            $params['external_account_id'] = $data['account'];
        }

        // set status
        if (isset($data['status']) && $data['status']) {
            $params['status'] = $data['status'];
        }

        // set type
        if (isset($data['type']) && $data['type']) {
            $params['type'] = $data['type'];
        }

        // set reason
        if (isset($data['reason']) && $data['reason']) {
            $params['reason'] = $data['reason'];
        }

        // set reason
        if (isset($data['action_date']) && $data['action_date']) {
            $params['date'] = date('Y-m-d', strtotime($data['action_date']));
        }

        try {
            if ($espmId) {
                // check change status
                if (isset($data['status']) && $data['status']) {
                    $status = $espmDao->getEspmStatus($espmId);
                    if ($status['status'] != $data['status']) {
                        $logger->save(
                            Logger::MODULE_ESPM,
                            $espmId,
                            Logger::ACTION_CHANGE_STATUS,
                            'Change status from <b>'. self::$espmStatuses[$status['status']] .'</b> to <b>'. self::$espmStatuses[$data['status']] .'</b>'
                        );
                    }
                }

                // save params
                $espmDao->save($params, ['id' => $espmId]);
            } else {
                $auth = $this->getServiceLocator()->get('library_backoffice_auth');
                $userId = $auth->getIdentity()->id;

                $params['creator_id'] = $userId;
                $params['status'] = self::STATUS_PENDING;
                $espmId = $espmDao->save($params);
            }
        } catch (\Exception $e) {
            return false;
        }

		return $espmId;
	}

    /**
     * @param $espmId
     * @param $archive
     * @return int
     */
    public function archive($espmId, $archive)
    {
        /**
         * @var \DDD\Dao\Finance\Espm\Espm $espmDao
         */
        $espmDao   = $this->getServiceLocator()->get('dao_finance_espm');

        return $espmDao->save(['is_archived' => $archive], ['id' => $espmId]);
    }
}
