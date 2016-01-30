<?php

namespace Website\View\Helper;

use Website\View\Helper\BaseHelper;

class Textline extends BaseHelper
{
    public function __invoke($id, $lang = 'en')
    {
        if ($lang) {
            $this->language = $lang;
        }

        return $this->getFromCache($id);
    }

    public function getFromCache($id)
    {
        if ($this->cacheService->get($id.'-'.$this->language) !== NULL) {
            return $this->cacheService->get($id.'-'.$this->language);
        }

        return $this->setCache($id, $this->getFromDatabase($id));
    }

    public function setCache($id, $value)
    {
        $this->cacheService->set($id.'-'.$this->language, $value);

        return $this->getFromCache($id);
    }

    public function getFromDatabase($id)
    {
        return $this->textlineService->getUniversalTextline($id);
    }
}
