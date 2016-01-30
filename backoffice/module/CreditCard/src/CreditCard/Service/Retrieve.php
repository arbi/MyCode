<?php

namespace CreditCard\Service;

use CreditCard\Entity\CompleteData;
use CreditCard\Entity\RemoteData;
use CreditCard\Model\Token as TokenDAO;
use CreditCard\Rest\RestRequest;
use DDD\Service\ServiceBase;
use Library\Constants\DomainConstants;
/**
 * Class Retrieve
 * @package CreditCard\Service
 *
 * @author Tigran Petrosyan
 */
class Retrieve extends ServiceBase
{
    /**
     * @param $creditCardId
     * @return CompleteData
     */
    public function getCreditCard($creditCardId)
    {
        $localData    = $this->getLocalData($creditCardId);
        $token        = $localData->getToken();
        $remoteData   = $this->getRemoteData($token);
        $completeData = new CompleteData();

        $completeData->setPan($localData->getFirstDigits() . $remoteData->getLast10());
        $completeData->setSecurityCode($remoteData->getSecurityCode());
        $completeData->setExpirationMonth($remoteData->getExpirationMonth());
        $completeData->setExpirationYear($remoteData->getExpirationYear());
        $completeData->setHolder($remoteData->getHolder());
        $completeData->setBrand($localData->getBrand());

        return $completeData;
    }

    /**
     * @param $creditCardId
     * @return array|\CreditCard\Entity\LocalData|null
     */
    public function getLocalData($creditCardId)
    {
        /**
         * @var TokenDAO $tokenDao
         */
        $tokenDao  = $this->getServiceLocator()->get('dao_cc_token');

        $localData = $tokenDao->getLocalData($creditCardId);

        /**
         * @var Encrypt $encryptService
         */
        $encryptService = $this->getServiceLocator()->get('service_encrypt');

        $decrypted      = $encryptService->decrypt($localData->getFirstDigits(), $localData->getSalt());

        $localData->setFirstDigits($decrypted);
        return $localData;
    }

    /**
     * @param $token
     * @return RemoteData
     */
    public function getRemoteData($token)
    {
        $remoteData = new RemoteData();

        if (DomainConstants::BO_DOMAIN_NAME != "backoffice.ginosi.com") {

            $remoteData->setHolder('Gnam Gnam');
            $remoteData->setLast10('4444444444');
            $remoteData->setSecurityCode('123');
            $remoteData->setExpirationMonth('11');
            $remoteData->setExpirationYear('2020');

        } else {
            $request = new RestRequest(
                'GET',
                [

                ]
            );

            $config      = $this->getServiceLocator()->get('Config');
            $apiEndpoint = $config['vault-configuration']['storage_api_endpoint'];
            $apiPort     = $config['vault-configuration']['storage_api_port'];
            $apiKey      = $config['vault-configuration']['storage_api_key'];

            $request->setUrl($apiEndpoint . '?token=' . $token);
            $request->setPort($apiPort);
            $request->setApiKey($apiKey);

            $request->execute();

            $response = $request->getResponseBody();

            $responseArray = json_decode($response);

            $remoteData->setHolder(isset($responseArray->holder) ? $responseArray->holder : '');
            $remoteData->setLast10(isset($responseArray->last10) ? $responseArray->last10 : '');
            $remoteData->setSecurityCode(isset($responseArray->security_code) ? $responseArray->security_code : '');
            $remoteData->setExpirationMonth(isset($responseArray->exp_month) ? $responseArray->exp_month : '');
            $remoteData->setExpirationYear(isset($responseArray->exp_year) ? $responseArray->exp_year : '');
        }

        return $remoteData;
    }

    public function getCreditCardSafeData($creditCardId)
    {

    }
}
