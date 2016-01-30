<?php

namespace CreditCard\Controller;

use CreditCard\Entity\CompleteData;
use CreditCard\Service\Retrieve as RetrieveService;
use CreditCard\Service\Store as StoreService;
use Library\Controller\ControllerBase;
use Zend\View\Model\ViewModel;

/**
 * Class TestController
 * @package CreditCard\Controller
 */
class TestController extends ControllerBase
{
    public function indexAction()
    {

    }

    public function getCardAction()
    {
        $ccId = $this->params()->fromRoute('cc_id', 0);

        /**
         * @var RetrieveService $retrieveService
         */
        $retrieveService = $this->getServiceLocator()->get('service_retrieve_cc');

        $cc = $retrieveService->getCreditCard($ccId);

        echo('<pre>');
        var_dump($cc);
        echo('</pre>');

        die;
    }

    public function storeCardAction()
    {
        /**
         * @var StoreService $storeService
         */
        $storeService = $this->getServiceLocator()->get('service_store_cc');

        $ccRawData = new CompleteData();
        $ccRawData->setPan('4111111111111111');
        $ccRawData->setExpirationMonth('01');
        $ccRawData->setExpirationYear('16');
        $ccRawData->setSecurityCode('123');
        $ccRawData->setHolder('Some VISA dddddddd Holder');
        $ccRawData->setCustomerId(1);
        $ccRawData->setPartnerId(0);
        $ccRawData->setBrand(1);
        $ccRawData->setSource(0);
        $ccRawData->setStatus(0);

        $token = $storeService->store($ccRawData);

        echo $token;
        
	$ccRawData = new CompleteData();
        $ccRawData->setPan('378282246310005');
        $ccRawData->setExpirationMonth('01');
        $ccRawData->setExpirationYear('16');
        $ccRawData->setSecurityCode('123');
        $ccRawData->setHolder('Some AMEX dddddddddddddddddddddd Holder');
        $ccRawData->setBrand(2);

        $token = $storeService->store($ccRawData);

        echo $token;

        $ccRawData = new CompleteData();
        $ccRawData->setPan('30569309025904');
        $ccRawData->setExpirationMonth('01');
        $ccRawData->setExpirationYear('16');
        $ccRawData->setSecurityCode('123');
        $ccRawData->setHolder('Some DINNERS CLUBddddddddddddddddddddddddddddddd Holder');
        $ccRawData->setBrand(2);

        $token = $storeService->store($ccRawData);

        echo $token;

        die;
    }
}
