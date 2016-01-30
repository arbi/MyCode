<?php
namespace Console\Controller;

use DDD\Dao\Accommodation\Accommodations;
use Library\Controller\ConsoleBase;
use Library\Constants\Roles;
use Library\Constants\EmailAliases;
use Library\Constants\DomainConstants;
use Library\Constants\Constants;

class ConciergeController extends ConsoleBase
{
    public function sendArrivalsMailAction()
    {
        /**
         * @var \DDD\Service\ApartmentGroup $service
         * @var \DDD\Service\Textline $textlineService
         */
        $service   = $this->getServiceLocator()->get('service_apartment_group');
        $textlineService = $this->getServiceLocator()->get('service_textline');

        $groupList = $service->getGroupListByUserId();
        $mailer    = $this->getServiceLocator()->get('Mailer\Email');

        $mailTime = strtotime(date('Y-m-d 09:00'));

        foreach ($groupList as $group) {
            if (is_null($group->getEmail())) {
                continue;
            }

            $contactInfo = $service->getContactPhone($group->getId());

            $timezone = $group->getTimezone();
            $time     = new \DateTime(null, new \DateTimeZone($timezone));

            $groupTime = strtotime($time->format('Y-m-d H:00'));
            $groupName = strtolower(preg_replace('/\s/', '', $group->getName()));

            if (strlen($groupName) >= 4) {
                $groupName = substr($groupName, 0, 4);
            }

            $key       = $groupName . $group->getId() . $time->format('Ymd');
            $string    = $time->format('Y-m-d');

            $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $iv     = mcrypt_create_iv($ivSize, MCRYPT_RAND);

            $ciphertext = mcrypt_encrypt(
                MCRYPT_RIJNDAEL_128,
                $key,
                $string,
                MCRYPT_MODE_CBC,
                $iv
            );

            $ciphertext = $iv . $ciphertext;

            $request = rawurlencode(base64_encode($ciphertext));

            $buttonUri = 'http://'. DomainConstants::WS_DOMAIN_NAME .
                '/arrivals/'. $group->getId() . '/' . $time->format('Ymd') . '?key=' . $request;

            if ($mailTime == $groupTime) {
                $mailer->send(
                    'concierge-email',
                    [
                        'to'           => $group->getEmail(),
                        'to_name'      => $group->getName(),
                        'replyTo'      => EmailAliases::RT_RESERVATION,
                        'from_address' => EmailAliases::FROM_MAIN_MAIL,
                        'from_name'    => 'Ginosi Concierge Service',
                        'subject'      => 'Guests for ' . $group->getName() . ' on ' . date(Constants::GLOBAL_DATE_FORMAT),
                        'buttonUri'    => $buttonUri,
                        'title'        => $textlineService->getUniversalTextline(1500),
                        'content'      => $textlineService->getUniversalTextline(1499),
                        'contactPhone' => $contactInfo['contact_phone'],
                        'country'      => $contactInfo['name'],
                        'buttonText'   => $textlineService->getUniversalTextline(1501),
                    ]
                );

            }
        }
    }
}