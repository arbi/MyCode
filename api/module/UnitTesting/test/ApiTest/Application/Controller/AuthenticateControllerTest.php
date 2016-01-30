<?php

namespace ApiTest\Application\Controller;

use Library\UnitTesting\ApiBaseTest;
use Library\Constants\DomainConstants;

class AuthenticateControllerTest extends ApiBaseTest
{
    public $errorList = [400, 500];
    public function testLogin()
    {
        $adapter  = $this->getApplicationServiceLocator()->get('dbadapter');
        $response = $this->getAccessToken();

        $this->assertArrayHasKey('access_token', $response);
        $accessToken = $response['access_token'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/auth/login');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close ($ch);
        $response = json_decode($response);

        $sql = 'SELECT user_id FROM oauth_access_tokens WHERE access_token = "' . $accessToken . '"';
        $statement = $adapter->createStatement($sql);
        $oauthUser = $statement->execute();
        $oauthUser = $oauthUser->current();

        $sql       = 'SELECT id FROM ga_bo_users WHERE email = "' . $oauthUser['user_id'] .'"';
        $statement = $adapter->createStatement($sql);
        $user      = $statement->execute();

        $this->assertEquals(1, $user->count(), 'User with email: ' . $oauthUser['user_id'] . ' not found. ');


        if (in_array('status', (array)$response)) {
            $this->assertTrue(false, 'Authenticate faild. ');
        }
    }

    public function testRefreshToken()
    {
        $adapter  = $this->getApplicationServiceLocator()->get('dbadapter');
        $response = $this->getAccessToken();

        $this->assertArrayHasKey('access_token', $response);

        $sql          = "SELECT refresh_token FROM oauth_refresh_tokens WHERE user_id = 'app.user@ginosi.com' LIMIT 1";
        $statement    = $adapter->createStatement($sql);
        $result       = $statement->execute();

        $this->assertEquals(1, $result->count(), 'Refresh token not found. ');

        $refreshToken = $result->current();

        $sql          = "SELECT * FROM oauth_clients LIMIT 1";
        $statement    = $adapter->createStatement($sql);
        $result       = $statement->execute();

        $this->assertEquals(1, $result->count(), 'Oauth client not found. ');

        $oauthClient = $result->current();


        $postData = [
            "grant_type"    => 'refresh_token',
            "refresh_token" => $refreshToken['refresh_token'],
            "client_id"     => $oauthClient['client_id'],
            "client_secret" => $oauthClient['client_secret']
        ];

        $jsonData = json_encode($postData);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/auth/refresh-token');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
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
            $this->assertTrue(false, 'Problem in refreshing token. ');
        }
    }

    public function testlogOut()
    {
        $response = $this->getAccessToken();

        $this->assertArrayHasKey('access_token', $response);
        $accessToken = $response['access_token'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/auth/logout');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close ($ch);
        $response = json_decode($response);

        if (in_array('status', (array)$response)) {
            $this->assertTrue(false, 'Problem in logout. ');
        }
    }
}
