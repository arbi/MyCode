<?php

namespace Venue\Controller;

use Venue\Form\InputFilter\VenueChargeFilter;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Library\Controller\ControllerBase;
use Venue\Form\VenueChargeForm;
use Library\Constants\TextConstants;
use Library\Utility\Helper;

class Charges extends ControllerBase
{
    public function addAction()
    {
        try {
            $venueId = $this->params()->fromRoute('id');

            if (is_null($venueId)) {
                throw new \Exception('Venue id is required');
            }

            /**
             * @var \DDD\Service\Venue\Venue $venueService
             */
            $venueService = $this->getServiceLocator()->get('service_venue_venue');
            $venueData = $venueService->getVenueById($venueId);

            if ($venueData === false) {
                throw new \Exception('It is impossible to create a charge for a non-existent venue');
            }

            $form = $this->getForm($venueId);


            $viewData = [
                'form'      => $form,
                'venueData' => $venueData
            ];

            $viewModel = new ViewModel($viewData);
            $viewModel->setTemplate('venue/charges/item.phtml');

            return $viewModel;
        } catch (\Exception $e) {
            return $this->redirect()->toUrl('/');
        }
    }

    public function editAction()
    {
        try {
            $chargeId = $this->params()->fromRoute('id');

            if (is_null($chargeId)) {
                throw new \Exception('Venue charge id is required');
            }

            /**
             * @var \DDD\Dao\Venue\Charges $venueChargeDao
             * @var \DDD\Service\Venue\Venue $venueService
             */

            $venueChargeDao = $this->getServiceLocator()->get('dao_venue_charges');
            $venueService = $this->getServiceLocator()->get('service_venue_venue');
            $chargeData = $venueChargeDao->getChargeById($chargeId);
            $itemData  = $venueService->getItemsByChargeId($chargeId);

            $form = $this->getForm($chargeData->getVenueId(), $chargeData);

            $viewData = [
                'form'          => $form,
                'chargeData'    => $chargeData,
                'itemData'      => $itemData
            ];

            $viewModel = new ViewModel($viewData);
            $viewModel->setTemplate('venue/charges/item.phtml');

            return $viewModel;
        } catch (\Exception $e) {
            return $this->redirect()->toUrl('/');
        }
    }

    public function ajaxSaveAction()
    {
        $result = [
            'status'    => 'error',
            'msg'       => TextConstants::SERVER_ERROR
        ];

        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception(TextConstants::AJAX_ONLY_POST_ERROR);
            }

            $postData = $this->params()->fromPost();

            $venueId = (isset($postData['venue_id'])) ? $postData['venue_id'] : 0;

            /**
             * @var \DDD\Dao\Venue\Venue $venueDao
             */
            $venueDao = $this->getServiceLocator()->get('dao_venue_venue');
            $venueData = $venueDao->getVenueById($venueId);

            if ($venueData === false) {
                throw new \Exception('It is impossible to create a charge for a non-existent venue');
            }

            /**
             * @var \DDD\Service\Venue\Charges $venueChargeService
             */
            $venueChargeService = $this->getServiceLocator()->get('service_venue_charges');

            $form = $this->getForm($venueId);
            $form->setData($postData);

            $form->setInputFilter(new VenueChargeFilter());

            if ($form->isValid()) {

                if (isset($postData['id']) && $postData['id'] > 0) {
                    $dbResult = $venueChargeService->saveCharge($postData);
                    $chargeId = $postData['id'];
                } else {
                    $dbResult = $venueChargeService->createCharge($postData);
                    $chargeId = $dbResult;
                }

                if ($dbResult !== false) {
                    $successMessage = (isset($postData['id']) && $postData['id'] > 0)
                        ? TextConstants::SUCCESS_UPDATE
                        : TextConstants::SUCCESS_ADD;

                    Helper::setFlashMessage([
                        'success' => $successMessage,
                    ]);

                    $result = [
                        'status'    => 'success',
                        'msg'       => $successMessage,
                        'url'       => $this->url()->fromRoute('venue-charges', [
                            'action' => 'edit',
                            'id' => $chargeId
                        ])
                    ];
                }
            } else {
                $result['msg'] = $this->parseFormMessage($form);
            }

        } catch (\Exception $e) {
            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }

    private function getForm($venueId = 0, $data = [])
    {
        /**
         * @var \DDD\Service\User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        $usersList = $userService->getPeopleList();

        $form = new VenueChargeForm(
            ['users_list' => $usersList],
            $data
        );

        $form->get('venue_id')->setValue($venueId);

        return $form;
    }

    /**
     * @param \Zend\Form\Form $form
     * @return bool|string
     */
    private function parseFormMessage($form)
    {
        if (is_array($form->getMessages())) {
            $errorMessage = '';

            foreach ($form->getMessages() as $title => $values) {
                $formElement = $form->getElements();
                $errorMessage .= $formElement[$title]->getLabel() . PHP_EOL;

                if (is_array($values)) {
                    foreach ($values as $value) {
                        $errorMessage .= '<li>' . $value . '</li>';
                    }
                } else {
                    $errorMessage .= $values;
                }
            }

            return $errorMessage;
        } elseif(is_string($form->getMessages())) {
            return $form->getMessages();
        } else {
            return false;
        }
    }
}
