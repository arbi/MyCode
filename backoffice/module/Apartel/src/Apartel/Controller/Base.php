<?php

namespace Apartel\Controller;

use Library\Controller\ControllerBase;

/**
 * Class Base
 * @package Apartel\Controller
 *
 * @author Tigran Petrosyan
 */
class Base extends ControllerBase
{
    /**
     *
     * @access protected
     * @var int
     */
    protected $apartelId;

    /**
     * @param $apartelId
     * @return $this
     */
    public function setApartelId($apartelId)
    {
        /**
         * @var \DDD\Dao\Apartel\General $generalDao
         */
        $generalDao = $this->getServiceLocator()->get('dao_apartel_general');
        $isApartel = $generalDao->isApartel($apartelId);
        if ($isApartel) {
            $this->apartelId = $apartelId;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getApartelId()
    {
        return $this->apartelId;
    }
}