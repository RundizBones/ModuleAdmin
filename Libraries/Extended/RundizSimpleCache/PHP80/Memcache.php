<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries\Extended\RundizSimpleCache\PHP80;


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
     * Trim out long cache key from the beginning if it is too longer than Memcache can accept (250).
     * 
     * @param string $key The cache key included prefix.
     * @return string Return trimmed cache key from the beginning.
     */
    private function trimLongKey($key): string
    {
        if (strlen($key) >= 249) {
            if (mb_strlen($key) !== strlen($key)) {
                $key = md5($key);
            } else {
                $key = substr($key, strlen($key) - 249);
            }
        }

        return $key;
    }// trimLongKey


    /**
     * {@inheritDoc}
     */
    public function delete($key): bool
    {
        return parent::delete($this->trimLongKey($this->memcachePrefix . $key));
    }// delete


    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null): mixed
    {
        return parent::get($this->trimLongKey($this->memcachePrefix . $key), $default);
    }// get


    /**
     * {@inheritDoc}
     */
    public function has($key): bool
    {
        $flags = false;
        $this->Memcache->get($this->trimLongKey($this->memcachePrefix . $key), $flags);
        return ($flags !== false);
    }// has


    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        return parent::set($this->trimLongKey($this->memcachePrefix . $key), $value, $ttl);
    }// set


}
