<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Dao\User\Evaluation\Evaluations as EvaluationsDAO;
use DDD\Service\ServiceBase;
use DDD\Service\User\Evaluations as EvaluationService;
use DDD\Service\User as UserService;

use Library\Authentication\BackofficeAuthenticationService;

/**
 * Methods to work with "UpcomingEvaluations" widget
 *
 * @author Tigran Petrosyan
 */
final class UpcomingEvaluations extends ServiceBase
{
    /**
     * @return \DDD\Domain\UniversalDashboard\Widget\UpcomingEvaluations[]
     */
    public function getUpcomingEvaluations()
	{
        /**
         * @var EvaluationsDAO $evaluationsDao
         * @var BackofficeAuthenticationService $authenticationService
         */
        $evaluationsDao        = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $loggedInUserId        = $authenticationService->getIdentity()->id;

        $results = $evaluationsDao->getUpcomingEvaluations($loggedInUserId);

		return $results;
	}

    /**
     * @return int
     */
    public function getUpcomingEvaluationsCount()
	{
        /**
         * @var EvaluationsDAO $evaluationsDao
         * @var BackofficeAuthenticationService $authenticationService
         */
        $evaluationsDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $loggedInUserId = $authenticationService->getIdentity()->id;

        $count = $evaluationsDao->getUpcomingEvaluationsCount($loggedInUserId);

		return $count;
	}

    /**
     * @param $evaluationId
     * @return bool
     */
    public function cancelPlannedEvaluation($evaluationId)
    {
        /**
         * @var EvaluationService $evaluationService
         */
        $evaluationService = $this->getServiceLocator()->get('service_user_evaluations');
        $result = $evaluationService->cancelEvaluation($evaluationId);

        return $result;
    }
}