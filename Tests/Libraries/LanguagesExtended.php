<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


class LanguagesExtended extends \Rdb\Modules\RdbAdmin\Libraries\Languages
{


    /**
     * Let test class access protected properties.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->{$name};
    }// __get


    /**
     * {@inheritDoc}
     */
    public function registerTextDomain(string $domain, string $directory)
    {
        return parent::registerTextDomain($domain, $directory);
    }// registerTextDomain


}
