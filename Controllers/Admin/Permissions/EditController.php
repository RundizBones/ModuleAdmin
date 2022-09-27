<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Permissions;


/**
 * Edit permission controller
 * 
 * @since 0.1
 */
class EditController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\PermissionsTrait;


    /**
     * Clear permissions for selected module.
     * 
     * @global array $_DELETE
     * @param string $module_system_name
     * @return string
     */
    public function doClearAction(string $module_system_name): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminPermissions', ['managePermissions']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf(['persistentTokenMode' => true]);

        $output = [];
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make delete data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if csrf token validate passed.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            // prepare data
            $data = [];
            $data['module_system_name'] = $module_system_name;

            // form validation.
            $formValidated = true;
            if (empty($data['module_system_name'])) {
                $formValidated = false;
                $output['alertDialog'] = true;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to clear permissions for selected module, please reload the page and try again.');
                http_response_code(400);
            }

            if ($formValidated === true) {
                $output = array_merge($output, $this->doUpdateDelete($data));
                if (isset($output['deleted']) && $output['deleted'] === true) {
                    $output['cleared'] = true;
                    $output['alertDialog'] = true;
                    $output['formResultMessage'] = sprintf(__('All permissions for %1$s module were cleared successfully.'), strip_tags($module_system_name));
                    http_response_code(200);
                }
            }
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
        unset($Csrf);
        return $this->responseAcceptType($output);
    }// doClearAction


    /**
     * Update permission (insert on checked, delete on unchecked).
     * 
     * @global array $_PATCH
     * @return string
     */
    public function doUpdateAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminPermissions', ['managePermissions']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf(['persistentTokenMode' => true]);

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
            // if csrf token validate passed.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            // prepare data
            $data = [];
            if (isset($_PATCH['userrole_id']) && $this->Input->patch('permissionFor') === 'roles') {
                $data['userrole_id'] = $this->Input->patch('userrole_id');
                if (is_array($data['userrole_id'])) {
                    $data['userrole_id'] = reset($data['userrole_id']);
                }
            }
            if (isset($_PATCH['user_id']) && $this->Input->patch('permissionFor') === 'users') {
                $data['user_id'] = $this->Input->patch('user_id');
                if (is_array($data['user_id'])) {
                    $data['user_id'] = reset($data['user_id']);
                }
            }
            $data['module_system_name'] = $this->Input->patch('module_system_name');
            $data['permission_page'] = $this->Input->patch('permission_page');
            $data['permission_action'] = $this->Input->patch('permission_action');
            if ($this->Input->patch('checked') === 'true') {
                $processMethod = 'insert';
            } elseif ($this->Input->patch('checked') === 'false') {
                $processMethod = 'delete';
            }

            // form validation.
            $formValidated = true;
            if (!isset($data['userrole_id']) && !isset($data['user_id'])) {
                $formValidated = false;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Could not update. Please reload the page and try again, if problem still exists please contact administrator.');
                http_response_code(400);
                // also log error because this is not normal.
                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $Logger->write('modules/rdbadmin/controllers/admin/permissions/editcontroller', 4, 'Could not update, no \'userrole_id\' or \'user_id\' were found. PATCH data is {patchData}', ['patchData' => $_PATCH]);
                    unset($Logger);
                }
            } elseif (!isset($processMethod)) {
                $formValidated = false;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Could not update. Please reload the page and try again, if problem still exists please contact administrator.');
                http_response_code(400);
                // also log error because this is not normal.
                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $Logger->write('modules/rdbadmin/controllers/admin/permissions/editcontroller', 4, 'Could not update, no \'checked\' were found. PATCH data is {patchData}', ['patchData' => $_PATCH]);
                    unset($Logger);
                }
            }

            if ($formValidated === true) {
                if ($processMethod === 'insert') {
                    $output = array_merge($output, $this->doUpdateInsert($data));
                } elseif ($processMethod === 'delete') {
                    $output = array_merge($output, $this->doUpdateDelete($data));
                }
            }
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
        unset($Csrf);
        return $this->responseAcceptType($output);
    }// doUpdateAction


    /**
     * Do delete unchecked permission.
     * 
     * This method may response http status code.<br>
     * This method was called from `doUpdateAction()` method.
     * 
     * @param array $data The associative array where key is column and value is its value.
     * @return array Return form result status and message if success or error.
     */
    protected function doUpdateDelete(array $data): array
    {
        $output = [];
        $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
        try {
            $deleteResult = $UserPermissionsDb->delete($data);
        } catch (\Exception $e) {
            $output['exceptionMessage'] = $e->getMessage();
        }
        unset($UserPermissionsDb);

        if (isset($deleteResult) && $deleteResult === true) {
            $Sth = $this->Db->PDOStatement();
            $output['rowsAffected'] = $Sth->rowCount();
            $output['deleted'] = true;
            $output['formResultStatus'] = 'success';
            $output['formResultMessage'] = __('Deleted successfully.');

            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
                $Logger->write('modules/rdbadmin/controllers/admin/permissions', 0, 'permission deleted', ['data' => $data, 'result' => $output]);
                unset($Logger);
            }

            http_response_code(204);
            unset($Sth);
        } else {
            $output['deleted'] = false;
            $output['formResultStatus'] = 'warning';
            $output['formResultMessage'] = __('Unable to delete.');
            if (isset($output['exceptionMessage'])) {
                $output['formResultMessage'] .= '<br>' . PHP_EOL . $output['exceptionMessage'];
            }
            http_response_code(500);
        }

        return $output;
    }// doUpdateDelete


    /**
     * Do insert checked permission but check for not exists before.
     * 
     * This method may response http status code.<br>
     * This method was called from `doUpdateAction()` method.
     * 
     * @param array $data The associative array where key is column and value is its value.
     * @return array Return form result status and message if success or error.
     */
    protected function doUpdateInsert(array $data): array
    {
        $output = [];
        $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
        $permissionRow = $UserPermissionsDb->get($data);
        if (is_object($permissionRow) && !empty($permissionRow)) {
            // if already exists.
            $output['exists'] = true;
            http_response_code(200);
        } else {
            // if not already exists.
            try {
                $addResult = $UserPermissionsDb->add($data);
            } catch (\Exception $e) {
                $output['exceptionMessage'] = $e->getMessage();
            }

            if (isset($addResult) && $addResult !== false) {
                // if added success.
                $output['permission_id'] = $addResult;

                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $Logger->write('modules/rdbadmin/controllers/admin/permissions', 0, 'permission inserted', ['data' => $data, 'result' => $output]);
                    unset($Logger);
                }

                http_response_code(201);
            } else {
                // if add failed.
                $output['formResultStatus'] = 'warning';
                $output['formResultMessage'] = __('Unable to set permission.');
                if (isset($output['exceptionMessage'])) {
                    $output['formResultMessage'] .= '<br>' . PHP_EOL . $output['exceptionMessage'];
                }
                http_response_code(500);
            }
        }
        unset($permissionRow, $UserPermissionsDb);

        return $output;
    }// doUpdateInsert


}
