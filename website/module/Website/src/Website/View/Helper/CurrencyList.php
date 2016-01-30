<?php

namespace Website\View\Helper;

use Website\View\Helper\BaseHelper;
use DDD\Dao\Currency\Currency;

class CurrencyList extends BaseHelper
{
    public function __invoke()
    {
        $currencyDao  = new Currency($this->serviceLocator, 'ArrayObject');
        $currecnyList = $currencyDao->getCurrencyListForSite();
	    return $currecnyList;
    }
}