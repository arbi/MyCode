<?php

namespace Apartel\Controller;

use Apartel\Controller\Base as ApartelBaseController;
use Library\ActionLogger\Logger;
use Library\Utility\Helper;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

/**
 * Class Inventory
 * @package Apartel\Controller
 */
class History extends ApartelBaseController
{
    public function indexAction()
    {
        try {
            if ($this->apartelId > 0) {
                /** @var \DDD\Dao\Apartel\Details $apartelDetailsDao */
                $apartelDetailsDao  = $this->getServiceLocator()->get('dao_apartel_details');
                $apartelCurrentData = $apartelDetailsDao->getApartelDetailsById($this->apartelId);

                /** @var Logger $logger */
                $logger = $this->getServiceLocator()->get('ActionLogger');
                $logger->setOutputFormat(Logger::OUTPUT_HTML);
                $apartelLogsArray = $logger->getDatatableData(
                    Logger::MODULE_APARTEL,
                    $this->apartelId
                );

                return new ViewModel([
                    'historyAaData' => $apartelLogsArray,
                    'apartelId' => $this->apartelId,
                    'apartelName' => $apartelCurrentData->getName()
                ]);
            } else {
                throw new \Exception('Apartel not found');
            }
        } catch (\Exception $e) {
            Helper::setFlashMessage(['error' => $e->getMessage()]);
            return $this->redirect()->toUrl('/');
        }
	}
}
