<?php

namespace ZF2Graylog2\Traits;

use Zend\Log\Writer\Null;
use ZF2Graylog2\Logger\Writer\Graylog2;

trait Logger
{
    public $logger;
    private $sessionHash;
    private $extra;

    /**
     * @param array $extra
     * @return \Zend\Log\Logger
     */
    public function constructLogger(array $extra = [])
    {
        if (!isset($this->logger)) {
            $config = $this->serviceLocator->get('Config');

            $this->logger = new \Zend\Log\Logger();

            try {
                $writer = new Graylog2(
                    $config['logger']['facility'],
                    $config['logger']['host'],
                    $config['logger']['port']
                );
            } catch (\Exception $ex) {
                $writer = new Null();
            }

            $this->logger->addWriter($writer);
        }

        if (empty($this->sessionHash)) {
            $this->sessionHash = time();
        }

        if (!empty($extra)) {
            $this->extra = array_merge(
                $extra,
                ['session_hash' => $this->sessionHash]
            );
        } else {
            $this->extra = ['session_hash' => $this->sessionHash];
        }


        return $this->logger;
    }

    /**
     * Emergency
     *
     * @param string $message
     * @param array $extra
     */
    public function gr2emerg($message, Array $extra = [])
    {
        $this->constructLogger($extra);

        $this->logger->emerg($message, $this->extra);
    }

    /**
     * Alert
     *
     * @param string $message
     * @param array $extra
     */
    public function gr2alert($message, Array $extra = [])
    {
        $this->constructLogger($extra);

        $this->logger->alert($message, $this->extra);
    }

    /**
     * Critical
     *
     * @param string $message
     * @param array $extra
     */
    public function gr2crit($message, Array $extra = [])
    {
        $this->constructLogger($extra);

        $this->logger->crit($message, $this->extra);
    }

    /**
     * Error
     *
     * @param string $message
     * @param array $extra
     */
    public function gr2err($message, Array $extra = [])
    {
        $this->constructLogger($extra);

        $this->logger->err($message, $this->extra);
    }

    /**
     * Warning
     *
     * @param string $message
     * @param array $extra
     */
    public function gr2warn($message, Array $extra = [])
    {
        $this->constructLogger($extra);

        $this->logger->warn($message, $this->extra);
    }

    /**
     * Notice
     *
     * @param string $message
     * @param array $extra
     */
    public function gr2notice($message, Array $extra = [])
    {
        $this->constructLogger($extra);

        $this->logger->notice($message, $this->extra);
    }

    /**
     * Information
     *
     * @param string $message
     * @param array $extra
     */
    public function gr2info($message, Array $extra = [])
    {
        $this->constructLogger($extra);

        $this->logger->info($message, $this->extra);
    }

    /**
     * Debug
     *
     * @param string $message
     * @param array $extra
     */
    public function gr2debug($message, Array $extra = [])
    {
        $this->constructLogger($extra);

        $this->logger->debug($message, $this->extra);
    }

    /**
     * @param \Exception $e
     * @param string $message
     * @param array $extra
     */
    public function gr2logException(\Exception $e, $message = '', array $extra = [])
    {
        $this->constructLogger($extra);

        if (!empty($message)) {
            $message = $message . ' : ' . $e->getMessage();
        } else {
            $message = $e->getMessage();
        }

        $this->logger->crit($message, [
                'exception' => TRUE,
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'code'      => $e->getCode(),
                'trace'     => $e->getTraceAsString()
            ]);

        if ($e->getPrevious() !== NULL) {
            $this->gr2logException(
                $e->getPrevious(),
                'Previous Exception > ' . $e->getMessage()
            );
        }
    }
}
