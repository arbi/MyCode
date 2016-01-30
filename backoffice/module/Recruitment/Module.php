<?php

namespace Recruitment;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;

class Module implements AutoloaderProviderInterface
{

	public function getAutoloaderConfig()
    {
		return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace ( '\\', '/', __NAMESPACE__ ),
                    'Library' => __DIR__ . '/../../library/Library',
                    'DDD' => __DIR__ . '/../../library/DDD'
                ]
            ]
		];
	}

	public function getViewHelperConfig()
    {
		return array (
		);
	}

	public function getConfig()
    {
		return include __DIR__ . '/config/module.config.php';
	}

    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'service_recruitment_job'               => 'DDD\Service\Recruitment\Job',
                'service_recruitment_applicant'         => 'DDD\Service\Recruitment\Applicant',
                'service_recruitment_applicant_comment' => 'DDD\Service\Recruitment\ApplicantComment',
                'service_recruitment_interview'         => 'DDD\Service\Recruitment\Interview',
            ],
            'factories' => [
                'DDD\Dao\Recruitment\Job\Job' =>  function($sm) {
                    return new \DDD\Dao\Recruitment\Job\Job($sm);
                },
                'DDD\Dao\Recruitment\Applicant\Applicant' =>  function($sm) {
                    return new \DDD\Dao\Recruitment\Applicant\Applicant($sm);
                },
                'DDD\Dao\Recruitment\Applicant\ApplicantComment' =>  function($sm) {
                    return new \DDD\Dao\Recruitment\Applicant\ApplicantComment($sm);
                },
                'DDD\Dao\Recruitment\Interview\Interview' =>  function($sm) {
                    return new \DDD\Dao\Recruitment\Interview\Interview($sm);
                },
                'DDD\Dao\Recruitment\Interview\InterviewParticipant' =>  function($sm) {
                    return new \DDD\Dao\Recruitment\Interview\InterviewParticipant($sm);
                },
            ],
            'aliases'=> [
                'dao_recruitment_job_job'                         => 'DDD\Dao\Recruitment\Job\Job',
                'dao_recruitment_applicant_applicant'             => 'DDD\Dao\Recruitment\Applicant\Applicant',
                'dao_recruitment_applicant_applicant_comment'     => 'DDD\Dao\Recruitment\Applicant\ApplicantComment',
                'dao_recruitment_interview_interview'             => 'DDD\Dao\Recruitment\Interview\Interview',
                'dao_recruitment_interview_interview_participant' => 'DDD\Dao\Recruitment\Interview\InterviewParticipant',
            ],
        ];
    }
}
