<?php

namespace Library\ViewHelper\Textline;

class Universal extends TextlineBase
{
    public function __invoke($id = false, $clean = false)
    {
        if ($id > 0) {
            if ($clean) {
                return $this->getFromDatabase($id, $clean);
            }

            return $this->getFromCache($id);
        }

        return false;
    }

    public function getFromCache($id)
    {
        if ($this->cacheService->get($id) !== NULL) {
            return $this->cacheService->get($id);
        }

        return $this->setCache($id, $this->getFromDatabase($id));
    }

    public function setCache($id, $value)
    {
        $this->cacheService->set($id, $value);

        return $this->getFromCache($id);
    }

    public function getFromDatabase($id, $clean = false)
    {
        return $this->textlineService->getUniversalTextline($id, $clean);
    }
}
