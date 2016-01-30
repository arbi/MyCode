<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;

use DDD\Dao\Finance\Expense\ExpenseCost;

use Library\Constants\Constants;
use Library\Utility\CsvGenerator;
use Library\Utility\Helper;
use Library\Constants\Roles;
use Library\Constants\TextConstants;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class Cost extends ApartmentBaseController
{
    /**
     * @property int apartmentId
     * @return array|ViewModel
     */
    public function indexAction()
    {
        $apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
        $currency                = $apartmentGeneralService->getCurrencySymbol($this->apartmentId);

        return new ViewModel([
            'apartmentId'     => $this->apartmentId,
            'apartmentStatus' => $this->apartmentStatus,
            'currency'        => $currency,
        ]);
    }

    public function downloadCsvAction()
    {
        $apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
        $currency                = $apartmentGeneralService->getCurrencySymbol($this->apartmentId);
        $expenseDao              = new ExpenseCost($this->getServiceLocator(), '\ArrayObject');
        $costs                   = $expenseDao->getApartmentCosts($this->apartmentId);
        $costArray               = [];

		foreach ($costs as $cost) {
			$costArray [] = [
                "ID"                 => $cost['id'],
                "Category"           => $cost['category'],
                "Date"               => date(Constants::GLOBAL_DATE_FORMAT, strtotime($cost['date'])),
                "Amount ($currency)" => $cost['amount'],
                "Purpose"            => $cost['purpose']
			];
		}

        if (!empty($costArray)) {
            $response = $this->getResponse();
            $headers  = $response->getHeaders();

            $utilityCsvGenerator = new CsvGenerator();
            $filename            = 'costs_apartment_' . str_replace(' ', '_', date('Y-m-d')) . '.csv';
            $utilityCsvGenerator->setDownloadHeaders($headers, $filename);

            $csv = $utilityCsvGenerator->generateCsv($costArray);
            $response->setContent($csv);

            return $response;
        } else {
            $flash_session        = Helper::getSessionContainer('use_zf2');
            $flash_session->flash = ['notice' => 'There are empty data, nothing to download.'];

            $url = $this->getRequest()->getHeader('Referer')->getUri();
            $this->redirect()->toUrl($url);
        }
    }

    public function ajaxGetCostsAction()
    {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $requestParams = $this->getRequest()->getPost();

            /**
             * @var \DDD\Service\Finance\Expense\ExpenseCosts $expenseService
             */
            $expenseService = $this->getServiceLocator()->get('service_finance_expense_expenses_cost');

            $costs = $expenseService->getDatatableData(
                $this->apartmentId,
                $requestParams['iDisplayStart'],
                $requestParams['iDisplayLength'],
                $requestParams['iSortCol_0'],
                $requestParams['sSortDir_0'],
                $requestParams['sSearch']
            );

            $result = [
                'iTotalRecords'         => $costs['total'],
                'iTotalDisplayRecords'  => $costs['total'],
                'iDisplayStart'         => $requestParams['iDisplayStart'],
                'iDisplayLength'        => $requestParams['iDisplayLength'],
                'aaData'                => $costs['data']
            ];
        } catch (\Exception $e) {
            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }
}
