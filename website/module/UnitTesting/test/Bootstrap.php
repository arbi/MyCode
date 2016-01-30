<?php

namespace UnitTesting;

use Zend\Loader\AutoloaderFactory;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use RuntimeException;

error_reporting(E_ALL);
chdir(__DIR__);

/**
 * Test bootstrap, for setting up autoloading
 */
class Bootstrap
{
    /**
     * @var
     */
    protected static $serviceManager;

    /**
     *
     */
    public static function init()
    {
        ob_start();
        $zf2ModulePaths = array(dirname(dirname(__DIR__)));
        if (($path = static::findParentPath('/ginosi/incubator/website'))) {
            $zf2ModulePaths[] = $path;
        }
        if (($path = static::findParentPath('module')) !== $zf2ModulePaths[0]) {
            $zf2ModulePaths[] = $path;
        }

        static::initAutoloader();

        // use ModuleManager to load this module and it's dependencies
        $config = array(
            'module_listener_options' => array(
                'module_paths' => $zf2ModulePaths,
            ),
            'modules' => array(
                'Website'
            )
        );

        $serviceManager = new ServiceManager(new ServiceManagerConfig());
        $serviceManager->setService('ApplicationConfig', $config);
        $serviceManager->get('ModuleManager')->loadModules();
        static::$serviceManager = $serviceManager;
    }

    /**
     *
     */
    public static function chroot()
    {
        $rootPath = dirname(static::findParentPath('module'));
        chdir($rootPath);
    }

    /**
     * @return mixed
     */
    public static function getServiceManager()
    {
        return static::$serviceManager;
    }

    /**
     *
     */
    protected static function initAutoloader()
    {
        $vendorPath = '/ginosi/incubator/website';

        $zf2Path = '/ginosi/incubator/website/zendframework';

        if (!is_dir($zf2Path)) {
            throw new RuntimeException(
                'Unable to load ZF2. Run `php composer.phar install` or'
                . ' define a ZF2_PATH environment variable.'
            );
        }

        if (file_exists($vendorPath . '/autoload.php')) {
            include $vendorPath . '/autoload.php';
        }

        include $zf2Path . '/zend-loader/src/AutoloaderFactory.php';
        AutoloaderFactory::factory(array(
            'Zend\Loader\StandardAutoloader' => array(
                'autoregister_zf' => true,
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/' . __NAMESPACE__,
                ),
            ),
        ));
    }

    /**
     * @param $path
     * @return bool|string
     */
    protected static function findParentPath($path)
    {
        $dir = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/' . $path)) {
            $dir = dirname($dir);
            if ($previousDir === $dir) {
                return false;
            }
            $previousDir = $dir;
        }
        return $dir . '/' . $path;
    }
}

Bootstrap::init();
Bootstrap::chroot();