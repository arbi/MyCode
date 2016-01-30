<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ReviewBase
 *
 * @author tigran.tadevosyan@ginosi.com
 */

namespace DDD\Domain\Review;

class ReviewBooking
{
    protected $id;
    
    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id'])) ? $data['id'] : null;
    }
    
    public function getId(){
		return $this->id;
	}
}