<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
error_reporting(E_ALL);
defined('DS') 
    || define('DS', DIRECTORY_SEPARATOR); 
defined('BASE_PATH')
    || define('BASE_PATH', realpath(dirname(__FILE__) . DS .'..' . DS));

chdir(dirname(__DIR__));

require_once BASE_PATH .DS. 'library' .DS. 'Library' .DS. 'Constants' .DS. 'consts.php';

// Setup autoloading
require 'init_autoloader.php';

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
