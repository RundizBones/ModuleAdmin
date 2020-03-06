<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


class PluginsExtended extends \Rdb\Modules\RdbAdmin\Libraries\Plugins
{


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


}
