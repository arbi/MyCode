<?php

namespace DDD\Service\Apartel;

use DDD\Service\ServiceBase;
use Library\Utility\Debug;
use Zend\Form\Annotation\Object;
use Library\Constants\Objects;

class OTADistribution extends ServiceBase
{
    const STATUS_PENDING = 1;
    const STATUS_SELLING = 2;

    /**
     * @param int[]|array $apartelIdList
     * @param int[]|array $acceptableOTAList
     * @return \ArrayObject
     */
    public function getOTAListFromArray($apartelIdList, $acceptableOTAList)
    {
        /**
         * @var \DDD\Dao\Apartel\OTADistribution $apartelOTADao
         */
        $apartelOTADao = $this->getOTADistributionDao();
        $OTAList = $apartelOTADao->getApartelOTAListFromArray($apartelIdList, $acceptableOTAList);

        return $OTAList;
    }

    public function changeOTASellingStatus($distributionId, $status) {
        /**
         * @var \DDD\Dao\Apartel\OTADistribution $apartelOTADao
         */
        $apartelOTADao = $this->getOTADistributionDao();
        $channelList = $apartelOTADao->save([
            'status' => $status,
            'date_edited' => date('Y-m-d H:i:s'),
        ], ['id' => $distributionId]);

        return $channelList;
    }

    public function changeOTACrawlerStatus($distributionId, $otaStatus) {
        /**
         * @var \DDD\Dao\Apartel\OTADistribution $apartelOTADao
         */
        $apartelOTADao = $this->getOTADistributionDao();
        $channelList = $apartelOTADao->save([
            'ota_status' => $otaStatus,
            'date_edited' => date('Y-m-d H:i:s'),
        ], ['id' => $distributionId]);

        return $channelList;
    }

    /**
     * @param int $apartelId
     * @return \ArrayObject
     */
    public function getOTAList($apartelId)
    {
        /**
         * @var \DDD\Dao\Apartel\OTADistribution $apartelOTADao
         */
        $apartelOTADao = $this->getOTADistributionDao();
        $OTAList = $apartelOTADao->getApartelOTAList($apartelId);

        return $OTAList;
    }

    /**
     * @return \ArrayObject
     */
    public function getPartnerList()
    {
        $partnerDao = $this->getServiceLocator()->get('dao_partners_partners');
        $partnerList = $partnerDao->getActiveOutsidePartners();

        return $partnerList;
    }

    /**
     * @param $data
     * @param $apartelId
     * @return bool|int]
     */
    public function saveOTA($data, $apartelId)
    {
        /**
         * @var \DDD\Dao\Apartel\OTADistribution $apartelOTADao
         */
        if (empty($data)) {
            return false;
        }

        $apartelOTADao = $this->getOTADistributionDao();
        $params = [
            'apartel_id' => (int)$apartelId,
            'partner_id' => $data['ota_name'],
            'reference' => $data['ota_ref'],
            'url' => $data['ota_url'],
            'status' => 1,
        ];

        $insertId = $apartelOTADao->save($params);

        return $insertId;
    }

    /**
     * @param int $otaId
     * @return boolean
     */
    public function removeOTA($otaId)
    {
        try {
            if ($otaId > 0) {
                $apartelOTADao = $this->getOTADistributionDao();
                $apartelOTADao->delete(['id' => $otaId]);

                return true;
            }

            return false;
        } catch (\Exception $ex) {
			return false;
		}
    }

	private function getOTADistributionDao()
    {
		return $this->getServiceLocator()->get('dao_apartel_ota_distribution');
	}
}
