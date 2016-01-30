<?php
namespace Console\Controller;

use DDD\Service\Queue\EmailQueue;
use DDD\Service\Recruitment\Applicant;
use Library\Constants\EmailAliases;
use Library\Controller\ConsoleBase;
use Library\Utility\Helper;
use Zend\Validator\EmailAddress;

/**
 * Class EmailController
 * @package Console\Controller
 */
class EmailController extends ConsoleBase
{
    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', false);

        switch ($action) {
            case 'send-applicant-rejections': $this->sendApplicantRejectionsAction();
                break;
            default :
                echo '- type correct option ( send-applicant-rejection )'.PHP_EOL;
                return false;
        }
    }

    public function sendApplicantRejectionsAction()
    {
        /**
         * @var \DDD\Service\Queue\EmailQueue $emailQueueService
         * @var \Mailer\Service\Email $mailer
         */
        $emailQueueService = $this->getServiceLocator()->get('service_queue_email_queue');
        $list = $emailQueueService->fetch(EmailQueue::TYPE_APPLICANT_REJECTION);

        if ($list && $list->count()) {
            /**
             * @var \DDD\Service\Textline $textlineService
             */
            $textlineService = $this->getServiceLocator()->get('service_textline');

            foreach ($list as $item) {
                //Don't send an email if applicant is not rejected anymore
                if (Applicant::APPLICANT_STATUS_REJECT != $item['status']) {
                    $emailQueueService->delete($item['id']);
                    continue;
                }

                $mailer = $this->getServiceLocator()->get('Mailer\Email');
                $emailValidator = new EmailAddress();

                if (!$emailValidator->isValid($item['email'])) {
                    $this->outputMessage('[error] Applicant email is not valid: ' . $item['email'] . ' Removing from queue.');

                    $this->gr2err("Applicant rejection mail wasn't sent", [
                        'applicant_id'   => $item['entity_id'],
                        'applicant_name' => $item['applicant_name'],
                    ]);

                    continue;
                }

                $mailer->send(
                    'applicant-rejection',
                    [
                        'to'           => $item['email'],
                        'bcc'          => EmailAliases::HR_EMAIL,
                        'to_name'      => $item['applicant_name'],
                        'replyTo'      => EmailAliases::HR_EMAIL,
                        'from_address' => EmailAliases::HR_EMAIL,
                        'from_name'    => 'Ginosi Apartments',
                        'subject'      => $textlineService->getUniversalTextline(1608, true),
                        'msg'          => Helper::evaluateTextline(
                            $textlineService->getUniversalTextline(1607),
                            [
                                '{{APPLICANT_NAME}}' => $item['applicant_name'],
                                '{{POSITION_TITLE}}' => $item['position_title'],
                            ]
                        )
                    ]
                );

                $emailQueueService->delete($item['id']);

                $this->outputMessage("\e[1;32mRejection email to {$item['applicant_name']} sent. \e[0m");
            }
        } else {
            $this->outputMessage("\e[1;32mQueue is empty. \e[0m");
        }

        $this->outputMessage("\e[1;32mDone. \e[0m");
    }
}
