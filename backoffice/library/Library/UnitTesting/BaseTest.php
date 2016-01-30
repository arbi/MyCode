<?php

namespace Library\UnitTesting;
use Library\Constants\DomainConstants;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class BaseTest extends AbstractHttpControllerTestCase
{
    const UNIT_TESTER_USER_ID = 13;
    public function setUp()
    {
        putenv("APPLICATION_ENV=development");
        $this->setApplicationConfig(
            include '/ginosi/backoffice/config/application.config.php'
        );
        if (!defined('ROLE_GUEST')) {
            define('ROLE_GUEST', '0');
        }
        parent::setUp();
        $backofficeAuthenticationService = $this->getApplicationServiceLocator()->get('library_backoffice_auth');
        $backofficeAuthenticationService->authenticate(null, 'test@ginosi.com', '123456');
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
}
