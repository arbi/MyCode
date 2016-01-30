<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Dao\User\Evaluation\Evaluations as EvaluationsDAO;
use DDD\Dao\User\UserManager;
use DDD\Service\ServiceBase;
use DDD\Service\User\Evaluations as EvaluationService;
use Library\Authentication\BackofficeAuthenticationService;

/**
 * Methods to work with "EvaluationLessEmployees" widget
 *
 * @author Tigran Petrosyan
 */
final class EvaluationLessEmployees extends ServiceBase
{
    /**
     * @return \DDD\Domain\User\User[]
     */
    public function getEvaluationLessEmployees()
	{
        /**
         * @var UserManager $userManagerDao
         * @var BackofficeAuthenticationService $authenticationService
         */
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $loggedInUserId = $authenticationService->getIdentity()->id;

        $result = $userManagerDao->getEvaluationLessEmployees($loggedInUserId);

        return $result;
	}

    /**
     * @return int
     */
    public function getEvaluationLessEmployeesCount()
	{
        /**
         * @var UserManager $userManagerDao
         * @var BackofficeAuthenticationService $authenticationService
         */
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $loggedInUserId = $authenticationService->getIdentity()->id;

        $count = $userManagerDao->getEvaluationLessEmployeesCount($loggedInUserId);

		return $count;
	}
}