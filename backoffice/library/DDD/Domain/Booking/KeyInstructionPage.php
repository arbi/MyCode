<?php

namespace DDD\Domain\Booking;

class KeyInstructionPage
{
    protected $id;
    protected $res_number;

    protected $apartelId;

    protected $apartment_id;
    protected $apartment_id_assigned;
    protected $acc_name;
    protected $acc_country_id;
    protected $acc_province_id;
    protected $acc_city_id;
    protected $acc_address;
    protected $acc_postal_code;

    protected $date_from;
    protected $date_to;

    protected $booker_price;

    protected $pax;
    protected $occupancy;

    protected $guestFirstName;
    protected $guestLastName;

    protected $guestPhone;
    protected $guestTravelPhone;

    protected $guestEmail;
    protected $secondaryEmail;

    protected $guestCityName;

    protected $ip;

    protected $pin;
    protected $outside_door_code;

    protected $ki_page_status;
    protected $ki_mail_sent;
    protected $ki_viewed_date;
    protected $ki_page_type;

    protected $geo_lat;
    protected $geo_lon;

    protected $floor;
    protected $unit;

    protected $wifi_network;
    protected $wifi_password;

    protected $youtube_video;

    protected $ki_viewed;

    protected $check_in_time;
    protected $check_out_time;

    /**
     * @var bool
     */
    protected $showApartmentEntryCode;

    protected $map_attachment;
    protected $officeMapAttachment;

    protected $block;

    protected $superviser_user_id;
    protected $channel_res_id;

    protected $location_phone;
    protected $building_phone;
    protected $office_phone;
    protected $office_id;
    protected $buildingId;

    protected $parking;
    protected $parkingTextlineId;
    protected $partnerId;

    public function exchangeArray($data)
    {
        $this->id                       = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number               = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->apartelId                = (isset($data['apartel_id'])) ? $data['apartel_id'] : null;
        $this->apartment_id             = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartment_id_assigned    = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->acc_name                 = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->acc_country_id           = (isset($data['acc_country_id'])) ? $data['acc_country_id'] : null;
        $this->acc_province_id          = (isset($data['acc_province_id'])) ? $data['acc_province_id'] : null;
        $this->acc_city_id              = (isset($data['acc_city_id'])) ? $data['acc_city_id'] : null;
        $this->acc_address              = (isset($data['acc_address'])) ? $data['acc_address'] : null;
        $this->acc_postal_code          = (isset($data['acc_postal_code'])) ? $data['acc_postal_code'] : null;
        $this->date_from                = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->date_to                  = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->booker_price             = (isset($data['booker_price'])) ? $data['booker_price'] : null;
        $this->pax             		    = (isset($data['pax'])) ? $data['pax'] : null;
        $this->occupancy          	    = (isset($data['occupancy'])) ? $data['occupancy'] : null;
        $this->guestFirstName           = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName            = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->guestPhone               = (isset($data['guest_phone'])) ? $data['guest_phone'] : null;
        $this->guestTravelPhone         = (isset($data['guest_travel_phone'])) ? $data['guest_travel_phone'] : null;
        $this->guestEmail               = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->secondaryEmail           = (isset($data['secondary_email'])) ? $data['secondary_email'] : null;
        $this->guestCityName            = (isset($data['guest_city_name'])) ? $data['guest_city_name'] : null;
        $this->ip                       = (isset($data['ip_address'])) ? $data['ip_address'] : null;
        $this->pin                      = (isset($data['pin'])) ? $data['pin'] : null;
        $this->outside_door_code        = (isset($data['outside_door_code'])) ? $data['outside_door_code'] : null;
        $this->ki_page_status           = (isset($data['ki_page_status'])) ? $data['ki_page_status'] : null;
        $this->ki_mail_sent             = (isset($data['ki_mail_sent'])) ? $data['ki_mail_sent'] : null;
        $this->ki_viewed_date           = (isset($data['ki_viewed_date'])) ? $data['ki_viewed_date'] : null;
        $this->ki_page_type             = (isset($data['ki_page_type'])) ? $data['ki_page_type'] : null;
        $this->geo_lat                  = (isset($data['geo_lat'])) ? $data['geo_lat'] : null;
        $this->geo_lon                  = (isset($data['geo_lon'])) ? $data['geo_lon'] : null;
        $this->floor                    = (isset($data['floor'])) ? $data['floor'] : null;
        $this->unit                     = (isset($data['unit'])) ? $data['unit'] : null;
        $this->wifi_network             = (isset($data['wifi_network'])) ? $data['wifi_network'] : null;
        $this->wifi_password            = (isset($data['wifi_password'])) ? $data['wifi_password'] : null;
        $this->youtube_video            = (isset($data['youtube_video'])) ? $data['youtube_video'] : null;
        $this->ki_viewed                = (isset($data['ki_viewed'])) ? $data['ki_viewed'] : null;
        $this->check_in_time            = (isset($data['check_in_time'])) ? $data['check_in_time'] : null;
        $this->check_out_time           = (isset($data['check_out_time'])) ? $data['check_out_time'] : null;
        $this->showApartmentEntryCode   = (isset($data['show_apartment_entry_code'])) ? $data['show_apartment_entry_code'] : null;
        $this->map_attachment           = (isset($data['map_attachment'])) ? $data['map_attachment'] : null;
        $this->officeMapAttachment      = (isset($data['office_map_attachment'])) ? $data['office_map_attachment'] : null;
        $this->block                    = (isset($data['block'])) ? $data['block'] : null;
        $this->superviser_user_id       = (isset($data['superviser_user_id'])) ? $data['superviser_user_id'] : null;
        $this->channel_res_id           = (isset($data['channel_res_id'])) ? $data['channel_res_id'] : null;
        $this->location_phone           = (isset($data['location_phone'])) ? $data['location_phone'] : null;
        $this->building_phone           = (isset($data['building_phone'])) ? $data['building_phone'] : null;
        $this->office_phone             = (isset($data['office_phone'])) ? $data['office_phone'] : null;
        $this->office_id                = (isset($data['office_id'])) ? $data['office_id'] : null;
        $this->parking                  = (isset($data['parking'])) ? $data['parking'] : null;
        $this->parkingTextlineId        = (isset($data['parking_textline_id'])) ? $data['parking_textline_id'] : null;
        $this->buildingId               = (isset($data['building_id'])) ? $data['building_id'] : null;
        $this->partnerId                = (isset($data['partner_id'])) ? $data['partner_id'] : null;
    }

    public function getBlock() {
        return $this->block;
    }

    public function getMapAttachment() {
        return $this->map_attachment;
    }

    /**
     * @return mixed
     */
    public function getOfficeMapAttachment()
    {
        return $this->officeMapAttachment;
    }

    public function getId() {
        return $this->id;
    }

    public function getResNumber() {
        return $this->res_number;
    }

    public function getApartelId() {
        return $this->apartelId;
    }

    public function getApartmentId() {
        return $this->apartment_id;
    }

    public function getApartmentIdAssigned() {
        return $this->apartment_id_assigned;
    }

    public function getApartmentName() {
        return $this->acc_name;
    }

    public function getApartmentCountryId()
    {
        return $this->acc_country_id;
    }

    public function getAccProvinceId() {
        return $this->acc_province_id;
    }

    public function getApartmentCityId()
    {
        return $this->acc_city_id;
    }

    public function getAccAddress() {
        return $this->acc_address;
    }

    public function getAccPostalCode() {
        return $this->acc_postal_code;
    }

    public function getDateFrom() {
        return $this->date_from;
    }

    public function getDateTo() {
        return $this->date_to;
    }

    public function getBookerPrice() {
        return $this->booker_price;
    }

    public function getPAX() {
        return $this->pax;
    }

    public function getGuestFirstName() {
        return ucwords($this->guestFirstName);
    }

    public function getGuestLastName() {
        return ucwords($this->guestLastName);
    }

    public function getGuestPhone() {
        return $this->guestPhone;
    }

    public function getGuestTravelPhone() {
        return $this->guestTravelPhone;
    }

    public function getGuestEmail() {
        return $this->guestEmail;
    }

    public function getSecondaryEmail() {
        return $this->secondaryEmail;
    }

    public function getGuestCityName() {
        return $this->guestCityName;
    }

    public function getIp() {
        return long2ip($this->ip);
    }

    public function getPin() {
        return $this->pin;
    }

    public function getOutsideCode() {
        return $this->outside_door_code;
    }

    public function getKiPageStatus() {
        return $this->ki_page_status;
    }

    public function getKiMailSent() {
        return $this->ki_mail_sent;
    }

    public function getKiViewDate() {
        return $this->ki_viewed_date;
    }

    public function getKiPageType() {
        return (int)$this->ki_page_type;
    }

    public function getGeoLat() {
        return $this->geo_lat;
    }

    public function getGeoLon() {
        return $this->geo_lon;
    }

    public function getFloor() {
        return $this->floor;
    }

    public function getUnit() {
        return $this->unit;
    }

    public function getWifiNetwork() {
        return $this->wifi_network;
    }

    public function getWifiPassword() {
        return $this->wifi_password;
    }

    public function getYoutubeVideo() {
        return $this->youtube_video;
    }

    public function isKiViewed()
    {
        return $this->ki_viewed;
    }

    public function getCheckInTime() {
        return $this->check_in_time;
    }

    public function getCheckOutTime() {
        return $this->check_out_time;
    }

    /**
     * @return bool
     */
    public function getShowApartmentEntryCode()
    {
        return $this->showApartmentEntryCode;
    }

    public function getSuperviserId() {
        return $this->superviser_user_id;
    }

    public function getChannelResId() {
        return $this->channel_res_id;
    }

    public function getLocationPhone()
    {
        return $this->location_phone;
    }

    public function getBuildingPhone()
    {
        return $this->building_phone;
    }

    public function getOfficePhone()
    {
        return $this->office_phone;
    }

    public function getOfficeId()
    {
        return $this->office_id;
    }

    public function getOccupancy()
    {
        return $this->occupancy;
    }

    /**
     * @return mixed
     */
    public function getParkingTextlineId()
    {
        return $this->parkingTextlineId;
    }

    /**
     * @return bool
     */
    public function hasParking()
    {
        return $this->parking ? true : false;
    }

    /**
     * @param $pin
     * @return $this
     */
    public function setPin($pin)
    {
        $this->pin = $pin;
        return $this;
    }

    /**
     * @param $outsideDoorCode
     * @return $this
     */
    public function setOutsideDoorCode($outsideDoorCode)
    {
        $this->outside_door_code = $outsideDoorCode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBuildingId()
    {
        return $this->buildingId;
    }

    /**
     * @return mixed
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }

}
