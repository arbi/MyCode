<?php

namespace Finance\Controller;

use DDD\Domain\MoneyAccount\MoneyAccount;

use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;

use Finance\Form\Psp as PspForm;
use Finance\Form\InputFilter\PspFilter;

use Zend\Http\Request;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class PspController extends ControllerBase
{
    public function indexAction()
    {
	    return new ViewModel();
    }

    public function ajaxPspListAction()
    {
        /**
         * @var Request $request
         * @var BackofficeAuthenticationService $auth
         */
        $service = $this->getServiceLocator()->get('service_psp');
        $request = $this->params();

        $results = $service->pspList(
            (integer)$request->fromQuery('iDisplayStart'),
            (integer)$request->fromQuery('iDisplayLength'),
            (integer)$request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '1')
        );

        $result = $this->prepareData($results);

        $pspCount = $service->pspCount($request->fromQuery('sSearch'), $request->fromQuery('all', '1'));

        if (empty($result)) {
            array_push($result, [' ', '', '', '', '', '', '', '', '', '']);
        }

        $resultArray = [
            'sEcho' => $request->fromQuery('sEcho'),
            'iTotalRecords' => $pspCount,
            'iTotalDisplayRecords' => $pspCount,
            'iDisplayStart' => $request->fromQuery('iDisplayStart'),
            'iDisplayLength' => (integer)$request->fromQuery('iDisplayLength'),
            'aaData' => $result,
        ];

        return new JsonModel($resultArray);
    }

	public function editAction()
    {
		$service = $this->getServiceLocator()->get('service_psp');
		$request = $this->getRequest();
		$pspId = $this->params()->fromRoute('id', false);

		$form = new PspForm($this->getServiceLocator(), $pspId);
		$form->setInputFilter(new PspFilter());
		$form->prepare();

		if ($request->isPost()) {
			$postData = $request->getPost();
			$form->setData($postData);

			if ($form->isValid()) {
				if ($redirectId = $service->savePsp($postData, $pspId)) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);

                    if ($pspId) {
                        Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                    }

					$this->redirect()->toRoute('finance/psp', [
                        'controller' => 'psp',
                        'action' 	 => 'edit',
                        'id' 		 => $redirectId,
                    ]);

				} else {
					Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
				}
			} else {
				Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
			}

			$form->populateValues($postData);
		} else {
			if ($pspId) {
				$pspData = $service->getPspData($pspId);

                if (!$pspData) {
                    Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                    return $this->redirect()->toRoute('finance/psp');
                }

			    $form->populateValues($pspData);
			}
		}

		return new ViewModel([
			'form' => $form,
			'id' => $pspId,
			'status' => (isset($pspData) ? (int)$pspData['active'] : false)
		]);
	}

	/**
	 * Activate and Deactivate PSP
	 */
	public function activateAction() {
        $service = $this->getServiceLocator()->get('service_psp');
        $pspId   = $this->params()->fromRoute('id', false);
        $status  = (int)$this->params()->fromQuery('status', null);
        $status  = !is_null($status) ? (int)$status : null;

        if (   $pspId
        	&& !is_null($status)
            && is_int($status)
        ) {
            $result = $service->changeStatus($pspId, $status);
            $successText = ($status) ? TextConstants::SUCCESS_ACTIVATE : TextConstants::SUCCESS_DEACTIVATE;

            if ($result) {
                Helper::setFlashMessage(['success' => $successText]);
            } else {
                Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
            }
        } else {
            Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
        }

        $this->redirect()->toRoute('finance/psp', ['controller' => 'psp']);
	}

	/**
	 * @param \Zend\Db\ResultSet\ResultSet|MoneyAccount[]|\ArrayObject $pspList
	 * @return array
	 */
	private function prepareData($pspList) {
		$data = [];

		if ($pspList->count()) {
			foreach ($pspList as $psp) {
				$router = $this->getEvent()->getRouter();
				$editUrl = $router->assemble([
					'controller' => 'psp',
					'action' => 'edit',
					'id' => $psp->getId(),
					], ['name' => 'finance/psp']
				);
				$status = (
					$psp->getActive()
						? '<span class="label label-success">Active</span>'
						: '<span class="label label-default">Inactive</span>'
				);

				array_push($data, [
					'<div class="text-center">'.$status.'</div>',
					$psp->getShortName(),
					$psp->getName(),
					$psp->getMoneyAccountName(),
					$psp->getBatch(),
					'<a class="btn btn-xs btn-primary" href="' . $editUrl . '" data-html-content="Edit"></a>'
				]);
			}
		}

		return $data;
	}
}
