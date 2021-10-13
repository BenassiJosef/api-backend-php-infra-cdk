<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 03/05/2017
 * Time: 21:55
 */

namespace App\Utils;

use Doctrine\Common\Cache\PredisCache;
use Predis\Client;

class CacheEngine
{
    protected $cache;
    protected $life;

    public function __construct(string $redisUrl, int $connectionTimeout = 30)
    {
        $redis       = new Client($redisUrl, [
            'read_write_timeout' => -1,
            'connection_timeout' => $connectionTimeout
        ]);
        $cacheDriver = new PredisCache($redis);
        $this->cache = $cacheDriver;
        $this->life  = 3600;
    }

    public function save(string $path, array $dataset)
    {
        return $this->cache->save($path, $dataset, $this->life);
    }

    public function saveMultiple(array $keysAndValues)
    {
        return $this->cache->saveMultiple($keysAndValues, $this->life);
    }

    /**
     * @param string $path
     * @return false|mixed
     */

    public function fetch(string $path)
    {
        return $this->cache->fetch($path);
    }

    public function delete(string $path)
    {
        return $this->cache->delete($path);
    }

    public function deleteMultiple(array $keys)
    {
        return $this->cache->deleteMultiple($keys);
    }

    public function contains(string $path)
    {
        return $this->cache->contains($path);
    }

    public function calculateLifetime(int $digits = 3600)
    {
        $this->life = $digits;
    }

    public function getStats() {
        return $this->cache->getStats();
    }
}
