<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Roles;


/**
 * Roles re-order controller.
 * 
 * @since 0.1
 */
class ReorderController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use Traits\RolesTrait;


    /**
     * Update re-order role's priority.
     * 
     * @global array $_PATCH
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminRoles', ['changePriority']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validate csrf token passed.
            $updateData = json_decode($_PATCH['updateData']);

            if (is_array($updateData) && $this->isRestrictedPriority($updateData) === false) {
                // if update roles are not in restricted.
                $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);

                // prepare priority data for checking and re-ordering.
                $priorities = [];
                foreach ($this->selectedRoles as $eachRole) {
                    $priorities[(string) $eachRole->userrole_id] = $eachRole->userrole_priority;
                }// endforeach;
                unset($eachRole);

                // validate that number of priorities matched number of re-order items.
                if (count($updateData) !== count($priorities)) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('There are problems with your items, please reload the page and try again.');
                    if (defined('APP_ENV') && APP_ENV === 'development') {
                        $output['debug_ids'] = $updateData;
                        $output['debug_priorities'] = $priorities;
                    }
                    http_response_code(400);
                    $formValidated = false;
                } else {
                    $formValidated = true;
                }

                // do update data to db.
                if ($formValidated === true) {
                    // if form validation passed.
                    // re-order the priority from low to high (user roles displaying use low to high).
                    asort($priorities);
                    $iUpdated = 0;
                    foreach ($updateData as $userrole_id) {
                        $data = [];
                        $where = [];

                        $where['userrole_id'] = $userrole_id;
                        $data['userrole_priority'] = current($priorities);

                        $updateResult = $UserRolesDb->update($data, $where);
                        if ($updateResult === true) {
                            $iUpdated++;
                        }
                        unset($updateResult);

                        next($priorities);
                    }// endforeach;
                    unset($data, $userrole_id, $where);

                    if ($iUpdated === count($updateData)) {
                        $output['formResultStatus'] = 'success';
                        $output['formResultMessage'] = __('Updated successfully.');
                    } else {
                        $output['formResultStatus'] = 'warning';
                        $output['formResultMessage'] = __('Some of items were updated successfully.');
                    }
                    $output['updated'] = true;
                    unset($iUpdated);
                }// endif;

                unset($priorities, $UserRolesDb);
            } else {
                // if update roles are in restricted.
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to update restricted roles.');
                http_response_code(400);
            }

            unset($updateData);
        } else {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to validate token, please try again. If this problem still occur please reload the page and try again.');
            http_response_code(400);
        }

        unset($csrfName, $csrfValue);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// indexAction


}
