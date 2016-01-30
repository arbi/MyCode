<?php

namespace DDD\Service\Website;

use DDD\Service\ServiceBase;
use DDD\Dao\Textline\Apartment;

/**
 * Class Textline
 * @package DDD\Service\Website
 */
class Textline extends ServiceBase
{

    public $_apartmentDao = FALSE;

    /**
     * @param $apartmentId
     * @return bool
     */
    public function getApartmentDirectKeyInstructionTextline($apartmentId)
    {
        try {
            $this->getApartmentDao();
            $apartmentTextline = $this->_apartmentDao->getApartmentDirectEntryTextline($apartmentId);
            $buildingTextline  = $this->_apartmentDao->getBuildingDirectEntryTextline($apartmentId);
            $return = $apartmentTextline->getEnText();
            if ($buildingTextline) {
                $return = $buildingTextline->getEnText() . ' ' . $return;
            }
            return $return;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $apartmentId
     * @return bool
     */
    public function getApartmentReceptionKeyInstructionTextline($apartmentId)
    {
        try {
            $apartmentDao = new \DDD\Dao\Accommodation\Accommodations($this->getServiceLocator());

            $textlines = $apartmentDao->getApartmentReceptionEntryTextline($apartmentId);

            if (!$textlines['en_text']) {
                return false;
            }

            return $textlines['en_text'];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     *
     * @return \DDD\Dao\Textline\Apartment
     */
    public function getApartmentDao()
    {
        if (!$this->_apartmentDao) {
            $this->_apartmentDao = new Apartment($this->getServiceLocator());
        }

        return $this->_apartmentDao;
    }
}
