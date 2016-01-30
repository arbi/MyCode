<?php

namespace Application\Service;

use Application\Entity\Error;
use ZF\ApiProblem\ApiProblem;

class ApiException extends \Exception
{
    public function __construct($code = false)
    {
        if (!$code) {
            $code = Error::SERVER_SIDE_PROBLEM_CODE;
        }
        parent::__construct('', $code);
    }
}
