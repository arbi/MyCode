<?php

namespace Library\Plugins;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Library\Constants\EmailAliases;
use Zend\Validator\EmailAddress;

class Email extends AbstractPlugin {
    /**
     *
     * @param string $subject Subject of mail message.
     * @param string $message Message of mail. Can contain HTML tags and spec symbols, for example "\n"
     * @param array|string $to If string and more one email, delimit by ',' (without spaces)
     * @param object $sl
     * @return boolean
     */
    public function email($subject, $message, $to, $sl)
    {
        try {
            $devMail = 'notify@ginosi.com';
            $emailValidator = new EmailAddress();
            $mailer = $sl->get('Mailer\Email-Alerts');

            if (is_string($to) AND strstr($to, ',')) {
                $to = preg_split( "/(, |,)/", $to );

            } elseif (is_string($to)) {
                $to = [$to];
            }

            if (is_array($to)) {
                if (!in_array($devMail, $to)) {
                    array_push($to, $devMail);
                }

                foreach ($to as $key => $email)  {
                    if (!$emailValidator->isValid($email)) {
                        unset($to[$key]);
                    }
                }

                if (empty($to)) {
                    return FALSE;
                }

                foreach ($to as $mailTo) {
                    $mailer->send(
                        'soother',
                        array(
                            'layout'       => 'clean',
                            'to'           => $mailTo,
                            'from_address' => EmailAliases::FROM_MAIN_MAIL,
                            'from_name'    => 'Ginosi Backoffice',
                            'subject'      => $subject,
                            'message'      => print_r($message, true),
                    ));
                }

            }

            return TRUE;
        } catch (\Exception $e) {
            return FALSE;
        }
    }
}

