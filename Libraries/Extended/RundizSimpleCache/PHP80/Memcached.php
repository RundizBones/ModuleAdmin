<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries\Extended\RundizSimpleCache\PHP80;


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
     * Trim out long cache key from the beginning if it is too longer than Memcached can accept (250).
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
        $result = $this->Memcached->get($this->trimLongKey($this->memcachePrefix . $key));

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
        return parent::set($this->trimLongKey($this->memcachePrefix . $key), $value, $ttl);
    }// set


}
