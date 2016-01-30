<?php

namespace CreditCard\Service;

use CreditCard\Rest\RestRequest;
use DDD\Service\ServiceBase;
use Library\Constants\DomainConstants;
/**
 * Class Update
 * @package CreditCard\Service
 *
 * @author Tigran Petrosyan
 */
class Update extends ServiceBase
{
    /**
     * @param $token
     * @param $expirationMonth
     * @param $expirationYear
     * @return bool
     */
    public function updateRemoteData($token, $expirationMonth, $expirationYear)
    {
        if (DomainConstants::BO_DOMAIN_NAME != "backoffice.ginosi.com") {
            return true;
        } else {

            $request = new RestRequest(
                'POST',
                [
                    'token' => $token,
                    'exp_month' => $expirationMonth,
                    'exp_year' => $expirationYear
                ]
            );

            $config      = $this->getServiceLocator()->get('Config');
            $apiEndpoint = $config['vault-configuration']['storage_api_endpoint'];
            $apiPort     = $config['vault-configuration']['storage_api_port'];
            $apiKey      = $config['vault-configuration']['storage_api_key'];

            $request->setUrl($apiEndpoint);
            $request->setPort($apiPort);
            $request->setApiKey($apiKey);

            $request->execute();

            $response = $request->getResponseInfo();

            if ($response['http_code'] == 200) {
                return true;
            } else {
                return false;
            }
        }
    }
}
