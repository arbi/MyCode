<?php

namespace ApiTest\Warehouse\Controller;

use Library\UnitTesting\ApiBaseTest;
use Library\Constants\DomainConstants;

class ConfigsResourceTest extends ApiBaseTest
{
    public $errorList = [400, 500];
    public function testFethAll()
    {
        $response = $this->getAccessToken();

        $this->assertArrayHasKey('access_token', $response);
        $accessToken = $response['access_token'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/warehouse/configs');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close ($ch);
        $response = json_decode($response);

        if (in_array('status', (array)$response)) {
            $this->assertTrue(false, 'Configs list request has problem');
        }

        $this->assertGreaterThan(0, count((array)$response), "There is no Config!");
    }
}
