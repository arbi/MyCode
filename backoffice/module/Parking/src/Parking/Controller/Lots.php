<?php

namespace Parking\Controller;

use Library\Constants\Roles;
use Parking\Controller\Base as ParkingBaseController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class Lots extends ParkingBaseController
{
	public function indexAction()
    {
        /**
         * @var $auth \Library\Authentication\BackofficeAuthenticationService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PARKING_MANAGEMENT)) {
            return $this->redirect()->toRoute('home');
        } else {
            return new ViewModel(['ajaxSourceUrl' => '/parking/lots/get-json']);
        }
	}

    public function getJsonAction()
    {
        /**
         * @var \DDD\Dao\Parking\General $parkingGeneralDao
         */
        $parkingGeneralDao = $this->getServiceLocator()->get('dao_parking_general');

        $request = $this->params();
        $currentPage = ($request->fromQuery('start') / $request->fromQuery('length')) + 1;

        $results = $parkingGeneralDao->getParkingLotsForDatatable(
            (integer)$request->fromQuery('start'),
            (integer)$request->fromQuery('length'),
            $request->fromQuery('order'),
            $request->fromQuery('search'),
            $request->fromQuery('all', '1')
        );

        $parkingLotsCount = $parkingGeneralDao->getParkingLotsCountForDatatable($request->fromQuery('search'), $request->fromQuery('all', '1'));
        $returnResult = [];

        foreach ($results as $row) {
            array_push($returnResult, [
                $row->isActive() ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Inactive</span>',
                $row->getName(),
                $row->getCity(),
                $row->getAddress(),
                $row->isVirtual() ? '<span class="glyphicon glyphicon-ok"></span>' : '',
                '<a href="/parking/' . $row->getId() . '/general" class="btn btn-xs btn-primary" target="_blank" data-html-content="View"></a>'
            ]);
        }

        $return = [
            'sEcho' => $request->fromQuery('sEcho'),
            'iTotalRecords' => $parkingLotsCount,
            'iTotalDisplayRecords' => $parkingLotsCount,
            'iDisplayStart' => ($currentPage - 1) * (integer)$request->fromQuery('start'),
            'iDisplayLength' => (integer)$request->fromQuery('length'),
            'aaData' => $returnResult,
        ];

        return new JsonModel($return);
    }
}
