<?php

namespace Library\OTACrawler;

class DistributorItem
{
    /**
     * @var array $data
     */
    protected $data;

    public function __construct($data)
    {
        $this->data = iterator_to_array($data);
    }

    public function getId()
    {
        return isset($this->data['id']) ? $this->data['id'] : null;
    }

    public function getIdentityId()
    {
        return isset($this->data['apartment_id'])
            ? $this->data['apartment_id']
            : (
                isset($this->data['apartel_id'])
                    ? $this->data['apartel_id']
                    : null
            );
    }

    public function getPartnerId()
    {
        return isset($this->data['partner_id']) ? $this->data['partner_id'] : null;
    }

    public function getPartnerName()
    {
        return isset($this->data['partner_name']) ? $this->data['partner_name'] : null;
    }

    public function getReference()
    {
        return isset($this->data['reference']) ? $this->data['reference'] : null;
    }

    public function getUrl()
    {
        return isset($this->data['url']) ? $this->data['url'] : null;
    }

    public function getStatus()
    {
        return isset($this->data['status']) ? $this->data['status'] : null;
    }

    public function getOtaStatus()
    {
        return isset($this->data['ota_status']) ? $this->data['ota_status'] : null;
    }

    public function getDateListed()
    {
        return isset($this->data['date_listed']) ? $this->data['date_listed'] : null;
    }

    public function getDateEdited()
    {
        return isset($this->data['date_edited']) ? $this->data['date_edited'] : null;
    }
}
