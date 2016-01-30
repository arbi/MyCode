<?php

namespace Library\DbManager;

class DbErrorHandler {
    
    const ECODE_UNKNOWN = 0;            // Unknown error
    const ECODE_DB_ERROR = 1;           // DB request failed
    const ECODE_WRONG_PARAMS = 2;       // Wrong params
    const ECODE_ALREADY_EXISTS = 3;     // Such record already exist    
    const ECODE_RECORD_USED = 5;        // Can't delete. Record used
               
    protected $code;
    protected $message;

    function __construct($message='', $code='') {
        $this->setError($message, $code);
    }

    public function getCode() {
        return $this->code;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function setError($message, $code='') {
        $this->code = $code;
        $this->message = $message;
    }

    public function __toString(){
        return $this->getMessage();
    }
}