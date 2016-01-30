<?php

namespace DDD\Service\Website;

use DDD\Service\ServiceBase;

class Cache extends ServiceBase
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
