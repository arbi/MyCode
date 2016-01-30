<?php

namespace DDD\Domain\Apartel\General;

class  General
{
    protected $id;
    protected $apartment_group_id;
    protected $cubilis_id;
    protected $sync_cubilis;
    protected $cubilis_username;
    protected $cubilis_password;
    protected $slug;
    protected $status;

    public function exchangeArray($data)
    {
        $this->id                   = (isset($data['id'])) ? $data['id'] : null;
        $this->apartment_group_id   = (isset($data['apartment_group_id'])) ? $data['apartment_group_id'] : null;
        $this->cubilis_id           = (isset($data['cubilis_id'])) ? $data['cubilis_id'] : null;
        $this->sync_cubilis         = (isset($data['sync_cubilis'])) ? $data['sync_cubilis'] : null;
        $this->cubilis_username     = (isset($data['cubilis_username'])) ? $data['cubilis_username'] : null;
        $this->cubilis_password     = (isset($data['cubilis_password'])) ? $data['cubilis_password'] : null;
        $this->slug                 = (isset($data['slug'])) ? $data['slug'] : null;
        $this->status               = (isset($data['status'])) ? $data['status'] : null;
    }

    /**
     * @return mixed
     */
    public function getApartmentGroupId()
    {
        return $this->apartment_group_id;
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
    public function getCubilisPassword()
    {
        return $this->cubilis_password;
    }

    /**
     * @return mixed
     */
    public function getCubilisUsername()
    {
        return $this->cubilis_username;
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
    public function getSyncCubilis()
    {
        return $this->sync_cubilis;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

}
