<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Libraries;


/**
 * Cache class.
 * 
 * @since 0.1
 */
class Cache
{


    /**
     * @var \Psr\SimpleCache\CacheInterface The cache object.
     */
    protected $Cache;


    /**
     * @var \System\Container
     */
    protected $Container;


    /**
     * @var string Contain current cache driver.
     */
    protected $driver;


    /**
     * Class constructor.
     * 
     * @param \System\Container $Container The DI container.
     * @param array $options Additional options or configuration in associative array.
     *                                      For file system, it accept 'cachePath', 'umask' array keys.
     */
    public function __construct(\System\Container $Container, array $options = [])
    {
        if ($Container instanceof \System\Container) {
            $this->Container = $Container;
        }

        /* @var $Config \System\Config */
        if ($this->Container->has('Config')) {
            $Config = $this->Container->get('Config');
        } else {
            $Config = new \System\Config();
        }
        $Config->setModule('RdbAdmin');

        $driver = $Config->get('driver', 'cache', 'filesystem');

        if ($driver === 'apcu' && function_exists('apcu_fetch')) {
            $this->Cache = new \Rundiz\SimpleCache\Drivers\Apcu();
            $this->driver = $driver;
        } elseif ($driver === 'memcache' || $driver === 'memcached') {
            $MemcacheObject = $Config->get('memcacheConfig', 'cache', null);
            if (is_object($MemcacheObject) && method_exists($MemcacheObject, 'getStats')) {
                try {
                    $result = $MemcacheObject->getStats();
                    if ($driver === 'memcache' && $result !== false) {
                        $this->Cache = new \Rundiz\SimpleCache\Drivers\Memcache($MemcacheObject);
                        $this->driver = $driver;
                    } elseif ($driver === 'memcached') {
                        foreach ($result as $key => $item) {
                            if (isset($item['pid']) && $item['pid'] > 0) {
                                $this->Cache = new \Rundiz\SimpleCache\Drivers\Memcached($MemcacheObject);
                                $this->driver = $driver;
                                break;
                            }
                        }// endforeach;
                        unset($item, $key);
                    }
                    unset($result);
                } catch (\Exception $ex) {
                    // memcache, or memcached failed to connect.
                }
            }
            unset($MemcacheObject);
        }

        // fallback or filesystem cache.
        if (is_null($this->Cache) || !is_object($this->Cache) || $driver === 'filesystem') {
            if (isset($options['cachePath'])) {
                $cachePath = $options['cachePath'];
            } else {
                $cachePath = '';
            }
            if (isset($options['umask'])) {
                $umask = (int) $options['umask'];
            } else {
                $umask = 0002;
            }
            $this->Cache = new \Rundiz\SimpleCache\Drivers\FileSystem($cachePath, $umask);
            $this->driver = 'filesystem';
            unset($cachePath, $umask);
        }

        unset($Config, $driver);
        return $this->Cache;
    }// __construct


    /**
     * Magic get.
     * 
     * @param string $name Property name.
     * @return mixed Return its value depend on property.
     */
    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
    }// __get


    /**
     * Get cache object to continue using simple cache.
     * 
     * @return \Psr\SimpleCache\CacheInterface
     */
    public function getCacheObject(): \Psr\SimpleCache\CacheInterface
    {
        return $this->Cache;
    }// getCacheObject


}
