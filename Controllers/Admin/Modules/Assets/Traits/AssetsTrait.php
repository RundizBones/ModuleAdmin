<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Modules\Assets\Traits;


/**
 * Module's assets trait.
 * 
 * @since 1.1.8
 */
trait AssetsTrait
{


    /**
     * Get module's assets URLs and methods.
     * 
     * @return string Return associative array.
     */
    protected function getMAssetsUrlsMethods()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['getAssetsUrl'] = $urlAppBased . '/admin/modules/assets';
        $output['getAssetsRESTUrl'] = $output['getAssetsUrl'];
        $output['getAssetsRESTMethod'] = 'GET';

        $output['publishAssetsRESTUrl'] = $output['getAssetsUrl'];
        $output['publishAssetsRESTMethod'] = 'POST';

        unset($Url, $urlAppBased);

        return $output;
    }// getMAssetsUrlsMethods


}
