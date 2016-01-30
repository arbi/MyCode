<?php

namespace DDD\Domain\Apartment\Review;

class View
{
    protected $id;
    protected $liked;
    protected $dislike;
    protected $status;
    protected $score;
    protected $resNumber;
    protected $date_from;
    protected $review_date;
    protected $apartmentName;
    protected $apartmentId;

   public function exchangeArray($data) {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->liked = (isset($data['liked'])) ? $data['liked'] : null;
        $this->dislike = (isset($data['dislike'])) ? $data['dislike'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->score = (isset($data['score'])) ? $data['score'] : null;
        $this->resNumber = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->date_from = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->review_date = (isset($data['date'])) ? $data['date'] : null;
        $this->apartmentName = (isset($data['apartment_name'])) ? $data['apartment_name'] : null;
        $this->apartmentId = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
    }
    
    public function getDate_from() {
        return $this->date_from;
    }
    
    public function getReviewDate() {
        return $this->review_date;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getLiked() {
        return $this->liked;
    }

    public function getDislike() {
        return $this->dislike;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getScore() {
        return $this->score;
    }

    /**
     * @access public
     * @return string
     */
    public function getResNumber() {
    	return $this->resNumber;
    }

    /**
     * @return mixed
     */
    public function getApartmentName()
    {
        return $this->apartmentName;
    }

    /**
     * @return mixed
     */
    public function getApartmentId()
    {
        return $this->apartmentId;
    }

}