<?php

namespace Library\Finance\Base;

use Library\Finance\CreditCard\CreditCardEntity;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF2Graylog2\Traits\Logger;

class CreditCardBase
{
    use ServiceLocatorAwareTrait;
    use Logger;
    // Card Types
    const UNKNOWN     = 0;
    const VISA        = 1;
    const MASTERCARD  = 2;
    const AMEX        = 3;
    const DISCOVER    = 4;
    const JCB         = 5;
    const DINERS_CLUB = 6;
    const DINERS_CLUB_US = 7;
    const MASTERCARD_OR_DINERS_CLUB = 8;

    // Card Statuses
    const CARD_UNKNOWN = 1;
    const CARD_VALID = 2;
    const CARD_INVALID = 3;
    const CARD_TEST = 4;
    const CARD_FRAUD = 5;
    const CARD_DO_NOT_USE = 6;

    // Transaction Statuses
    const TRANSACTION_UNKNOWN = 1;
    const TRANSACTION_VALID = 2;
    const TRANSACTION_INVALID = 3;

    const DEFAULT_CVC = 123;
    const DINERS_CLUB_US_FIRST_VALUE = 5;

    public static $notSureMasterOrDinersClubSuffixes = ['54','55'];

    /**
     * @var CreditCardEntity|null
     */
    protected $creditCardEntity;

    /**
     * @var CC $ccDao
     */
    protected $ccDao;

    /**
     * @param ServiceLocatorInterface $sm
     */
    public function __construct($sm)
    {
        $this->setServiceLocator($sm);

        $this->ccDao = new CC($this->getServiceLocator(), '\ArrayObject');
    }

    /**
     * @param CreditCardEntity $creditcardEntity
     */
    public function setEntity($creditcardEntity)
    {
        $this->creditCardEntity = $creditcardEntity;
    }

    /**
     * @return CreditCardEntity|null
     */
    public function getEntity()
    {
        return $this->creditCardEntity;
    }
}
