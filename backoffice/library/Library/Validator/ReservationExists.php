<?php

namespace Library\Validator;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Validator\Db\RecordExists;
use Library\Constants\DbTables;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterAwareInterface;

/**
 * Class ReservationExists
 * @package Library\Validator
 *
 * @author Tigran Petrosyan
 */
class ReservationExists extends RecordExists
{
    /**
     * @param [] $options
     */
    public function __construct($options = null)
    {
        $options['table'] = DbTables::TBL_BOOKINGS;
        $options['field'] = 'res_number';
        parent::__construct($options);
    }

    /**
     * @param string $reservationNumber
     * @return bool
     */
    public function isValid($reservationNumber)
    {
		return parent::isValid($reservationNumber);
	}
}

?>