<?php

namespace Apartel\Controller;

use Apartel\Controller\Base as ApartelBaseController;
use Apartel\Form\Type as TypeForm;
use Apartel\Form\InputFilter\Type as TypeFilter;
use Apartel\Form\Rate as RateForm;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Zend\Http\Request;
use Zend\View\Model\ViewModel;


class TypeRate extends ApartelBaseController
{
    public function homeAction()
    {
        /**
         * @var \DDD\Service\Apartel\Type $typeService
         */
        $typeService = $this->getServiceLocator()->get('service_apartel_type');
        $typesRates  = $typeService->getApartelTypesWithRates($this->apartelId, true);

        return [
            'typeRates' => $typesRates
        ];
    }

    public function indexAction()
    {
        try {
            /**
             * @var \DDD\Service\Apartel\Type $typeService
             * @var Request $request
             */
            $typeId = $this->params ()->fromRoute ('type_id', 0);
            $typeService = $this->getServiceLocator()->get('service_apartel_type');
            $typesRates = $typeService->getApartelTypesWithRates($this->apartelId, true);
            $request = $this->getRequest();

            // set Type From
            $typeDetails = $typeService->getTypeDetails($this->apartelId, $typeId);
            $form = new TypeForm($typeDetails['all_apartment_list']);
            $form->prepare();

            // edit mode
            if ($typeId) {
                $form->populateValues($typeDetails);
            }

            // submit data
            if ($request->isPost()) {
                $form->setInputFilter(new TypeFilter());
                $filter = $form->getInputFilter();
                $form->setInputFilter($filter);
                $data = $request->getPost();
                $form->setData($data);

                if ($form->isValid()) {
                    $vData = $form->getData();
                    $flash = ['success' => $typeId ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD ];
                    $responseTypeId = $typeService->saveType($vData, $this->apartelId);
                    Helper::setFlashMessage($flash);
                    return $this->redirect()->toRoute('apartel/type-rate/type', [
                        'type_id' => $responseTypeId, 'apartel_id' => $this->apartelId], [], true);
                } else {
                    $messages = Helper::parsFormInvalidMessages($form->getMessages());
                    $flash = ['error' => $messages ];
                    Helper::setFlashMessage($flash);
                }
            }

            return [
                'typeRates' => $typesRates,
                'form' => $form,
                'typeId' => $typeId,
            ];
        } catch (\Exception $e) {
            Helper::setFlashMessage(['error'=> TextConstants::ERROR ]);
            return $this->redirect()->toRoute('apartel/type-rate/home', ['apartel_id' => $this->apartelId], [], true);
        }
    }

    public function deleteAction()
    {
        try {
            /**
             * @var \DDD\Service\Apartel\Type $typeService
             * @var Request $request
             */
            $typeId = $this->params ()->fromRoute ('type_id', 0);
            $typeService = $this->getServiceLocator()->get('service_apartel_type');
            $deleteResponse = $typeService->deleteType($typeId);
            $flesh = [$deleteResponse['status'] => $deleteResponse['msg']];
        } catch (\Exception $e) {
            $flesh = ['error'=> TextConstants::ERROR ];
        }

        Helper::setFlashMessage($flesh);
        return $this->redirect()->toRoute('apartel/type-rate/home', ['apartel_id' => $this->apartelId], [], true);
    }

    public function rateAction()
    {
        try {
            /**
             * @var \DDD\Service\Apartel\Rate $rateService
             * @var \DDD\Service\Apartel\Type $typeService
             * @var \DDD\Service\Apartel\Inventory $inventoryService
             * @var Request $request
             */
            $typeId      = $this->params()->fromRoute('type_id', 0);
            $rateId      = $this->params()->fromRoute('rate_id', 0);
            $rateService = $this->getServiceLocator()->get('service_apartel_rate');
            $typeService = $this->getServiceLocator()->get('service_apartel_type');
            $request     = $this->getRequest();
            $typesRates  = $typeService->getApartelTypesWithRates($this->apartelId, true);

            // set Type From
            $options = $rateService->getDetailsForForm($typeId, $rateId, $this->apartelId);
            $form    = new RateForm($options);
            $form->prepare();

            // edit mode
            $viewPriceData = [];
            if ($rateId) {
                $rateDetails = $rateService->getRateDetails($rateId);
                $form->populateValues($rateDetails['formValue']);
                $viewPriceData = $rateDetails['viewPriceData'];
            }

            // submit data
            if ($request->isPost()) {
                $data = $request->getPost();
                $data = $data->toArray();
                $response = $rateService->saveRate($data, $this->apartelId);

                $flash = [$response['status'] => $response['msg']];
                Helper::setFlashMessage($flash);

                return $this->redirect()->toRoute('apartel/type-rate/type/rate', [
                    'rate_id' => $response['rate_id'], 'type_id' => $response['type_id'], 'apartel_id' => $this->apartelId], [], true);
            }

            return [
                'typeRates'     => $typesRates,
                'form'          => $form,
                'typeId'        => $typeId,
                'rateId'        => $rateId,
                'currency'      => $options['currency'],
                'isParent'      => $options['is_parent'],
                'parentPrices'  => $options['parentPrices'],
                'viewPriceData' => $viewPriceData,
            ];
        } catch (\Exception $e) {
            Helper::setFlashMessage(['error'=> $e->getMessage()]);
            return $this->redirect()->toRoute('apartel/type-rate/home', ['apartel_id' => $this->apartelId], [], true);
        }
    }

    public function rateDeleteAction()
    {
        try {
            /**
             * @var \DDD\Service\Apartel\Rate $rateService
             * @var Request $request
             */
            $rateId         = $this->params()->fromRoute('rate_id', 0);
            $rateService    = $this->getServiceLocator()->get('service_apartel_rate');
            $deleteResponse = $rateService->deleteRate($rateId);
            $flesh          = [$deleteResponse['status'] => $deleteResponse['msg']];
        } catch (\Exception $e) {
            $flesh = ['error'=> TextConstants::ERROR ];
        }

        Helper::setFlashMessage($flesh);
        return $this->redirect()->toRoute('apartel/type-rate/home', ['apartel_id' => $this->apartelId], [], true);
    }
}
