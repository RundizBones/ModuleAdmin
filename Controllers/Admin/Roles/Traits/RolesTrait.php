<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Roles\Traits;


/**
 * Roles trait.
 */
trait RolesTrait
{


    /**
     * @var array Selected roles. This property can access after called to `isRestrictedPriority()` method. It can be use to loop each row immediately.
     */
    protected $selectedRoles = [];


    /**
     * Get role URLs and methods about role pages.
     * 
     * @param string $userrole_id The role ID.
     * @return array Return associative array.
     */
    protected function getRoleUrlsMethods($userrole_id = ''): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $urlAppBased = $Url->getAppBasedPath(true);

        $output = [];

        $output['actionsRolesUrl'] = $urlAppBased . '/admin/roles/actions';// bulk actions confirmation page.

        $output['addRolePageUrl'] = $urlAppBased . '/admin/roles/add';// add role page.
        $output['addRoleSubmitUrl'] = $urlAppBased . '/admin/roles';// add role form submit via rest api.
        $output['addRoleMethod'] = 'POST';

        $output['deleteRolesUrlBase'] = $urlAppBased . '/admin/roles';// delete role form submit via rest api.
        $output['deleteRolesMethod'] = 'DELETE';

        if (is_numeric($userrole_id)) {
            $output['editRolePageUrl'] = $urlAppBased . '/admin/roles/edit/' . $userrole_id;// edit role page (with userrole_id).
        }
        $output['editRolePageUrlBase'] = $urlAppBased . '/admin/roles/edit';// edit role page.
        $output['editRoleSubmitUrlBase'] = $urlAppBased . '/admin/roles';// edit role form submit via rest api.
        $output['editRoleMethod'] = 'PATCH';

        $output['getRolesUrl'] = $urlAppBased . '/admin/roles';// get roles via rest api.
        $output['getRolesMethod'] = 'GET';
        $output['getRoleUrlBase'] = $urlAppBased . '/admin/roles';// get a single role via rest api.
        $output['getRoleMethod'] = 'GET';

        $output['reorderSubmitUrl'] = $urlAppBased . '/admin/roles/reorder';// submit reorder via rest api.
        $output['reorderMethod'] = 'PATCH';

        unset($Url, $urlAppBased);

        return $output;
    }// getRoleUrlsMethods


    /**
     * Check if the data that will be update or delete is in restricted priority.
     * 
     * @param array $userrole_ids The list of user role IDs. This can be array of IDs that was re-ordered.
     * @return bool Return `true` if yes (cannot update), `false` if not (can update).
     */
    protected function isRestrictedPriority(array $userrole_ids): bool
    {
        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);

        $options = [];
        $options['roleIdsIn'] = $userrole_ids;
        $options['unlimited'] = true;
        $selectedRoles = $UserRolesDb->listItems($options);
        unset($options);

        if (isset($selectedRoles['items']) && is_array($selectedRoles['items'])) {
            // set the query result into array by the same order as selected.
            $newSelectedRoles = [];
            foreach ($userrole_ids as $userrole_id) {
                foreach ($selectedRoles['items'] as $eachRole) {
                    if ($eachRole->userrole_id == $userrole_id) {
                        $newSelectedRoles[] = $eachRole;
                    }
                }// endforeach;
                unset($eachRole);
            }// endforeach;
            unset($userrole_id);

            $this->selectedRoles = $newSelectedRoles;
            unset($newSelectedRoles);

            // now loop check that it is in restricted or not.
            foreach ($selectedRoles['items'] as $eachRole) {
                if (in_array((int) $eachRole->userrole_priority, $UserRolesDb->restrictedPriority)) {
                    return true;
                }
            }// endforeach;
            unset($eachRole);
        }

        unset($selectedRoles, $UserRolesDb);

        return false;
    }// isRestrictedPriority


}
