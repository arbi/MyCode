<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ReviewBase
 *
 * @author tigran.tadevosyan
 */

namespace DDD\Domain\Review;

class ReviewBase
{
    protected $id;
    protected $res_number;
    protected $score;
    protected $total_score;
    protected $liked;
    protected $dislike;
    protected $status;
    protected $acc_name;
    protected $apartment_id;
    
    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number   = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->score        = (isset($data['score'])) ? $data['score'] : null;
        $this->total_score  = (isset($data['total_score'])) ? $data['total_score'] : null;
        $this->liked        = (isset($data['liked'])) ? $data['liked'] : null;
        $this->dislike      = (isset($data['dislike'])) ? $data['dislike'] : null;
        $this->status       = (isset($data['status'])) ? $data['status'] : null;
        $this->acc_name     = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->apartment_id       = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
    }
    
    public function getId(){
		return $this->id;
	}
	public function setId($val){
		$this->id = $val;
	}
	
    public function getResNumber(){
		return $this->res_number;
	}
	public function setResNumber($val){
		$this->res_number = $val;
	}
    
    public function getScore(){
        return $this->score;
	}
	public function setScore($val){
		$this->score = $val;
	}
    
    public function getTotalScore(){
        return $this->total_score;
	}
	public function setTotalScore($val){
		$this->total_score = $val;
	}
    
	public function getLiked(){
		return $this->liked;
	}
	public function setLiked($val){
		$this->liked = $val;
	}
    
    public function getDislike(){
		return $this->dislike;
	}
	public function setDislike($val){
		$this->dislike = $val;
	}
    
    public function getStatus(){
		return $this->status;
	}
	public function setStatus($val){
		$this->status = $val;
	}
    
    public function getApartmentName(){
		return $this->acc_name;
	}
    
    public function getApartmentId(){
		return $this->apartment_id;
	}
	public function setApartmentId($val){
		$this->apartment_id = $val;
	}
}

?>
