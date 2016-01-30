<?php

namespace Website\View\Helper;

use Website\View\Helper\BaseHelper;
use DDD\Dao\Currency\Currency;
use Library\Utility\Helper;

class CurrencyUser extends BaseHelper
{
    public function __invoke()
    {
        $currecny = Helper::getCurrency();
	    return $currecny;
    }
}