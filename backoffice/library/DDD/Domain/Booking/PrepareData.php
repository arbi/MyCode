<?php

namespace DDD\Domain\Booking;

class PrepareData {
	
    protected $gem;
    protected $houskeeper;
    protected $status;
    protected $balance;
    protected $res_number;
    
    public function exchangeArray($data) {
        $this->gem 			= (isset($data['gem'])) ? $data['gem'] : null;
        $this->houskeeper 	= (isset($data['houskeeper'])) ? $data['houskeeper'] : null;
        $this->status 	= (isset($data['status'])) ? $data['status'] : null;
        $this->balance 	= (isset($data['guest_balance'])) ? $data['guest_balance'] : null;
        $this->res_number	= (isset($data['res_number'])) ? $data['res_number'] : null;
    }
    
    public function getResNumber() {
    	return $this->res_number;
    }
    
    public function getBalance() {
    	return $this->balance;
    }
    
    public function getStatus() {
    	return $this->status;
    }
    
    public function getGem() {
    	return $this->gem;
    }
    
    public function getHouskeeper() {
    	return $this->houskeeper;
    }
}

