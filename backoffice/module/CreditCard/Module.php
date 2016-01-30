<?php

namespace CreditCard;


use CreditCard\Model\CCCreationQueue;
use CreditCard\Model\FraudCC;
use CreditCard\Model\FraudCCHashes;
use CreditCard\Model\Token;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\ModuleManagerInterface;

/**
 * Class Module
 * @package CreditCard
 */
class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
       return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'service_card'                => 'CreditCard\Service\Card',
                'service_card_creation_queue' => 'CreditCard\Service\Queue',
                'service_store_cc'            => 'CreditCard\Service\Store',
                'service_retrieve_cc'         => 'CreditCard\Service\Retrieve',
                'service_salt_generator'      => 'CreditCard\Service\SaltGenerator',
                'service_encrypt'             => 'CreditCard\Service\Encrypt',
                'service_fraud_cc'            => 'CreditCard\Service\Fraud',
                'service_update_cc'           => 'CreditCard\Service\Update',

            ],
        	'factories' => [
                'CreditCard\Model\Token' =>  function($sm) {
                    return new Token($sm);
                },
                'CreditCard\Model\Queue' =>  function($sm) {
                    return new CCCreationQueue($sm);
                },
                'CreditCard\Model\FraudCC' =>  function($sm) {
                    return new FraudCC($sm);
                },
                'CreditCard\Model\FraudCCHashes' =>  function($sm) {
                    return new FraudCCHashes($sm);
                },
            ],
            'aliases'=> [
                'dao_cc_token'          => 'CreditCard\Model\Token',
                'dao_cc_creation_queue' => 'CreditCard\Model\Queue',
                'dao_fraud_cc'          => 'CreditCard\Model\FraudCC',
                'dao_fraud_cc_hashes'   => 'CreditCard\Model\FraudCCHashes',
            ],
        ];
    }
}
