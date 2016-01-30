<?php

namespace Architecture\BackofficeTest;

use Library\UnitTesting\BaseTest;

class CheckControllerTest extends BaseTest
{
    /**
     * Test setter
     */
    public function testMissing()
    {
        // get backoffice modules
        $moduleManager = $this->getApplicationServiceLocator()->get('ModuleManager');
        $modulesLoaded = $moduleManager->getModules();

        $boModulePath     = '/ginosi/backoffice/module/';
        $boTestModulePath = '/ginosi/backoffice/module/UnitTesting/test/BackofficeTest/';
        $ds               = DIRECTORY_SEPARATOR;

        foreach ($modulesLoaded as $module) {
            $controllerPath = $boModulePath . $module . $ds . 'src' . $ds . $module . $ds . 'Controller';

            if (is_dir($controllerPath)) {
                $controllerFiles = scandir($controllerPath);

                foreach ($controllerFiles as $file) {
                    if ($file != '.' && $file != '..') {

                        if (strpos($file, 'Controller') === false) {
                            $controllerName = substr($file, 0, -4);
                        } else {
                            $controllerName = substr($file, 0, strpos($file, 'Controller'));
                        }

                        $boTestController = $boTestModulePath . $module . $ds . 'Controller' . $ds . $controllerName . 'ControllerTest.php';

                        $this->assertFileExists($boTestController, $module . ' Module ' . $controllerName . ' Controller test not found');
                    }
                }
            }
        }
    }
}