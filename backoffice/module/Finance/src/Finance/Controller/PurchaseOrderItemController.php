<?php

namespace Finance\Controller;

use DDD\Dao\Finance\Expense\ExpenseItem;
use DDD\Service\Currency;
use DDD\Service\Finance\Expense\ExpenseTicket;
use DDD\Service\User;
use Library\Controller\ControllerBase;

use Finance\Form\SearchItemForm;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Library\Utility\CsvGenerator;



class PurchaseOrderItemController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var User $usersService
         */
        $usersService = $this->getServiceLocator()->get('service_user');
        $users = $usersService->getPeopleListAsArray(false, false);
        $form = new SearchItemForm('search-item-form', $users);
        $form->prepare();
        return new ViewModel([
            'form' => $form
        ]);
    }

    public function getDatatableDataAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $queryParams    = $this->params()->fromQuery();
        $iDisplayStart  = $queryParams["iDisplayStart"];
        $iDisplayLength = $queryParams["iDisplayLength"];
        $sortCol        = (int)$queryParams['iSortCol_0'];
        $sortDir        = $queryParams['sSortDir_0'];

        $result = $expenseService->getItemsForDataTable(
            $iDisplayStart,
            $iDisplayLength,
            $queryParams,
            $sortCol,
            $sortDir
        );

        $responseArray = [
            'iTotalRecords'        => $result['count'],
            'iTotalDisplayRecords' => $result['count'],
            'iDisplayStart'        => $iDisplayStart,
            'iDisplayLength'       => (int)$iDisplayLength,
            "aaData"               => $result['result']
        ];
        return new JsonModel(
            $responseArray
        );
    }

    public function validateDownloadCsvAction()
    {
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::ERROR,
        ];

        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');

        try {
            $isOk = $expenseService->validateDownloadCsvItem($this->params()->fromQuery());
            if (!$isOk) {
                $result['msg'] = TextConstants::DOWNLOAD_ERROR_CSV;
            } else {
                $result['status'] = 'success';
            }
        } catch (\Exception $ex) {
        }

        return new JsonModel($result);
    }


    public function downloadCsvAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');

        try {
            $data = $expenseService->getCsvArrayForDownload($this->params()->fromQuery());
        } catch (\Exception $ex) {
            $flash = Helper::getSessionContainer('use_zf2');
            $flash->flash = ['error' => $ex->getMessage()];

            return $this->redirect()->toRoute('finance/purchase-order');
        }

        $response = $this->getResponse();
        $headers  = $response->getHeaders();

        $filename = 'Purchase Order Items ' . date('Y-m-d') . '.csv';

        $utilityCsvGenerator = new CsvGenerator();
        $utilityCsvGenerator->setDownloadHeaders($headers, $filename);

        $csv = $utilityCsvGenerator->generateCsv($data);

        $response->setContent($csv);

        return $response;
    }

    /**
     * Delete Item Attachment
     */
    public function ajaxDeleteAttachmentAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $attachmentId = $request->getPost('attachmentId');
                /**
                 * @var \DDD\Service\Finance\Expense\ExpenseTicket $expenseTicketService
                 * @var \DDD\Dao\Finance\Expense\ExpenseItem $expenseItemDao
                 * @var \DDD\Dao\Finance\Expense\ExpenseItemAttachments $expenseItemAttachmentsDao
                 */
                $expenseTicketService      = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
                $expenseItemAttachmentsDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_attachments');

                $attachmentUrl = $expenseTicketService->getItemAttachmentPathById($attachmentId);
                $expenseItemAttachmentsDao->delete(['id' => $attachmentId]);

                @unlink($attachmentUrl);

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_DELETE,
                ];

                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ITEM_ATTACHMENT_DELETE]);
            } catch (\Exception $ex) {
                $result['msg'] = TextConstants::SERVER_ERROR;
            }
        } else {
            $result['msg'] = TextConstants::BAD_REQUEST;
        }

        return new JsonModel($result);
    }
}
