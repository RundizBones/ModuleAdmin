<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries\Extended\RundizSimpleCache;


use Psr\SimpleCache\CacheInterface;


/**
 * Extended Rundiz\SimpleCache\Drivers\Apcu class.
 * 
 * @since 1.2.9
 */
class Apcu extends \Rundiz\SimpleCache\Drivers\Apcu implements CacheInterface
{


    use \Rundiz\SimpleCache\Drivers\MultipleTrait;


    /**
     * @var string APCu cache key prefix.
     */
    protected $apcuPrefix = '';


    /**
     * Class constructor
     * 
     * @param string $cachePath This will be use as APCu cache key prefix.
     * @throws \Exception 
     */
    public function __construct(string $cachePath = '')
    {
        if (is_string($cachePath) && $cachePath !== '') {
            $cachePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $cachePath);
            $this->apcuPrefix = str_replace(ROOT_PATH . DIRECTORY_SEPARATOR, '', $cachePath) . '__';
        }
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function delete($key): bool
    {
        return parent::delete($this->apcuPrefix . $key);
    }// delete


    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        return parent::get($this->apcuPrefix . $key, $default);
    }// get


    /**
     * {@inheritDoc}
     */
    public function has($key): bool
    {
        return parent::has($this->apcuPrefix . $key);
    }// has


    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        return parent::set($this->apcuPrefix . $key, $value, $ttl);
    }// set


}
