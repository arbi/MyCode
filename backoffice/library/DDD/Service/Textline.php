<?php

namespace DDD\Service;

class Textline extends ServiceBase
{
    const KEY_INSTRUCTION = 64;
    const PARKING_TEXTLINE = 75;

    /**
     * @param int $id
     * @param bool $clean
     * @return bool
     */
    public function getUniversalTextline($id, $clean = false)
    {
        /**
         * @var \DDD\Dao\Textline\Universal $textlineUniversalDao
         */
        $textlineUniversalDao = $this->getServiceLocator()->get('dao_textline_universal');

        $resultArray = $textlineUniversalDao->getTextlineById($id);

        if (!$resultArray) {
            return FALSE;
        }
        if ($clean) {
            $result = $resultArray->getEnClean();
        } else {
            $result = $resultArray->getEn();
        }
        return $result;
    }

    /**
     * @param $id
     * @return bool
     */
    public function getProductTextline($id)
    {
        /**
         * @var \DDD\Dao\Textline\Apartment $texlineProductService
         */
        $texlineProductService = $this->getServiceLocator()->get('dao_textline_apartment');

        $resultArray = $texlineProductService->getProductTextlineById($id);

        if (!$resultArray) {
            return false;
        }

        return $resultArray['en'];
    }

    /**
     * @param $id
     * @return bool
     */
    public function getCityName($id)
    {
        /**
         * @var \DDD\Dao\Textline\Location $textlineLocationDao
         */
        $textlineLocationDao = $this->getServiceLocator()->get('dao_textline_location');

        $resultArray = $textlineLocationDao->getCityNameById($id);

        if (!$resultArray) {
            return FALSE;
        }
        return $resultArray->getEn();
    }

    /**
     * @param $id
     * @return bool
     */
    public function getProvinceName($id)
    {
        /**
         * @var \DDD\Dao\Textline\Location $textlineLocationDao
         */
        $textlineLocationDao = $this->getServiceLocator()->get('dao_textline_location');

        $resultArray = $textlineLocationDao->getProvinceNameById($id);

        if (!$resultArray) {
            return FALSE;
        }
        return $resultArray->getEn();
    }

    /**
     * @param $id
     * @return bool
     */
    public function getCountryName($id)
    {
        if ($id == 0) {
            $id = 39;
        }

        /**
         * @var \DDD\Dao\Textline\Location $textlineLocationDao
         */
        $textlineLocationDao = $this->getServiceLocator()->get('dao_textline_location');

        $resultArray = $textlineLocationDao->getCountryNameById($id);

        if (!$resultArray) {
            return FALSE;
        }
        return $resultArray->getEn();
    }
}
