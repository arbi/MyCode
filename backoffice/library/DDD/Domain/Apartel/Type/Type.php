<?php

namespace DDD\Domain\Apartel\Type;

class Type
{
    protected $id;
    protected $apartel_id;
    protected $name;
    protected $cubilis_id;
    protected $active;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->apartel_id = (isset($data['apartel_id'])) ? $data['apartel_id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->cubilis_id = (isset($data['cubilis_id'])) ? $data['cubilis_id'] : null;
        $this->active = (isset($data['active'])) ? $data['active'] : null;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return mixed
     */
    public function getApartelId()
    {
        return $this->apartel_id;
    }

    /**
     * @return mixed
     */
    public function getCubilisId()
    {
        return $this->cubilis_id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }


}
