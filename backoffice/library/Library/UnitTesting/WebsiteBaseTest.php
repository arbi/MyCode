<?php

namespace Library\UnitTesting;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class WebsiteBaseTest extends AbstractHttpControllerTestCase
{

    public function setUp()
    {
        putenv("APPLICATION_ENV=development");
        $this->setApplicationConfig(
            include '/ginosi/website/config/application.config.php'
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
}
