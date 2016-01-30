<?php

namespace DDD\Service\Apartment;

use DDD\Service\ServiceBase;
use Library\Utility\Debug;
use Zend\Form\Annotation\Object;
use Library\Constants\Objects;

class OTADistribution extends ServiceBase
{
    /**
     * @param int[]|array $apartmentIdList
     * @param int[]|array $acceptableStatusList
     * @param int[]|array $acceptableOTAList
     * @return \ArrayObject
     */
    public function getOTAListFromArray($apartmentIdList, $acceptableStatusList, $acceptableOTAList)
    {
        /**
         * @var \DDD\Dao\Apartment\OTADistribution $apartmentOTADao
         */
        $apartmentOTADao = $this->getOTADistributionDao();
        $channelList = $apartmentOTADao->getApartmentOTAListFromArray($apartmentIdList, $acceptableStatusList, $acceptableOTAList);

        return $channelList;
    }

    public function changeOTASellingStatus($distributionId, $status) {
        /**
         * @var \DDD\Dao\Apartment\OTADistribution $apartmentOTADao
         */
        $apartmentOTADao = $this->getOTADistributionDao();
        $channelList = $apartmentOTADao->save([
            'status' => $status,
            'date_edited' => date('Y-m-d H:i:s'),
        ], ['id' => $distributionId]);

        return $channelList;
    }

    public function changeOTACrawlerStatus($distributionId, $otaStatus) {
        /**
         * @var \DDD\Dao\Apartment\OTADistribution $apartmentOTADao
         */
        $apartmentOTADao = $this->getOTADistributionDao();
        $channelList = $apartmentOTADao->save([
            'ota_status' => $otaStatus,
            'date_edited' => date('Y-m-d H:i:s'),
        ], ['id' => $distributionId]);

        return $channelList;
    }

    /**
     * @param int $apartmentId
     * @return \ArrayObject
     */
    public function getOTAList($apartmentId)
    {
        /**
         * @var \DDD\Dao\Apartment\OTADistribution $apartmentOTADao
         */
        $apartmentOTADao = $this->getOTADistributionDao();
        $channelList = $apartmentOTADao->getApartmentOTAList($apartmentId);

        return $channelList;
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
     *
     * @param \ArrayObject $data
     * @param int $apartmentId
     * @return boolean
     */
    public function saveOTA($data, $apartmentId)
    {
        /**
         * @var \DDD\Dao\Apartment\OTADistribution $apartmentOTADao
         */
        if (empty($data)) {
            return false;
        }

        $apartmentOTADao = $this->getOTADistributionDao();
        $params = [
            'apartment_id' => $apartmentId,
            'partner_id' => $data['ota_name'],
            'reference' => $data['ota_ref'],
            'url' => $data['ota_url'],
            'status' => 1,
        ];

        $insertId = $apartmentOTADao->save($params);

        return $insertId;
    }
    /**
     *
     * @param int $otaId
     * @return boolean
     */
    public function removeOTA($otaId)
    {
        /**
         * @var \DDD\Dao\Apartment\OTADistribution $apartmentOTADao
         */
        try {
            if($otaId > 0) {
                $apartmentOTADao = $this->getOTADistributionDao();
                $apartmentOTADao->delete(['id' => $otaId]);

                return true;
            }
            return false;
        } catch (\Exception $ex) {
			return false;
		}
    }

	private function getOTADistributionDao($domain = 'ArrayObject')
    {
		return new \DDD\Dao\Apartment\OTADistribution($this->getServiceLocator(), $domain);
	}
}
