<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Zend\View\Model\ViewModel;
use Library\Constants\Roles;
use Library\ActionLogger\Logger;
use Library\Constants\TextConstants;
use Library\Constants\Constants;

class History extends ApartmentBaseController
{
    public function indexAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_APARTMENT_MANAGEMENT)) {
            return $this->redirect()->toRoute(
                    'apartment',
                    ['apartment_id' => $this->apartmentId]
            );
        }

        /**
         * @var \DDD\Service\Apartment\Logs $apartmentLogsService
         */
        $apartmentLogsService = $this->getServiceLocator()->get('service_apartment_logs');
        $apartmentLogs = $apartmentLogsService->getApartmentLogs($this->apartmentId);

        if (count($apartmentLogs) > 0) {
            foreach ($apartmentLogs as $log) {
                $rowClass = '';
                if (   $log['user_name']
                    == TextConstants::SYSTEM_USER
                ) {
                    $rowClass = "warning";
                }

                $apartmentLogsArray[] = [
                    date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($log['timestamp'])),
                    $log['user_name'],
                    $this->identifyApartmentModule($log['module_id']),
                    $log['value'],
                    "DT_RowClass" => $rowClass
                ];
            }
        } else {
            $apartmentLogsArray = [];
        }

		return new ViewModel([
            'aaData' => json_encode($apartmentLogsArray),
            'apartmentId' => $this->apartmentId,
            'apartmentStatus' => $this->apartmentStatus
		]);
    }


    private function identifyApartmentModule($moduleId)
    {
        $apartmentModules = [
            Logger::MODULE_APARTMENT_GENERAL => 'General',
            Logger::MODULE_APARTMENT_DETAILS => 'Details',
            Logger::MODULE_APARTMENT_LOCATION => 'Location',
            Logger::MODULE_APARTMENT_MEDIA => 'Media',
            Logger::MODULE_APARTMENT_DOCUMENTS => 'Documents',
            Logger::MODULE_APARTMENT_RATES => 'Rates',
            Logger::MODULE_APARTMENT_CALENDAR => 'Calendar',
            Logger::MODULE_APARTMENT_INVENTORY => 'Inventory',
            Logger::MODULE_APARTMENT_CONNECTION => 'Connection',
            Logger::MODULE_APARTMENT_REVIEW => 'Reviews',
        ];

        if (isset($apartmentModules[$moduleId])) {
            return $apartmentModules[$moduleId];
        }

        return 'not defined';
    }
}
