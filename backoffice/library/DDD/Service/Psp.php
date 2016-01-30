<?php

namespace DDD\Service;

class Psp extends ServiceBase
{
    const PSP_SQUARE_ID = 5;
    protected $pspDao;

    /**
     * @param int $start
     * @param int $limit
     * @param int $sortCol
     * @param string $sortDir
     * @param string $search
     * @param string $all
     * @return \Zend\Db\ResultSet\ResultSet|\DDD\Domain\Finance\Psp\ManagePspTableRow[]
     */
    public function pspList($start, $limit, $sortCol, $sortDir, $search, $all)
    {
        $pspDao = $this->getPspDao();

        return $pspDao->getPspList($start, $limit, $sortCol, $sortDir, $search, $all);
    }

    /**
     * @param string $search
     * @param string $all
     * @return int
     */
    public function pspCount($search, $all)
    {
        return $this->getPspDao()->getPspCount($search, $all);
    }

    /**

     * @param int $id
     * @return \ArrayObject
     */
    public function getPspData($id)
    {
        $pspDao = $this->getPspDao();
        $pspData = $pspDao->getPspData($id);

        if ($pspData) {
            return $pspData;
        }

        return [];
    }

    /**
     * @param array $pspData
     * @param int $pspId
     * @return int
     */
    public function savePsp($pspData, $pspId)
    {
		try {
			$pspDao = $this->getPspDao();

            $params = [
                'authorization' => $pspData['authorization'],
                'error_code' => $pspData['error_code'],
                'rrn' => $pspData['rrn'],
                'name' => $pspData['name'],
                'short_name' => $pspData['short_name'],
                'money_account_id' => $pspData['money_account_id']
            ];

            if ($pspId) {
               $pspDao->save($params, ['id' => $pspId]);
            } else {
               $pspId = $pspDao->save($params);
            }
		} catch (\Exception $ex) {
			return false;
		}

		return $pspId;
	}

    /**
     * @param int $pspId
     * @param int $status
     * @return bool
     */
    public function changeStatus($pspId, $status)
    {
        try {
			$pspDao = $this->getPspDao();
            $pspDao->save(['active' => $status], ['id' => $pspId]);
		} catch (\Exception $ex) {
			return false;
		}

		return true;

    }

    /**
     * @return array
     */
    public function getBatchPSPList()
    {
        $pspDao = $this->getPspDao();
        $psps = $pspDao->getBatchPSPs();
        $pspList = [];

        if ($psps->count()) {
            foreach ($psps as $psp) {
                $pspList[$psp->getId()] = $psp->getShortName();
            }
        }

        return $pspList;
    }

    /**
	 * @return \DDD\Dao\Psp\Psp
	 */
	protected function getPspDao()
    {
        if (!$this->pspDao) {
            $this->pspDao = $this->getServiceLocator()->get('dao_psp_psp');
        }

        return $this->pspDao;
    }

    /**
     * @param $pspId
     * @return int
     */
    public function getPspMoneyAccountId($pspId)
    {
        /**
         * @var \DDD\Dao\Psp\Psp $pspDao
         */
        $pspDao = $this->getServiceLocator()->get('dao_psp_psp');

        $moneyAccountId = $pspDao->getMoneyAccountIdByPspId($pspId);

        return $moneyAccountId ? $moneyAccountId : 0;
    }

    /**
     * @param $pspId
     * @return int
     */
    public function getPspInfo($pspId)
    {
        /**
         * @var \DDD\Dao\Psp\Psp $pspDao
         */
        $pspDao = $this->getServiceLocator()->get('dao_psp_psp');

        $pspinfo = $pspDao->getPspInfo($pspId);

        return $pspinfo ? $pspinfo : 0;
    }
}
