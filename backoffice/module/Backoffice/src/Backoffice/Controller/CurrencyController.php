<?php
namespace Backoffice\Controller;

use Library\Controller\ControllerBase;
//use core\domain\repositories\CurrencyRepository;
use Backoffice\Form\InputFilter\CurrencyFilter;
//use core\domain\entities\Currency;
use Backoffice\Form\CurrencyForm;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\Stdlib\Hydrator\ArraySerializable;
use Library\Utility\Helper;
use Library\Constants\TextConstants;
/**
 * CurrencyController
 * @package backoffice
 */
class CurrencyController extends ControllerBase
{
	/**
	 * Access point for currency controller
	 *
	 * @return array
	 */
    public function indexAction()
    {
    	$currencyService = $this->getServiceLocator()->get('service_currency_currency');
        $currencies = $currencyService->getCurrencyList();

    	return array(
    			'currencies' => $currencies
    	);
    }
    /**
     *
     * @return \Zend\Mvc\Controller\Plugin\Redirect|\Zend\View\Model\ViewModel
     */
    public function addAction()
    {
        $title = 'Add Currency';

    	// creating instance of form
    	$form = new CurrencyForm();

    	$form->get('submit')->setValue('Create Currency');

    	// creating instance of input filter
    	$inputFilter = new CurrencyFilter();

    	// set the input filter
    	$form->setInputFilter($inputFilter->getInputFilter());

    	// prepare the form
    	$form->prepare();

    	// check for posted data
    	$request = $this->getRequest();
    	if ($request->isPost()) {

    		// set posted data to form
    		$postData = $request->getPost();
    		$form->setData($postData);

    		// check for validation
    		if ($form->isValid()) {
                    // get validated data from form
                    $formData = $form->getData();
                    // save the currency
                    $currencyService = $this->getServiceLocator()->get('service_currency_currency');
                    $insertId = $currencyService->saveCurrecny($formData);
                    if($insertId > 0) {
                        Helper::setFlashMessage(['success'=>  TextConstants::SUCCESS_ADD]);
                        $this->redirect()->toRoute('backoffice/default', array('controller' => 'currency', 'action'=>'index'));
                    }
                    // Redirect to list of currencies
    		}else{
                    $form->populateValues($postData);
    		}
    	}

    	//Source code
    	$viewModel  = new ViewModel();
    	$viewModel->setVariables(array(
            'pageTitle' => $title,
            'form' => $form
    	));

        $viewModel->setTemplate('backoffice/currency/edit');
    	return $viewModel;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function editAction()
    {
        $title = 'Edit Currency';

    	// creating instance of form
    	$form = new CurrencyForm();

    	$form->get('submit')->setValue('Save Changes');

    	// creating instance of input filter
    	$inputFilter = new CurrencyFilter();

    	// set the input filter
    	$form->setInputFilter($inputFilter->getInputFilter());

    	// prepare the form
    	$form->prepare();

    	// currency service
    	$currencyService = $this->getServiceLocator()->get('service_currency_currency');
        $currencyId = $this->params("id", 0);
    	// check for posted data
    	$request = $this->getRequest();
    	if ($request->isPost()) {

            // set posted data to form
            $postData = $request->getPost();

            $form->setData($postData);

            // check for validation
            if ($form->isValid()) {
                // get validated data from form
                $formData = $form->getData();

                $currencyService->saveCurrecny($formData, $currencyId);
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                $this->redirect()->toRoute('backoffice/default', array('controller' => 'currency', 'action'=>'index'));
            } else {
                $form->populateValues($postData);
            }
    	} else {
            if ($currencyId){
                $currency = $currencyService->getCurrency($currencyId);
                if (!$currency) {
                    Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                    return $this->redirect()->toRoute('backoffice/default', ['controller' => 'currency']);
                }

                $currencyArray = [
                    'name'        => $currency->getName(),
                    'code'        => $currency->getCode(),
                    'symbol'      => $currency->getSymbol(),
                    'value'       => $currency->getValue(),
                    'auto_update' => $currency->getAutoUpdate(),
                    'gate'        => $currency->getGate(),
                    'visible'     => $currency->getVisible()
                ];
                $form->populateValues($currencyArray);

            } else {

            }
    	}
    	$viewModel  = new ViewModel();
    	$viewModel->setVariables(array(
            'form'      => $form,
            'pageTitle' => $title,
            'currencyId' => $currencyId
    	));
        $viewModel->setTemplate('backoffice/currency/edit');
    	return $viewModel;
    }

    public function getRangeJsonAction()
    {
        /**
         * @var \DDD\Service\Currency\CurrencyVault $currencyVaultService
         * @var \DDD\Dao\Task\Task $tasksDao
         * @var \DDD\Service\Task $taskService
         */
        $currencyVaultService = $this->getServiceLocator()->get('service_currency_currency_vault');
        $request = $this->params();

        $currencyId = $this->params("id", 0);
        $range = $request->fromQuery('range');
        $currentPage = ($request->fromQuery('start') / $request->fromQuery('length')) + 1;


        $responseArray = [
            'sEcho' => $request->fromQuery('sEcho'),
            'iTotalRecords' => 0,
            'iTotalDisplayRecords' => 0,
            'iDisplayStart' => ($currentPage - 1) * (integer)$request->fromQuery('start'),
            'iDisplayLength' => (integer)$request->fromQuery('length'),
            'aaData' => [],
        ];

        try {

            if ($currencyId && $range) {
                $results = $currencyVaultService->getCurrencyValuesInRange(
                    $currencyId,
                    $range,
                    (integer)$request->fromQuery('start'),
                    (integer)$request->fromQuery('length'),
                    $request->fromQuery('order')
                );

                $dates = explode(' - ', $request->fromQuery('range'));
                $dStart = new \DateTime($dates[0]);
                $dEnd = new \DateTime($dates[1]);
                $dDiff = $dStart->diff($dEnd);

                $count = $dDiff->days;

                $resultArray = [];
                foreach ($results as $row) {
                    $resultArray[] = [
                        date('M j, Y', strtotime($row['date'])),
                        $row['value']
                    ];
                }

                $responseArray['iTotalRecords'] = $count;
                $responseArray['iTotalDisplayRecords'] = $count;
                $responseArray['aaData'] = $resultArray;
            }
        } catch (\Exception $e) {
            // Do Nothing
        }

        return new JsonModel($responseArray);
    }
}
