<?php

namespace ZF2Graylog2\Logger\Formatter;

use Zend\Log\Formatter\Base;
use Gelf\Message;

class Gelf extends Base
{
    private $facility = 'ZF2';
    
    public function __construct($facility = NULL)
    {
        if (!is_null($facility)) {
            $this->facility = (string)$facility;
        }
    }
    
    public function format($event)
    {
        
        $message = new Message();
        
        $message->setFacility($this->facility)
                ->setLevel($event['priority'])
                ->setShortMessage($event['message'])
                ->setFullMessage($event['message']);
        
        if (isset($event['extra']['full_message'])) {
            $message->setFullMessage($event['extra']['full_message']);
        }
        
        if (isset($event['extra']['short'])) {
            $message->setShortMessage($event['extra']['short']);
        }

        if (isset($event['extra']['file'])) {
            $message->setFile($event['extra']['file']);
        }
        
        if (isset($event['extra']['line'])) {
            $message->setLine($event['extra']['line']);
        }

        if (isset($event['extra']['version'])) {
            $message->setVersion($event['extra']['version']);
        }

        if (isset($event['extra']['facility'])) {
            $message->setFacility($event['extra']['facility']);
        }
        
        foreach ($event['extra'] as $key => $value) {
            $message->setAdditional($key, $value);
        }
        
        return $message;
    }
}
