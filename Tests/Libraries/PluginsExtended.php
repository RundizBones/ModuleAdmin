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


    public function getHookIdHash(string $tag, $callback, int $priority): string
    {
        return parent::getHookIdHash($tag, $callback, $priority);
    }// getHookIdHash


}
