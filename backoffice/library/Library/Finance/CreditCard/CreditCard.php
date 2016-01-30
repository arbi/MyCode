<?php

namespace Library\Finance\CreditCard;

use Library\Finance\Base\CreditCardBase;
use Library\Finance\Exception\CreditCardNotFoundException;
use Zend\Db\TableGateway\Exception\RuntimeException;

class CreditCard extends CreditCardBase
{
    use CreditCardHelper;



}
