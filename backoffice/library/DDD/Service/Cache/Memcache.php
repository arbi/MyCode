<?php

namespace DDD\Service\Cache;

use DDD\Service\ServiceBase;


class Memcache extends ServiceBase
{
    public function set($key, $val)
    {
        $sl = $this->getServiceLocator();
        $sl->get('memcached')->setItem($key, $val);
    }

    public function get($key)
    {
        $sl = $this->getServiceLocator();
        return $sl->get('memcached')->getItem($key);
    }

    public function has($key)
    {
        $sl = $this->getServiceLocator();
        return $sl->get('memcached')->hasItem($key);
    }
}