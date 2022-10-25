<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Modules\Traits;


/**
 * Modules trait.
 * 
 * @since 1.2.5
 */
trait ModulesTrait
{


    /**
     * Get URLs and methods about module's management pages.
     * 
     * @return array Return associative array.
     */
    protected function getModuleUrlsMethods(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['actionModulesRESTUrl'] = $urlAppBased . '/admin/modules/actions';// actions via REST API.
        $output['actionModulesRESTMethod'] = 'PATCH';

        $output['getModulesRESTUrl'] = $urlAppBased . '/admin/modules';// get modules via REST API.
        $output['getModulesRESTMethod'] = 'GET';

        unset($Url, $urlAppBased);

        return $output;
    }// getModuleUrlsMethods


}
