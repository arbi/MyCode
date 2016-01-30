<?php

namespace WHOrder;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;

class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/' , __NAMESPACE__),
                ],
            ],
        ];
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'service_wh_order_order'          => 'DDD\Service\WHOrder\Order',
                'service_team_usages_procurement' => 'DDD\Service\Team\Usages\Procurement',
            ],
            'aliases'=> [
                'dao_wh_order_order'               => 'DDD\Dao\WHOrder\Order',
                'dao_finance_expense_expense_item' => 'DDD\Dao\Finance\Expense\ExpenseItem',
            ],
            'factories' => [
                'DDD\Service\WHOrder\Order' => function($sm){
                    $service = new \DDD\Service\WHOrder\Order();
                    $service->setServiceLocator($sm);
                    return $service;
                },
                'DDD\Dao\WHOrder\Order' => function($sm){
                    $dao = new \DDD\Dao\WHOrder\Order($sm);
                    return $dao;
                },
                'DDD\Dao\Finance\Expense\ExpenseItem' => function($sm){
                    $dao = new \DDD\Dao\Finance\Expense\ExpenseItem($sm);
                    return $dao;
                },
            ]
        ];
    }
}
