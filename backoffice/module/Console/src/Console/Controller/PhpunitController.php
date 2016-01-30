<?php

namespace Console\Controller;

use DDD\Service\UnitTesting;
use Library\Controller\ConsoleBase;
use DDD\Service\User as UserService;

/**
 * Class    PhpunitController
 * @package Console\Controller
 * @author  Harut Grigoryan
 */
class PhpunitController extends ConsoleBase
{
    /**
     * Execute all tests or by mode
     *
     * @return bool|void
     */
    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());
        $app = $this->getRequest()->getParam('app', false);

        // all tests from constant
        $apps = [
            'backoffice' => UnitTesting::LOG_BO_FILE_PATH,
            'website'    => UnitTesting::LOG_WEB_FILE_PATH,
            'api'        => UnitTesting::LOG_API_FILE_PATH
        ];

        // run by mode
        if ($app) {
            if (isset($apps[$app])) {
                $directory = $this->getDirectory($app, $apps[$app]);
                $output    = shell_exec('phpunit -c ' . $directory);
                return $this->outputMessage($output);
            }

            return $this->outputMessage('[error]' . $app . ' App Not Found for Testing');
        }

        // run all
        foreach ($apps as $appName => $logFile) {
            $directory = $this->getDirectory($appName, $logFile);
            $output = shell_exec('phpunit -c ' . $directory);
            return $this->outputMessage($output);
        }
    }

    /**
     * Return directory for testing
     *
     * @param  $app
     * @param  $path
     * @return string
     */
    private function getDirectory($app, $path)
    {
        $logNamePosition = strpos($path, 'logfile.json');
        $directory       = substr($path, 0, $logNamePosition);

        if (!is_dir($directory)) {
            $this->outputMessage('[error]' . $app . 'Test directory not found');
            exit;
        }

        return $directory;
    }
}
