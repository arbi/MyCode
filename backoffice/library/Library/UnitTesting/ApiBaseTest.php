<?php

namespace Library\UnitTesting;
use Library\Constants\DomainConstants;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ApiBaseTest extends AbstractHttpControllerTestCase
{

    public function setUp()
    {
        putenv("APPLICATION_ENV=development");
        $this->setApplicationConfig(
            include '/ginosi/api/config/application.config.php'
        );
        if (!defined('ROLE_GUEST')) {
            define('ROLE_GUEST', '0');
        }
        parent::setUp();
    }

    public static function assertEquals($expected, $actual, $message = '', $delta = 0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        if ('' === $message) {
            $message = $expected . ' is not equal to ' . $actual;
        }
        if ($actual != $expected) {
            self::fail($message);
        }
    }

    protected function getAccessToken()
    {
        $adapter  = $this->getApplicationServiceLocator()->get('dbadapter');

        $sql       = "SELECT * FROM oauth_access_tokens WHERE user_id = 'app.user@ginosi.com'";
        $statement = $adapter->createStatement($sql);
        $oauthUser = $statement->execute();

        if (!$oauthUser->count()) {
            $postData = [
                'username'      => 'app.user@ginosi.com',
                'password'      => '123456',
                'grant_type'    => 'password',
                'client_id'     => 'b4c3bcdaba1f8a5a58ca35139faf2e0f',
                'client_secret' => 'CSUHKs7VxsY7LTFxIJo8F+8BLYv73kQ7b3S/Rhe0vmRd8gFxrZXQfIsRgM1P00vWi9oCWJNuDhn2O4FDCDcXxQ==',
            ];
            $jsonData = json_encode($postData);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://' . DomainConstants::API_DOMAIN_NAME . '/oauth');
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

            return (array)$response;
        } else {
            $response = $oauthUser->current();

            return $response;
        }

    }
}
