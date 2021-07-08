<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Modules\Plugins\Traits;


/**
 * Plugins trait.
 * 
 * @since 0.2.4
 */
trait PluginsTrait
{


    /**
     * Get URLs and methods about module's plugin pages.
     * 
     * @return array Return associative array.
     */
    protected function getPluginUrlsMethods(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['actionPluginsRESTUrl'] = $urlAppBased . '/admin/modules/plugins/actions';// actions via REST API.
        $output['actionPluginsRESTMethod'] = 'PATCH';

        $output['getPluginsRESTUrl'] = $urlAppBased . '/admin/modules/plugins';// get modules plugins via REST API.
        $output['getPluginsRESTMethod'] = 'GET';

        unset($Url, $urlAppBased);

        return $output;
    }// getPluginUrlsMethods


}
