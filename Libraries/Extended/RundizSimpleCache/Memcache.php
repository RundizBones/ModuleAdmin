<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries\Extended\RundizSimpleCache;


use Psr\SimpleCache\CacheInterface;


/**
 * Extended Rundiz\SimpleCache\Drivers\Memcache class.
 * 
 * @since 1.2.9
 */
class Memcache extends \Rundiz\SimpleCache\Drivers\Memcache implements CacheInterface
{


    use \Rundiz\SimpleCache\Drivers\MultipleTrait;


    /**
     * @var string Memcache cache key prefix.
     */
    protected $memcachePrefix = '';


    /**
     * Class constructor
     * 
     * @param string $cachePath This will be use as Memcache cache key prefix.
     * @throws \Exception 
     */
    public function __construct(string $cachePath = '')
    {
        if (is_string($cachePath) && $cachePath !== '') {
            $cachePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $cachePath);
            $this->memcachePrefix = str_replace(ROOT_PATH . DIRECTORY_SEPARATOR, '', $cachePath) . '__';
        }
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function delete($key): bool
    {
        return parent::delete($this->memcachePrefix . $key);
    }// delete


    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        return parent::get($this->memcachePrefix . $key, $default);
    }// get


    /**
     * {@inheritDoc}
     */
    public function has($key): bool
    {
        return parent::has($this->memcachePrefix . $key);
    }// has


    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        return parent::set($this->memcachePrefix . $key, $value, $ttl);
    }// set


}
