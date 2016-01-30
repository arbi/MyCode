<?php
namespace Finance\Controller;

use Finance\Form\LegalEntitiesForm;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Library\Constants\TextConstants;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use DDD\Service\Finance\LegalEntities;

use Finance\Form\InputFilter\LegalEntitiesFilter;

/**
 * LegalEntitiesController
 * @package finance
 */
class LegalEntitiesController extends ControllerBase
{

    public function indexAction()
    {
        return new ViewModel();
    }

    public function ajaxLegalEntitiesListAction()
    {
	    /**
	     * @var LegalEntities $legalEntitiesService
	     */
	    $request = $this->params();
        $legalEntitiesService = $this->getServiceLocator()->get('service_finance_legal_entities');

        $results = $legalEntitiesService->getLegalEntitiesList(
            (integer)$request->fromQuery('iDisplayStart'),
            (integer)$request->fromQuery('iDisplayLength'),
            (integer)$request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '1')
        );

        $legalEntitiesCount = $legalEntitiesService->getLegalEntitiesCount($request->fromQuery('sSearch'), $request->fromQuery('all', '1'));
        foreach ($results as $row) {
            $status = $row->isActive() ? '<span class="label label-success">Active</span>'
						: '<span class="label label-default">Inactive</span>';
            $result[] = [
                $status,
                $row->getName(),
                $row->getCountry(),
                $row->getDescription(),
                '<a href="/finance/legal-entities/edit/'.$row->getId().'" class="btn btn-xs btn-primary" data-html-content="Edit"></a>',
            ];
        }

        if (!isset($result) || $result === null)
            $result[] = [' ', '', '', '', '', '', '', '', ''];

        $resultArray = [
            'sEcho'                => $request->fromQuery('sEcho'),
            'iTotalRecords'        => $legalEntitiesCount,
            'iTotalDisplayRecords' => $legalEntitiesCount,
            'iDisplayStart'        => $request->fromQuery('iDisplayStart'),
            'iDisplayLength'       => (integer)$request->fromQuery('iDisplayLength'),
            'aaData'               => $result,
        ];

        return new JsonModel($resultArray);
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function addAction()
    {
        $legalEntitiesService = $this->getServiceLocator()->get('service_finance_legal_entities');

    	// creating instance of form
    	$form = new LegalEntitiesForm('entities', $this->prepareCountryOptions());
    	$form->get('submit')->setValue('Add Legal Entity');
    	// creating instance of input filter
    	$inputFilter = new LegalEntitiesFilter();
        $hasNotUniqueName = 0;
    	// set the input filter
    	$form->setInputFilter($inputFilter->getInputFilter());

    	// prepare the form
    	$form->prepare();

        $legalEntityId = 0;
    	// check for posted data
    	$request = $this->getRequest();
    	if ($request->isPost()) {
            // set posted data to form
            $postData = $request->getPost();
            $isNameUnique = !$legalEntitiesService->checkName($request->getPost('id'), $request->getPost('name'), $request->getPost('country_id'));
            $form->setData($postData);
            if ($isNameUnique) {
                // check for validation
                if ($form->isValid()) {
                    // get validated data from form
                    $formData = $form->getData();

                    // unseting submit button, fieldsets, etc.
                    unset($formData["submit"]);

                    // save the legal entity
                    $formData['active'] = 1;
                    $legalEntitiesService->saveLegalEntity($formData, $legalEntityId);

                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                    $this->redirect()->toRoute('finance/legal-entities', array('controller' => 'legal-entities', 'action' => 'index'));
                } else {
                    $form->populateValues($postData);
                }
            } else {
                $hasNotUniqueName = 1;
            }
        }

    	$viewModel = new ViewModel();
    	$viewModel->setVariables(
            [
                'form'          => $form,
                'legalEntityId' => $legalEntityId,
                'pageTitle'     => 'Add Legal Entity',
                'hasNotUniqueName' => $hasNotUniqueName
    	   ]
        );
        $viewModel->setTemplate('finance/legal-entities/edit');
    	return $viewModel;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function editAction()
    {
        $legalEntitiesService = $this->getServiceLocator()->get('service_finance_legal_entities');

    	// creating instance of form
    	$form = new LegalEntitiesForm('entities', $this->prepareCountryOptions());

    	$form->get('submit')->setValue('Save Changes');
        $hasNotUniqueName = 0;
    	// creating instance of input filter
    	$inputFilter = new LegalEntitiesFilter();

    	// set the input filter
    	$form->setInputFilter($inputFilter->getInputFilter());

    	// prepare the form
    	$form->prepare();

        $legalEntityId = $this->params("id", 0);
    	// check for posted data
    	$request = $this->getRequest();
    	if ($request->isPost()) {

            // set posted data to form
            $postData = $request->getPost();
            $isNameUnique = !$legalEntitiesService->checkName($request->getPost('id'), $request->getPost('name'), $request->getPost('country_id'));
            $form->setData($postData);
            if ($isNameUnique) {
            // check for validation
            if ($form->isValid()) {
                // get validated data from form
                $formData = $form->getData();

                // unseting submit button, fieldsets, etc.
                unset($formData["submit"]);

                // save the legal entity
                $legalEntitiesService->saveLegalEntity($formData, $legalEntityId);

                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                $this->redirect()->toRoute('finance/legal-entities', array('controller' => 'legal-entities', 'action'=>'index'));
            } else {
                $form->populateValues($postData);
            }
        } else {
            $hasNotUniqueName = 1;
        }
    	} else {
            if ($legalEntityId) {
                $legalEntity = $legalEntitiesService->getLegalEntityById($legalEntityId);

                if (!$legalEntity) {
                    Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                    return $this->redirect()->toRoute('finance/legal-entities-activate');
                }

                $legalEntityData = [];

                $legalEntityData['id']          = $legalEntity->getId();
                $legalEntityData['name']        = $legalEntity->getName();
                $legalEntityData['description'] = $legalEntity->getDescription();
                $legalEntityData['active']      = $legalEntity->isActive();
                $legalEntityData['country_id']  = $legalEntity->getCountryId();

                $form->populateValues($legalEntityData);
            }
    	}
    	$viewModel  = new ViewModel();
    	$viewModel->setVariables(
            [
                'form'          => $form,
                'legalEntityId' => $legalEntityId,
                'isActive'      => isset($legalEntityData['active'])?$legalEntityData['active']:null,
                'pageTitle'     => 'Edit Legal Entity',
                'hasNotUniqueName' => $hasNotUniqueName
            ]
        );
        $viewModel->setTemplate('finance/legal-entities/edit');
    	return $viewModel;
    }

    public function activateAction()
    {
        $service       = $this->getServiceLocator()->get('service_finance_legal_entities');
        $legalEntityId = $this->params()->fromRoute('id', false);
        $status        = $this->params()->fromRoute('status', false);

        if ($legalEntityId) {
            $result      = $service->changeStatus($legalEntityId, $status);
            $successText = ($status) ? TextConstants::SUCCESS_ACTIVATE : TextConstants::SUCCESS_DEACTIVATE;

            if ($result) {
                Helper::setFlashMessage(['success' => $successText]);
            } else {
                Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
            }
        } else {
            Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
        }
        $this->redirect()->toRoute('finance/legal-entities', ['controller' => 'legal-entities']);
    }

    private function prepareCountryOptions()
    {
        $generalLocationService = $this->getServiceLocator()->get('service_location');

        // country options
        $countries = $generalLocationService->getAllActiveCountries();

        $countryOptions = ['-- Choose --'];

        foreach ($countries as $country) {
                $countryOptions[$country->getID()] = $country->getName();

        }

        return $countryOptions;
    }



    public function ajaxGetCountryAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $legalEntityId = (int)$request->getPost('id');
                $legalEntityService = $this->getServiceLocator()->get('service_finance_legal_entities');

                $legalEntityCountry = $legalEntityService->getCountryNameByLegalEntityId($legalEntityId);
                if ($legalEntityCountry) {
                    $result = [
                        'status' => 'success',
                        'country'    => $legalEntityCountry
                    ];
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

}
