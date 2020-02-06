<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Controllers\Admin\Permissions\Traits;


/**
 * Permissions trait.
 * 
 * @since 0.1
 */
trait PermissionsTrait
{


    /**
     * Get URLs and methods about permissions pages.
     * 
     * @return array Return associative array.
     */
    protected function getPermissionUrlsMethods(): array
    {
        $Url = new \System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['clearPermissionsSubmitUrlBase'] = $urlAppBased . '/admin/permissions';
        $output['clearPermissionsSUbmitMethod'] = 'DELETE';

        $output['editPermissionSubmitUrl'] = $urlAppBased . '/admin/permissions';// edit permission form submit via rest api.
        $output['editPermissionSubmitMethod'] = 'PATCH';

        $output['getPermissionsUrl'] = $urlAppBased . '/admin/permissions';// list permissions page, get permissions via rest api. (roles, user, pages, actions, checked data).
        $output['getPermissionsMethod'] = 'GET';

        return $output;
    }// getPermissionUrlsMethods


}
