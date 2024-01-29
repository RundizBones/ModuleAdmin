<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Cache class.
 * 
 * @since 0.1
 * @property-read string $driver Contain current cache driver.
 */
class Cache
{


    /**
     * @var \Psr\SimpleCache\CacheInterface The cache object.
     */
    protected $Cache;


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var string Contain current cache driver.
     */
    protected $driver;


    /**
     * Class constructor.
     * 
     * Example usage:<pre>
     * $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
     *     $this->Container, 
     *     [
     *         'cachePath' => STORAGE_PATH . '/cache/Modules/YOUR_MODULE/YOUR_PATH',
     *     ]
     * ))->getCacheObject();
     * </pre>
     * 
     * @param \Rdb\System\Container $Container The DI container.
     * @param array $options Additional options or configuration in associative array with keys:<br>
     *              `cachePath` (string) The file system cache path or incase of APCu, Memcache, Memcached it will be prefix for the cache key.<br>
     *              `umask` (int) The file system cache `umask` option.<br>
     */
    public function __construct(\Rdb\System\Container $Container, array $options = [])
    {
        if ($Container instanceof \Rdb\System\Container) {
            $this->Container = $Container;
        }

        /* @var $Config \Rdb\System\Config */
        if ($this->Container->has('Config')) {
            $Config = $this->Container->get('Config');
        } else {
            $Config = new \Rdb\System\Config();
        }
        $Config->setModule('RdbAdmin');

        $driver = $Config->get('driver', 'cache', 'filesystem');

        if ($driver === 'apcu' && function_exists('apcu_fetch')) {
            $this->Cache = new Extended\RundizSimpleCache\Apcu(($options['cachePath'] ?? ''));
            $this->driver = $driver;
        } elseif ($driver === 'memcache' || $driver === 'memcached') {
            $MemcacheObject = $Config->get('memcacheConfig', 'cache', null);
            if (is_object($MemcacheObject) && method_exists($MemcacheObject, 'getStats')) {
                try {
                    $result = $MemcacheObject->getStats();
                    if ($driver === 'memcache' && $result !== false) {
                        $this->Cache = new Extended\RundizSimpleCache\Memcache($MemcacheObject, ($options['cachePath'] ?? ''));
                        $this->driver = $driver;
                    } elseif ($driver === 'memcached') {
                        foreach ($result as $key => $item) {
                            if (isset($item['pid']) && $item['pid'] > 0) {
                                $this->Cache = new Extended\RundizSimpleCache\Memcached($MemcacheObject, ($options['cachePath'] ?? ''));
                                $this->driver = $driver;
                                break;
                            }
                        }// endforeach;
                        unset($item, $key);
                    }
                    unset($result);
                } catch (\Exception|\Error $err) {
                    // In this case, it means there is a problem with configuration. Developers need attention about this.
                    error_log($err);
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

        // always restore config module to default
        $Config->setModule('');

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
        $allowedAccessProps = ['Cache', 'driver'];

        if (in_array($name, $allowedAccessProps) && isset($this->{$name})) {
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
