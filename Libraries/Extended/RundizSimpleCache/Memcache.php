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
     * @param \Memcache $memcache Memcache class.
     * @param string $cachePath This will be use as Memcache cache key prefix.
     * @throws \Exception 
     */
    public function __construct(\Memcache $memcache, string $cachePath = '')
    {
        parent::__construct($memcache);

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
        $flags = false;
        $this->Memcache->get($this->memcachePrefix . $key, $flags);
        return ($flags !== false);
    }// has


    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        return parent::set($this->memcachePrefix . $key, $value, $ttl);
    }// set


}
