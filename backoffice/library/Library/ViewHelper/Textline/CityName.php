<?php

namespace Library\ViewHelper\Textline;


class CityName extends TextlineBase
{
    public function __invoke($id = false)
    {
        if ($id > 0) {
            return $this->getFromCache($id);
        }

        return false;
    }

    public function getFromCache($id)
    {
        if ($this->cacheService->get('city-' . $id) !== NULL) {
            return $this->cacheService->get('city-' . $id);
        }

        return $this->setCache($id, $this->getFromDatabase($id));
    }

    public function setCache($id, $value)
    {
        if (is_null($value)) {
            throw new \InvalidArgumentException('the "value" argument can not be null');
        }

        $this->cacheService->set('city-' . $id, $value);

        return $this->getFromCache($id);
    }

    public function getFromDatabase($id)
    {
        return $this->textlineService->getCityName($id);
    }
}
