<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


class PluginsExtended extends \Rdb\Modules\RdbAdmin\Libraries\Plugins
{


    public function __set($name, $value)
    {
        $allowedChangeProperties = ['callbackActions', 'callbackFilters', 'pluginsRegisteredHooks'];

        if (in_array($name, $allowedChangeProperties)) {
            $this->{$name} = $value;
        }
    }// __set


    public function getHookIdHash(string $tag, $callback): string
    {
        return parent::getHookIdHash($tag, $callback);
    }// getHookIdHash


}
