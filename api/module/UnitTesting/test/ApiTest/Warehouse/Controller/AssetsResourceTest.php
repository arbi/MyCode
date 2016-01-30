<?php

namespace ApiTest\Warehouse\Controller;

use Library\UnitTesting\ApiBaseTest;
use Library\Constants\DomainConstants;

class AssetsResourceTest extends ApiBaseTest
{
    /**
     * @var array
     */
    public $errorList = [400, 500];


    public function testFetchAll()
    {
        $response = $this->getAccessToken();

        $this->assertArrayHasKey('access_token', $response);
        $accessToken = $response['access_token'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/warehouse/assets');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close ($ch);
        $response = json_decode($response);

        if (in_array('status', (array)$response)) {
            $this->assertTrue(false, 'Assets list request has problem');
        }
    }

    public function testFetch()
    {
        $response = $this->getAccessToken();

        $this->assertArrayHasKey('access_token', $response);
        $accessToken = $response['access_token'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/warehouse/assets/koko');

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close ($ch);
        $response = json_decode($response);

        if (in_array('status', (array)$response)) {
            $this->assertTrue(false, 'Assets list request has problem');
        }

        $this->assertGreaterThan(0, count((array)$response), "There is no asset!");
    }

    public function testPost()
    {
        $response = $this->getAccessToken();

        $this->assertArrayHasKey('access_token', $response);
        $accessToken = $response['access_token'];

        $postData = [
            "uuid"               => rand(10000, 9999999999),
            "assetType"          => 1,
            "locationEntityId"   => 1,
            "locationEntityType" => 2,
            "quantity"           => 3000,
            "categoryId"         => 1,
            "status"             => 0,
            "barcode"            => rand(10000, 9999999999),
            "assigneeId"         => "",
            "name"               => "Toilet Paper",
            "shipmentStatus"     => 1,
            "comment"            => "new Toilet paper"
        ];

        $jsonData = json_encode($postData);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/warehouse/assets');
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
        $response = (array)$response;

        if (   array_key_exists('status', $response)
            && in_array($response['status'], $this->errorList)
        ) {
            $this->assertTrue(false, $response['detail']->message);
        }

        $this->assertGreaterThan(0, count($response), "There is no asset!");
    }

    public function testPatch()
    {
        $response = $this->getAccessToken();

        $this->assertArrayHasKey('access_token', $response);
        $accessToken = $response['access_token'];

        $postData = [
            "uuid"               => rand(10000, 9999999999),
            "assetType"          => 2,
            "locationEntityId"   => 1,
            "locationEntityType" => 2,
            "quantity"           => 3000,
            "categoryId"         => 1,
            "status"             => rand(1,6),
            "barcode"            => rand(10000, 9999999999),
            "assigneeId"         => "",
            "name"               => "Toilet Paper",
            "shipmentStatus"     => 1,
            "comment"            => "new Toilet paper"
        ];

        $jsonData = json_encode($postData);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/warehouse/assets/1');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
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
        $response = (array)$response;

        if (   array_key_exists('status', $response)
            && in_array($response['status'], $this->errorList)
        ) {
            $detail = (array)$response['detail'];
            $this->assertTrue(false, $response['status'] . $detail['message']);
        }

        $this->assertGreaterThan(0, count((array)$response), "There is no asset!");
    }

    public function testDuplicateRequest()
    {
        $response = $this->getAccessToken();

        $this->assertArrayHasKey('access_token', $response);
        $accessToken = $response['access_token'];

        $uuid = rand(10000, 9999999999);
        for ($i = 0; $i <= 1; $i++) {
            $postData = [
                "uuid"               => $uuid,
                "assetType"          => 2,
                "locationEntityId"   => 1,
                "locationEntityType" => 2,
                "quantity"           => 3000,
                "categoryId"         => 1,
                "status"             => rand(1, 6),
                "barcode"            => rand(10000, 9999999999),
                "assigneeId"         => "",
                "name"               => "Toilet Paper",
                "shipmentStatus"     => 1,
                "comment"            => "new Toilet paper"
            ];

            $jsonData = json_encode($postData);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/warehouse/assets');
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
            $response = (array)$response;

            if ($i == 1) {
                $this->assertArrayHasKey('status', $response, "For duplicate request it should return authentication error. ");

                $detail = (array)$response['detail'];
                $this->assertEquals(409, $detail['code'], "It should show duplicate internal error code: 409 instead of {$detail['code']}. ");
            }
        }
    }
}
