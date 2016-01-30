<?php

namespace Library\Constants;

$env = getenv('APPLICATION_ENV');

if ($env === FALSE) {
    $env = 'development';
}

if ($env === 'production') {
    // Production
    class EmailAliases
    {
        const FROM_MAIN_MAIL                            = 'reservations@ginosi.com';

        const FROM_ALERT_MAIL		                    = 'alert@ginosi.com';

        const TO_CURRENCY_CHECK                         = 'currencyupdate@ginosi.com';
        const FROM_CURRENCY_CHECK                   	= 'alert@ginosi.com';

        const TO_RESERVATION                            = 'resteam@ginosi.com';
        const RT_RESERVATION                            = 'reservations@ginosi.com';

        const TO_MODIFICATION                           = 'resteam@ginosi.com';
        const RT_MODIFICATION                           = 'reservations@ginosi.com';

        const TO_CANCELLATION                           = 'resteam@ginosi.com';
        const RT_CANCELLATION                           = 'reservations@ginosi.com';

        const TO_CONTACT                                = 'contact@ginosi.com';

        const TO_ALERT_INVENTORY_SYNC_QUEUE_FAIL        = 'development@ginosi.com';

        const HR_EMAIL                                  = 'hr@ginosi.com';
    }
} else {
    // Development
    class EmailAliases
    {
        const FROM_MAIN_MAIL                            = 'reservations@ginosi.com';

        const FROM_ALERT_MAIL		                    = 'alert@ginosi.com';

        const TO_CURRENCY_CHECK                         = 'test@ginosi.com';
        const FROM_CURRENCY_CHECK	                    = 'alert@ginosi.com';

        const TO_RESERVATION                            = 'test@ginosi.com';
        const RT_RESERVATION                            = 'reservations@ginosi.com';

        const TO_MODIFICATION                           = 'test@ginosi.com';
        const RT_MODIFICATION                           = 'reservations@ginosi.com';

        const TO_CANCELLATION                           = 'test@ginosi.com';
        const RT_CANCELLATION                           = 'reservations@ginosi.com';

        const TO_CONTACT                                = 'test@ginosi.com';

        const TO_ALERT_INVENTORY_SYNC_QUEUE_FAIL        = 'development@ginosi.com';

        const HR_EMAIL                                  = 'test@ginosi.com';
    }
}