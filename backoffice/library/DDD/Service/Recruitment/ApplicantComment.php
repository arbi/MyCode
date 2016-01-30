<?php

namespace DDD\Service\Recruitment;

use DDD\Service\ServiceBase;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Roles;

final class ApplicantComment extends ServiceBase
{
    /**
     * @param $applicantId
     * @param $isLoggedInUserHiringManager bool
     * @return \DDD\Domain\Recruitment\Applicant\ApplicantComment[]
     */
    public function getApplicantCommentsById($applicantId, $isLoggedInUserHiringManager)
    {
        /**
         * @var BackofficeAuthenticationService $authenticationService
         * @var \DDD\Dao\Recruitment\Applicant\ApplicantComment $applicantCommentDao
         */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $applicantCommentDao = $this->getServiceLocator()->get('dao_recruitment_applicant_applicant_comment');

        $loggedInUserId = $authenticationService->getIdentity()->id;

        $comments = null;
        $hrOnlyCommentsIncluded = false;

        if ($authenticationService->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR)) {
            $hrOnlyCommentsIncluded = true;
        }

        if ($authenticationService->hasRole(Roles::ROLE_APPLICANT_MANAGEMENT || $isLoggedInUserHiringManager) ) {
            $comments = $applicantCommentDao->getApplicantCommentsById($applicantId, null, $hrOnlyCommentsIncluded);
        } else {
            $comments = $applicantCommentDao->getApplicantCommentsById($applicantId, $loggedInUserId, $hrOnlyCommentsIncluded);
        }

        return $comments;
    }

    /**
     * @param array $data
     * @return int
     */
    public function save($data)
    {
        /**
         * @var \DDD\Dao\Recruitment\Applicant\ApplicantComment $applicantCommentDao
         */
        $applicantCommentDao = $this->getServiceLocator()->get('dao_recruitment_applicant_applicant_comment');
        return $applicantCommentDao->save($data);
    }
}
