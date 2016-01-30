<?php

namespace Library\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Stdlib\RequestInterface;
use ZF2Graylog2\Traits\Logger;

abstract class ConsoleBase extends AbstractActionController
{
    use Logger;
    const EMERG  = 0;
    const ALERT  = 1;
    const CRIT   = 2;
    const ERR    = 3;
    const WARN   = 4;
    const NOTICE = 5;
    const INFO   = 6;
    const DEBUG  = 7;

    private $colorMapping = [
        '[black]'       => "\e[0;30m",
        '[red]'         => "\e[0;31m",
        '[green]'       => "\e[0;32m",
        '[brown]'       => "\e[0;33m",
        '[blue]'        => "\e[0;34m",
        '[purple]'      => "\e[0;35m",
        '[cyan]'        => "\e[0;36m",
        '[light_grey]'  => "\e[0;37m",
        '[dark_grey]'   => "\e[1;30m",
        '[light_red]'   => "\e[1;31m",
        '[light_green]' => "\e[1;32m",
        '[yellow]'      => "\e[1;33m",
        '[light_blue]'  => "\e[1;34m",
        '[light_purple]'=> "\e[1;35m",
        '[light_cyan]'  => "\e[1;36m",
        '[white]'       => "\e[1;37m",
        '[success]'     => "\e[1;32m",
        '[warning]'     => "\e[1;33m",
        '[error]'       => "\e[1;31m",
    ];

    /**
     * @var boolean
     */
    protected $verboseMode;

    /**
     * @param $request RequestInterface
     */
    protected function initCommonParams($request)
    {
        if ($request->getParam('verbose') || $request->getParam('v'))
        {
            $this->verboseMode = true;
        }
    }

    /**
     * @param $message string
     * <pre>
     * You can use color tags in message.
     * Possible color options are
     * [success] - green,
     * [warning] - brown,
     * [error] - red,
     * [black], [red], [green], [brown], [blue], [purple], [cyan],
     * [light_grey], [dark_grey], [light_red], [light_green], [yellow],
     * [light_blue], [light_purple], [light_cyan]
     * </pre>
     * @author Tigran Petrosyan
     */
    protected function outputMessage($message)
    {
        $message = str_replace(array_keys($this->colorMapping), array_values($this->colorMapping), $message);
        if ($this->verboseMode) {
            echo $message . "\e[0m" . PHP_EOL;
        }
    }
}
