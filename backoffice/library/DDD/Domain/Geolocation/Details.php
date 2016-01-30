<?php

namespace DDD\Domain\Geolocation;

class Details
{

    protected $id;
    protected $name;
    protected $slug;
    protected $latitude;
    protected $longitude;
    protected $cover_image;
    protected $thumbnail;
    protected $is_selling;
    protected $is_searchable;
    protected $information_text;
    protected $iso;
    protected $tot;
    protected $totAdditional;
    protected $totIncluded;
    protected $totMaxDuration;
    protected $vat;
    protected $vatAdditional;
    protected $vatIncluded;
    protected $vatMaxDuration;
    protected $sales_tax;
    protected $salesTaxAdditional;
    protected $salesTaxIncluded;
    protected $salesTaxMaxDuration;
    protected $city_tax;
    protected $cityTaxAdditional;
    protected $cityTaxIncluded;
    protected $cityTaxMaxDuration;
    protected $location_id;
    protected $parent_id;
    protected $category;
    protected $tot_type;
    protected $vat_type;
    protected $sales_tax_type;
    protected $city_tax_type;
    protected $currency;
    protected $provinceShortName;
    protected $wsShowRightColumn;

    public function exchangeArray($data)
    {
        $this->id                  = (isset($data['id'])) ? $data['id'] : null;
        $this->name                = (isset($data['name'])) ? $data['name'] : null;
        $this->slug                = (isset($data['slug'])) ? $data['slug'] : null;
        $this->latitude            = (isset($data['latitude'])) ? $data['latitude'] : null;
        $this->longitude           = (isset($data['longitude'])) ? $data['longitude'] : null;
        $this->cover_image         = (isset($data['cover_image'])) ? $data['cover_image'] : null;
        $this->thumbnail           = (isset($data['thumbnail'])) ? $data['thumbnail'] : null;
        $this->is_selling          = (isset($data['is_selling'])) ? $data['is_selling'] : null;
        $this->is_searchable       = (isset($data['is_searchable'])) ? $data['is_searchable'] : null;
        $this->information_text    = (isset($data['information_text'])) ? $data['information_text'] : null;
        $this->iso                 = (isset($data['iso'])) ? $data['iso'] : null;
        $this->tot                 = (isset($data['tot'])) ? $data['tot'] : null;
        $this->totAdditional       = (isset($data['tot_additional'])) ? $data['tot_additional'] : null;
        $this->totIncluded         = (isset($data['tot_included'])) ? $data['tot_included'] : null;
        $this->totMaxDuration      = (isset($data['tot_max_duration'])) ? $data['tot_max_duration'] : null;
        $this->vat                 = (isset($data['vat'])) ? $data['vat'] : null;
        $this->vatAdditional       = (isset($data['vat_additional'])) ? $data['vat_additional'] : null;
        $this->vatIncluded         = (isset($data['vat_included'])) ? $data['vat_included'] : null;
        $this->vatMaxDuration      = (isset($data['vat_max_duration'])) ? $data['vat_max_duration'] : null;
        $this->sales_tax           = (isset($data['sales_tax'])) ? $data['sales_tax'] : null;
        $this->salesTaxAdditional  = (isset($data['sales_tax_additional'])) ? $data['sales_tax_additional'] : null;
        $this->salesTaxIncluded    = (isset($data['sales_tax_included'])) ? $data['sales_tax_included'] : null;
        $this->salesTaxMaxDuration = (isset($data['sales_tax_max_duration'])) ? $data['sales_tax_max_duration'] : null;
        $this->city_tax            = (isset($data['city_tax'])) ? $data['city_tax'] : null;
        $this->cityTaxAdditional   = (isset($data['city_tax_additional'])) ? $data['city_tax_additional'] : null;
        $this->cityTaxIncluded     = (isset($data['city_tax_included'])) ? $data['city_tax_included'] : null;
        $this->cityTaxMaxDuration  = (isset($data['city_tax_max_duration'])) ? $data['city_tax_max_duration'] : null;
        $this->location_id         = (isset($data['location_id'])) ? $data['location_id'] : null;
        $this->parent_id           = (isset($data['parent_id'])) ? $data['parent_id'] : null;
        $this->category            = (isset($data['category'])) ? $data['category'] : null;
        $this->tot_type            = (isset($data['tot_type'])) ? $data['tot_type'] : null;
        $this->vat_type            = (isset($data['vat_type'])) ? $data['vat_type'] : null;
        $this->sales_tax_type      = (isset($data['sales_tax_type'])) ? $data['sales_tax_type'] : null;
        $this->city_tax_type       = (isset($data['city_tax_type'])) ? $data['city_tax_type'] : null;
        $this->currency            = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->provinceShortName   = (isset($data['province_short_name'])) ? $data['province_short_name'] : null;
        $this->wsShowRightColumn   = (isset($data['ws_show_right_column'])) ? $data['ws_show_right_column'] : null;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getCityTaxType()
    {
        return $this->city_tax_type;
    }

    public function getSalesTaxType()
    {
        return $this->sales_tax_type;
    }

    public function getVatType()
    {
        return $this->vat_type;
    }

    public function getTotType()
    {
        return $this->tot_type;
    }

    public function getParent_id()
    {
        return $this->parent_id;
    }

    public function setParent_id($val)
    {
        $this->parent_id = $val;
        return $this;
    }

    public function getLocation_id()
    {
        return $this->location_id;
    }

    public function setLocation_id($val)
    {
        $this->location_id = $val;
        return $this;
    }

    public function getCity_tax()
    {
        return $this->city_tax;
    }

    public function setCity_tax($val)
    {
        $this->city_tax = $val;
        return $this;
    }

    public function getSales_tax()
    {
        return $this->sales_tax;
    }

    public function setSales_tax($val)
    {
        $this->sales_tax = $val;
        return $this;
    }

    public function getVat()
    {
        return $this->vat;
    }

    public function setVat($val)
    {
        $this->vat = $val;
        return $this;
    }

    public function getTot()
    {
        return $this->tot;
    }

    public function setTot($val)
    {
        $this->tot = $val;
        return $this;
    }

    public function getIso()
    {
        return $this->iso;
    }

    public function setIso($val)
    {
        $this->iso = $val;
        return $this;
    }

    public function getInformation_text()
    {
        return $this->information_text;
    }

    public function setInformation_text($val)
    {
        $this->information_text = $val;
        return $this;
    }

    public function getIs_searchable()
    {
        return $this->is_searchable;
    }

    public function setIs_searchable($val)
    {
        $this->is_searchable = $val;
        return $this;
    }

    public function getIs_selling()
    {
        return $this->is_selling;
    }

    public function setIs_selling($val)
    {
        $this->is_selling = $val;
        return $this;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setThumbnail($val)
    {
        $this->thumbnail = $val;
        return $this;
    }

    public function getCover_image()
    {
        return $this->cover_image;
    }

    public function setCover_image($val)
    {
        $this->cover_image = $val;
        return $this;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function setLongitude($val)
    {
        $this->longitude = $val;
        return $this;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLatitude($val)
    {
        $this->latitude = $val;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($val)
    {
        $this->name = $val;
        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($val)
    {
        $this->slugx = $val;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($val)
    {
        $this->id = $val;
        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return int|null
     */
    public function getTotIncluded()
    {
        return $this->totIncluded;
    }

    /**
     * @return int|null
     */
    public function getVatIncluded()
    {
        return $this->vatIncluded;
    }

    /**
     * @return int|null
     */
    public function getSalesTaxIncluded()
    {
        return $this->salesTaxIncluded;
    }

    /**
     * @return int|null
     */
    public function getCityTaxIncluded()
    {
        return $this->cityTaxIncluded;
    }

    /**
     * @return string|null
     */
    public function getProvinceShortName()
    {
        return $this->provinceShortName;
    }

    /**
     * @return mixed
     */
    public function getWsShowRightColumn()
    {
        return $this->wsShowRightColumn;
    }

    /**
     * @return mixed
     */
    public function getTotMaxDuration()
    {
        return $this->totMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getVatMaxDuration()
    {
        return $this->vatMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getSalesTaxMaxDuration()
    {
        return $this->salesTaxMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getCityTaxMaxDuration()
    {
        return $this->cityTaxMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getTotAdditional()
    {
        return $this->totAdditional;
    }

    /**
     * @return mixed
     */
    public function getVatAdditional()
    {
        return $this->vatAdditional;
    }

    /**
     * @return mixed
     */
    public function getSalesTaxAdditional()
    {
        return $this->salesTaxAdditional;
    }

    /**
     * @return mixed
     */
    public function getCityTaxAdditional()
    {
        return $this->cityTaxAdditional;
    }

}
