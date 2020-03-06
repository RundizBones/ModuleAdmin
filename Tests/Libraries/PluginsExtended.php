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


    public function addHook(string $type, string $tag, $callback, int $priority = 10)
    {
        return parent::addHook($type, $tag, $callback, $priority);
    }// addHook


    public function getHookIdHash(string $tag, $callback): string
    {
        return parent::getHookIdHash($tag, $callback);
    }// getHookIdHash


    public function hasHook(string $type, string $tag, $callback = false)
    {
        return parent::hasHook($type, $tag, $callback);
    }// hasHook


    public function removeAllHooks(string $type, string $tag, $priority = false)
    {
        return parent::removeAllHooks($type, $tag, $priority);
    }// removeAllHooks


    public function removeHook(string $type, string $tag, $callback, int $priority = 10): bool
    {
        return parent::removeHook($type, $tag, $callback, $priority);
    }// removeHook


}
