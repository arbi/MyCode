<?php

namespace Website\View\Helper;

use Website\View\Helper\BaseHelper;

class CityName extends BaseHelper
{
    public function __invoke($id = FALSE, $lang = FALSE)
    {
        if (!$id) {
            return FALSE;
        }
        
        if ($lang) {
            $this->language = $lang;
        }
        
        return $this->getFromCache($id);
    }
    
    public function getFromCache($id)
    {
        if ($this->cacheService->get('city-'.$id.'-'.$this->language) !== NULL) {
            return $this->cacheService->get('city-'.$id.'-'.$this->language);
        }

        return $this->setCache($id, $this->getFromDatabase($id));
    }
    
    public function setCache($id, $value)
    {
        if (is_null($value)) {
            throw new \InvalidArgumentException('the "value" argument can not be null');
        }

        $this->cacheService->set('city-'.$id.'-'.$this->language, $value);

        return $this->getFromCache($id);
    }
    
    public function getFromDatabase($id)
    {
        return $this->textlineService->getCityName($id, $this->language);
    }
}