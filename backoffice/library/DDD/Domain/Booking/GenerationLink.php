<?php

namespace DDD\Domain\Booking;

class GenerationLink
{

    protected $id;
    protected $provide_cc_page_hash;
    protected $guestLanguageIso;

    public function exchangeArray($data)
    {
        $this->id                   = (isset($data['id'])) ? $data['id'] : null;
        $this->provide_cc_page_hash = (isset($data['provide_cc_page_hash'])) ? $data['provide_cc_page_hash'] : null;
        $this->guestLanguageIso     = (isset($data['guest_language_iso'])) ? $data['guest_language_iso'] : null;
    }

    public function getGuestLanguageIso()
    {
        return $this->guestLanguageIso;
    }

    public function getProvideCcPageHash()
    {
        return $this->provide_cc_page_hash;
    }

    public function getId()
    {
        return $this->id;
    }

}
