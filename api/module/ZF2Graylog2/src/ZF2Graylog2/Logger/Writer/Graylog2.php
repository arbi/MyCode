<?php

namespace ZF2Graylog2\Logger\Writer;

use ZF2Graylog2\Logger\Formatter;
use Zend\Log\Writer\AbstractWriter;
use Gelf\Transport\UdpTransport;
use Gelf\Publisher;

class Graylog2 extends AbstractWriter
{
    private $transport;
    private $publisher;
    
    protected $formatter;
    
    public function __construct(
            $facility = 'ZF2',
            $hostname = UdpTransport::DEFAULT_HOST,
            $port = UdpTransport::DEFAULT_PORT,
            $size = UdpTransport::CHUNK_SIZE_LAN)
    {
        $this->transport = new UdpTransport($hostname, $port, $size);
        
        $this->publisher = new Publisher();
        $this->publisher->addTransport($this->transport);

        $this->formatter = new Formatter\Gelf($facility);
    }
    

    public function doWrite(Array $event)
    {
        $message = $this->formatter->format($event);
        
        $this->publisher->publish($message);
    }
}
