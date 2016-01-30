<?php
chdir(__DIR__);

if (file_exists('/ginosi/incubator/backoffice/autoload.php')) {
    $loader = include '/ginosi/incubator/backoffice/autoload.php';
}

$zf2Path = false;

if (is_dir('/ginosi/incubator/backoffice/zendframework/zendframework')) {
    $zf2Path = '/ginosi/incubator/backoffice/zendframework/zendframework';
} else {
    echo "Cannot find Zend Framework library :(";
    exit;
}

if (isset($loader)) {
    $loader->add('Zend', $zf2Path);
} else {
    include $zf2Path . '/Zend/Loader/AutoloaderFactory.php';
    Zend\Loader\AutoloaderFactory::factory(array(
        'Zend\Loader\StandardAutoloader' => array(
            'autoregister_zf' => true
        )
    ));
}

// Run the application!
Zend\Mvc\Application::init(include 'module/Console/config/console.config.php')->run();
