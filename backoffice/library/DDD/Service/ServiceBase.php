<?php

namespace DDD\Service;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Library\Registry\Registry;
use Library\Utility\Helper;
use ZF2Graylog2\Traits\Logger;

/**
 * Base class for all Services
 * @abstract
 * @category Core
 * @package base
 *
 * @author
 */
abstract class ServiceBase implements ServiceLocatorAwareInterface
{
    // using service locator aware trait to inherit service locator getter and setter
    use Logger, ServiceLocatorAwareTrait;

    const EMERG  = 0;
    const ALERT  = 1;
    const CRIT   = 2;
    const ERR    = 3;
    const WARN   = 4;
    const NOTICE = 5;
    const INFO   = 6;
    const DEBUG  = 7;

	/**
	 * Registry object
	 *
	 * @var Registry
	 */
    public $registry;

    /**
     * Array of data access objects used to implement lazy loader
     * @access private
     * @var array
     */
    private $daoArray = array();

    /**
     * Array of actions to do in transaction
     * @var array
     */
    private $transactionStack = array();

    /**
     * @var null
     */
    private $_language            = null;

    /**
     * @var null
     */
    private $_currency            = null;

    /**
     * @var null
     */
    private $_textLineSite        = null;

    /**
     * Call parent constructor in child classes to initialize Registry object
     */
    public function __construct() {
        $this->registry = Registry::getInstance();
    }

    /**
     * Allow to add service methods to transaction stack
     *
     * @param string $key
     * @param ServiceBase $serviceObject
     * @param string $method
     */
    public function addActionToTransactionStack($key, $serviceObject, $method)
    {
    	// check to ensure that method exist in service class object
    	if (method_exists($serviceObject, $method)) {
    		$item = array(
    			"object" => $serviceObject,
    			"method" => $method
    		);
    		if (array_key_exists($key, $this->transactionStack)){
    			/*
    			 * @todo generate exception
    			 */
    		}else {
    			$this->transactionStack[$key] = $item;
    		}
    	}else {
    		/*
    		 * @todo generate exception
    		 */
    	}
    }

    /**
     * Allow to remove service methods from transaction stack
     *
     * @param string $key
     */
    public function removeActionFromTransactionStack($key)
    {
    	if (array_key_exists($key, $this->transactionStack)){
    		unset($this->transactionStack[$key]);
    	}
    }

    /**
     * Call all methods in transaction stack
     *
     * @return boolean result
     */
    public function doTransaction()
    {
    	// getting the db adapter
    	$dbAdapter = \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter();

    	$stack = $this->transactionStack;

    	if (count($stack)) {
    		/*
    		 * begin transaction
    		 */
    		$dbAdapter->getDriver()->getConnection()->beginTransaction();

    		$overAllSuccess = true;

    		foreach ($stack as $action) {
    			$serviceObject = $action["object"];
    			$serviceMethod = $action["method"];

    			$actionResult = $serviceObject->$serviceMethod();

    			if ($actionResult) {
    				continue;
    			}else {
    				$overAllSuccess = false;
    				break;
    			}
    		}

    		// check for overall success
    		if ($overAllSuccess) {
    			/*
    			 * commit
    			 */
    			$dbAdapter->getDriver()->getConnection()->commit();

    			// clearing stack
    			unset($this->transactionStack);
    			$this->transactionStack = array();

    			return true;
    		}else {
    			/*
    			 * rollback
    			 */
    			$dbAdapter->getDriver()->getConnection()->rollback();

    			return false;
    		}
    	}else {
    		// there is no any method in transaction to call
    		return false;
    	}
    }

    /**
     * @param string $dao
     * @return \DDD\Dao\Currency\Currency
     */
    public function getDao($dao)
    {
    	if (array_key_exists($dao, $this->daoArray)) {
    		return $this->daoArray[$dao];
    	}

    	$this->daoArray[$dao] = $this->getServiceLocator()->get($dao);
    	return $this->daoArray[$dao];
    }

    /**
     *
     * @return string language
     */

	public function getLanguage()
    {
        if ($this->_language === null) {
            $this->_language = Helper::getLanguage();
        }
    	return $this->_language;
    }

    /**
     *
     * @return string language
     */

	public function getCurrencySite()
    {
        if ($this->_currency === null) {
            $this->_currency = Helper::getCurrency();
        }
    	return $this->_currency;
    }

    /**
     *
     * @return string language
     */

	public function getTextLineSite($id)
    {
        if ($this->_textLineSite === null) {
            $helperTextLine = new \Website\View\Helper\Textline();
            $helperTextLine->setServiceLocator($this->getServiceLocator());
            $this->_textLineSite = $helperTextLine;
        }
        return $this->_textLineSite->getFromCache($id);
    }

    /**
     * @return array
     */
    public function getVisitorCountry()
    {
        if (!isset($this->_country)) {
            $this->_country = Helper::getUserCountry();
        }
        return $this->_country;
    }
}