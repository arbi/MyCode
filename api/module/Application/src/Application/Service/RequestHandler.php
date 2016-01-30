<?php

namespace Application\Service;

use Zend\ServiceManager\ServiceLocatorInterface;
use ApiLibrary\HttpResponses;

use Application\Entity\Error;
use ZF\ApiProblem\ApiProblem;

use ZF2Graylog2\Traits\Logger as ZF2Graylog2Logger;

class RequestHandler
{
    use ZF2Graylog2Logger;

    CONST IS_COMPLETE = 1;

    protected $serviceLocator = null;
    private $messageCode      = 0;
    private $httpCode         = 0;
    private $responseTitle    = '';

    const RESPONSE_TYPE_ARRAY = 0;
    const RESPONSE_TYPE_JSON  = 1;

    public function __construct(ServiceLocatorInterface $sl)
    {
        $this->serviceLocator = $sl;
    }

    public function getHttpContentBody($messageCode, $externalBody = false, $type = self::RESPONSE_TYPE_ARRAY)
    {
        $this->messageCode   = $messageCode;
        $this->responseTitle = $this->responseTitle($this->messageCode);
        $responseBody        = $this->createResponseBody($type, $externalBody);

        $this->apiMonitor($responseBody['code'], $this->messageCode, $this->responseTitle);

        return $responseBody;
    }

    private function responseTitle()
    {

        if (array_key_exists($this->messageCode, Error::$errorTitles)) {
            $this->httpCode = Error::$errorTitles[$this->messageCode]['httpCode'];
            return Error::$errorTitles[$this->messageCode]['message'];
        }
        return false;
    }

    private function createResponseBody($type, $externalBody)
    {
        $internalError = [
            'code'    => $this->messageCode,
            'message' => $this->responseTitle
        ];

        if ($externalBody) {
            $response = [
                "type"   => "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
                'title'  => Error::$errorTitles[$this->httpCode]['message'],
                'status' => $this->messageCode,
                'detail' => $internalError
            ];
        } else {
            $response = $internalError;
        }

        switch ($type) {
            case self::RESPONSE_TYPE_ARRAY:
                break;
            case self::RESPONSE_TYPE_JSON:
                $response = json_encode($response);
                break;
        }

        return ['code' => $this->httpCode, 'errorBody' => $response];
    }

    public function checkRequest($identity)
    {

        $token = $this->getToken();
        if (!$token) {
            return false;
        }

        $apiRequestDao = $this->serviceLocator->get('dao_api_api_requests');

        $duplicateRequest = $apiRequestDao->fetchOne(['identity' => $identity, 'access_token' => $token]);

        if (!$duplicateRequest) {
            $apiRequestDao->save(
                [
                    'identity'     => $identity,
                    'access_token' => $token,
                    'request_date' => date('Y-m-d H:i:s')
                ]
            );
        }

        if (!$duplicateRequest || ($duplicateRequest && !(int)$duplicateRequest['is_complete'])) {
            return false;
        }

        return true;
    }

    public function setCompleted($identity)
    {
        $token = $this->getToken();
        if (!$token) {
            return false;
        }

        $apiRequestDao = $this->serviceLocator->get('dao_api_api_requests');
        $apiRequestDao->save(['is_complete' => self::IS_COMPLETE], ['identity' => $identity, 'access_token' => $token]);
    }

    private function getToken()
    {
        try {
            $bearerTokenArray = [];
            $bearerToken      = $this->serviceLocator->get('request')->getHeaders()->get('Authorization')->getFieldValue();
            $bearerTokenArray = explode(' ', $bearerToken);
            $token            = $bearerTokenArray[1];

            return $token;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param \Exception $ex
     * @return JsonModel
     */
     public function handleException(\Exception $ex)
     {
         if ($ex instanceof ApiException) {
            $response = $this->getHttpContentBody($ex->getCode());
         } else {
            $response = $this->getHttpContentBody(Error::SERVER_SIDE_PROBLEM_CODE);
         }

         return new ApiProblem($response['code'], $response['errorBody']);
     }

     public function apiMonitor($httpCode, $internalCode, $message)
     {
         $scheme = $this->serviceLocator->get('Request')->getUri()->getScheme();
         $host   = $this->serviceLocator->get('Request')->getUri()->getHost();
         $path   = $this->serviceLocator->get('Request')->getUri()->getPath();
         $method = $this->serviceLocator->get('Request')->getMethod();

         $this->gr2err('Frontier Api was Problem', [
             'uri'           => $scheme . '://' . $host . $path,
             'uri_method'    => $method,
             'http_code'     => $httpCode,
             'internal_code' => $internalCode,
             'message'       => $message,
         ]);
     }
}
