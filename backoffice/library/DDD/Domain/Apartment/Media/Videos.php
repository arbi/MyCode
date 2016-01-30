<?php

namespace DDD\Domain\Apartment\Media;

class Videos
{
    private $id;
    private $apartment_id;
    
    private $video;
    private $key_entry_video;
    
    public function exchangeArray($data) {
        $this->id       = (isset($data['id'])) ? $data['id'] : null;
        $this->apartment_id	= (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        
        $this->video            = (isset($data['video'])) ? $data['video'] : null;
        $this->key_entry_video  = (isset($data['key_entry_video'])) ? $data['key_entry_video'] : null;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getApartmentId() {
        return $this->apartment_id;
    }

    public function getVideo() {
        return $this->video;
    }

    public function getKeyEntryVideo() {
        return $this->key_entry_video;
    }
}
