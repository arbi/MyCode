<?php

namespace Library\IpInfo;
use Library\IpInfo\Entity\IdentityInformation;
use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Validator\Exception\InvalidArgumentException;
use Zend\Validator\Ip;

/**
 * Class IpInfo
 * @package Library\IpInfo
 *
 * @author Tigran Petrosyan
 */
class IpInfo
{
    /**
     * @var string
     */
    private $endPoint;

    function __construct()
    {
        $this->endPoint = 'http://ipinfo.io/';
    }

    /**
     * @param $ipString
     * @return IdentityInformation
     * @throws \Zend\Validator\Exception\InvalidArgumentException
     */
    public function getIpInfo($ipString)
    {
        $ipValidator = new Ip();
        if (!$ipValidator->isValid($ipString)) {
            throw new InvalidArgumentException();
        }

        // creating request object
        $request = new Request();
        $request->setUri($this->endPoint . $ipString . '/json');


        $client = new Client();
        $adapter = new Client\Adapter\Curl();
        $adapter->setCurlOption(CURLOPT_TIMEOUT_MS, 500);
        $client->setAdapter($adapter);

        $response = $client->send($request);

        $data = $response->getBody();
        $dataArray = json_decode($data);

        $identityInformation = new IdentityInformation();
        $identityInformation->setCountry(isset($dataArray->country) ? $dataArray->country : '');
        $identityInformation->setRegion(isset($dataArray->region) ? $dataArray->region : '');
        $identityInformation->setCity(isset($dataArray->city) ? $dataArray->city : '');
        $identityInformation->setLocation(isset($dataArray->loc) ? $dataArray->loc : '');
        $identityInformation->setProvider(isset($dataArray->org) ? $dataArray->org : '');
        $identityInformation->setHostName(isset($dataArray->hostname) ? $dataArray->hostname : '');

        return $identityInformation;
    }
} 