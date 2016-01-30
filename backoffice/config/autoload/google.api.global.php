<?php
use Library\Constants\DomainConstants;

if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
    $secureConnection = TRUE;
    $protocol = 'https://';
} else {
    $secureConnection = FALSE;
    $protocol = 'http://';
}

return [
    'google-api' => [
        'clientId'     => '__client_id__',
       'clientSecret' => '__client_secret__',
       'redirectUri'  => $protocol . DomainConstants::BO_DOMAIN_NAME . '/authentication/googlesignin',
       'developerKey' => 'AIzaSyAcA3jWBD-p_0k6KwTHAquiGrzxaJAQJoM',
       'scopes'       => [
                           'https://www.googleapis.com/auth/userinfo.email',
                         ]
   ]
];
