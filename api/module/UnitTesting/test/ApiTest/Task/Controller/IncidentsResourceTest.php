<?php

namespace ApiTest\Task\Controller;

use Library\UnitTesting\ApiBaseTest;
use Library\Constants\DomainConstants;

class IncidentsResourceTest extends ApiBaseTest
{
    public $errorList = [400, 500];
    
    public function testPost()
    {
        $response = $this->getAccessToken();

        $this->assertArrayHasKey('access_token', $response);
        $accessToken = $response['access_token'];

        $postData = [
            "uuid"               => rand(10000, 9999999999),
            "locationEntityId"   => 1,
            "locationEntityType" => rand(1,4),
            "description"        => "new Toilet paper"
        ];

        $jsonData = json_encode($postData);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/task/incidents');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close ($ch);
        $response = json_decode($response);

        if (in_array('status', (array)$response)) {
            $this->assertTrue(false, 'Problem in adding incident report. ');
        }
    }
}
