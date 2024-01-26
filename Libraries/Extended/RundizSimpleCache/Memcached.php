<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries\Extended\RundizSimpleCache;


use Psr\SimpleCache\CacheInterface;


/**
 * Extended Rundiz\SimpleCache\Drivers\Memcached class.
 * 
 * @since 1.2.9
 */
class Memcached extends \Rundiz\SimpleCache\Drivers\Memcached implements CacheInterface
{


    use \Rundiz\SimpleCache\Drivers\MultipleTrait;


    /**
     * @var string Memcached cache key prefix.
     */
    protected $memcachePrefix = '';


    /**
     * Class constructor
     * 
     * @param \Memcached $Memcached Memcached class.
     * @param string $cachePath This will be use as Memcached cache key prefix.
     * @throws \Exception 
     */
    public function __construct(\Memcached $Memcached, string $cachePath = '')
    {
        parent::__construct($Memcached);

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
        $result = $this->Memcached->get($this->memcachePrefix . $key);

        if ($result === false && $this->Memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            return false;
        }
        return true;
    }// has


    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        return parent::set($this->memcachePrefix . $key, $value, $ttl);
    }// set


}
