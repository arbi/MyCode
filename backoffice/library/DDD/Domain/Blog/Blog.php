<?php

namespace DDD\Domain\Blog;

class Blog
{
    protected $id;
    protected $title;
    
    /**
     * @access protected
     * @var string
     */
    protected $slug;
    protected $content;
    protected $date;
    protected $img;
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->title = (isset($data['title'])) ? $data['title'] : null;
        $this->slug = (isset($data['slug'])) ? $data['slug'] : null;
        $this->content = (isset($data['content'])) ? $data['content'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->img = (isset($data['img'])) ? $data['img'] : null;
    }
    
    /**
     * @access public
     * @return string
     */
    public function getSlug() {
    	return $this->slug;
    }
    
    public function getImg() {
            return $this->img;
    }
    
    public function setImg($val) {
            $this->img = $val;
            return $this;
    }
    
    public function getDate() {
            return $this->date;
    }
    
    public function setDate($val) {
            $this->date = $val;
            return $this;
    }
    
    public function getContent() {
            return $this->content;
    }
    
    public function setContent($val) {
            $this->content = $val;
            return $this;
    }
    
    public function getTitle() {
            return $this->title;
    }
    
    public function setTitle($val) {
            $this->title = $val;
            return $this;
    }
    
    public function getId() {
            return $this->id;
    }
    
    public function setId($val) {
            $this->id = $val;
            return $this;
    }
    
}
