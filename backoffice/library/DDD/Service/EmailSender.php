<?php

namespace DDD\Service;

use DDD\Service\Booking as BookingService;
use Library\Constants\DomainConstants;
use Library\Plugins\Email as EmailPlugin;


class EmailSender extends ServiceBase
{
    protected $emailPlugin          = null;

    /**
     *
     * @param string $subject Subject of mail message.
     * @param string $message Message of mail. Can contain HTML tags and spec symbols, for example "\n"
     * @param array|string $to If string and more one email, delimit by ',' (without spaces)
     * @return boolean
     */
    public function email($subject, $message, $to = 'notify@ginosi.com')
    {
        if (!($this->emailPlugin instanceof EmailPlugin)) {
            $this->emailPlugin = new EmailPlugin();
        }

        $this->emailPlugin->email($subject, $message, $to, $this->getServiceLocator());
    }

    /**
     * Email given message and other necessary information to Developers and
     * Operations Team
     *
     * @param string $rawMessage
     * @throws \Exception
     */
    public function emailCritical($rawMessage)
    {
        // Detect Environment
        $environment = (getenv('APPLICATION_ENV') == 'development' || DomainConstants::BO_DOMAIN_NAME !== "backoffice.ginosi.com")
            ? ' (TEST from ' . DomainConstants::BO_DOMAIN_NAME . ')'
            : '';

        $this->gr2crit("Critical Email Sent", [
            'cron' => 'ChannelManager',
            'full_message' => $rawMessage
        ]);
        $this->email('Critical Action Required' . $environment, $rawMessage, 'cubilis@ginosi.com');
    }

    /**
     * Email given message and other necessary information to Developers
     *
     * @param string $rawMessage
     * @throws \Exception
     */
    public function emailWarning($rawMessage)
    {
        list($caller) = debug_backtrace(false);
        $gmTime = date('D, d M Y H:i:s');

        try {
            throw new \Exception('bo.');
        } catch (\Exception $ex) {
            $traceAsString = $ex->getTraceAsString();
        }

        $time    = "Time: {$gmTime}";
        $method  = "Method: {$caller['class']}::{$caller['function']}()";
        $file    = "File: {$caller['file']}, Line: {$caller['line']}";
        $message = "Message: {$rawMessage}";
        $trace   = "Trace: <pre>{$traceAsString}</pre>";
        $result = implode("<br>\n", [$message, $time, $method, $file, $trace]);

        // Detect Environment
        $environment = (DomainConstants::BO_DOMAIN_NAME != "backoffice.ginosi.com") ? ' (TEST)' : '';

        $this->gr2warn("Warning Email sent", [
            'cron'         => 'ChannelManager',
            'full_message' => $rawMessage
        ]);
        $this->email('Warning Email from BO' . $environment, $result);
    }

}
