<?php

namespace Library\Finance\CreditCard;

use Zend\Validator\CreditCard as ZendCreditCard;

class CreditCardValidator extends ZendCreditCard
{

    /**
     *
     * @param string $firstSixNumbersFromCardNumber
     * @return string|boolean
     */
    public function getCardTypeByNumber($firstSixNumbersFromCardNumber)
    {
        if (is_numeric($firstSixNumbersFromCardNumber)) {
            $cardPrefix = substr($firstSixNumbersFromCardNumber, 0, 6);

            foreach ($this->cardType as $type => $typePrefixes) {
                foreach ($typePrefixes as $prefix) {
                    if (substr($cardPrefix, 0, strlen($prefix)) == $prefix) {
                        return $type;
                    }
                }
            }
        }

        return FALSE;
    }

}
