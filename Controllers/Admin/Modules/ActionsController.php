<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Modules;


/**
 * Modules actions controller.
 * 
 * @since 1.2.5
 */
class ActionsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\ModulesTrait;


    /**
     * Do update action on selected modules.
     * 
     * @return string
     */
    public function doUpdateAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminModules', ['manageModules']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make patch data into $_PATCH variable.
        $this->Input->patch('');
        global $_PATCH;

        if (
            isset($_PATCH[$csrfName]) &&
            isset($_PATCH[$csrfValue]) &&
            $Csrf->validateToken($_PATCH[$csrfName], $_PATCH[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            if ($this->Container->has('Modules')) {
                /* @var $Modules \Rdb\System\Modules */
                $Modules = $this->Container->get('Modules');
            } else {
                $Modules = new \Rdb\System\Modules($this->Container);
            }
            $currentModule = $Modules->getCurrentModule();

            $output = array_merge($output, $this->validateActions($_PATCH['module_ids'], $_PATCH['modulesystemnames'], $currentModule, $_PATCH['action']));
            unset($_PATCH);

            if (isset($output['actionsFormOk']) && $output['actionsFormOk'] === true) {
                if (isset($output['moduleSystemNameArray']) && is_array($output['moduleSystemNameArray'])) {
                    foreach ($output['moduleSystemNameArray'] as $mname) {
                        if (isset($output['action']) && $output['action'] === 'enable') {
                            $Modules->enable($mname);
                        } elseif (isset($output['action']) && $output['action'] === 'disable') {
                            $Modules->enable($mname, false);
                        }
                    }// endforeach;
                    unset($mname);
                }

                $output['formResultStatus'] = 'success';
                $output['formResultMessage'] = __('Updated successfully.');
                $output['updated'] = true;
            } else {
                // if form validation failed.
                // it was already send out http response code and message was set, nothing to do here.
            }

            unset($currentModule, $Modules);
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
    }// doUpdateAction


    /**
     * Validate form actions.
     * 
     * This method was called from `doUpdateAction()` method.
     * 
     * @param string $module_ids The selected module IDs as string (from the form).
     * @param string $modulesystemnames The selected module system names as string (from the from).
     * @param string $currentModule Current module folder name (usually RdbAdmin).
     * @param string $action The selected action.
     * @return array Return associative array with keys:<br>
     *                          `action` The selected action.<br>
     *                          `module_ids` The selected module IDs.<br>
     *                          `formResultStatus` (optional) If contain any error, it also send out HTTP response code.<br>
     *                          `formResultMessage` (optional) If contain any error, it also send out HTTP response code.<br>
     *                          `moduleIdsArray` The module ids in array.<br>
     *                          `moduleSystemNameArray` The module system names in array.<br>
     *                          `actionsFormOk` The boolean value of form validation. It will be `true` if form validation passed, and will be `false` if it is not.
     */
    protected function validateActions(string $module_ids, string $modulesystemnames, string $currentModule, string $action): array
    {
        $output = [];

        $output['action'] = $action;
        $output['module_ids'] = $module_ids;
        $output['moduleSystemNameArray'] = explode(',', $modulesystemnames);
        $expModuleIds = explode(',', $output['module_ids']);
        $actionsFormOk = false;// if modules and action were selected, it will be true.
        $allowedActions = ['enable', 'disable'];// select action values in array.

        // validate selected module and action. ------------------------------
        if (count($expModuleIds) <= 0) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select at least one module.');
        } else {
            $actionsFormOk = true;
        }
        foreach ($output['moduleSystemNameArray'] as $mname) {
            if (strtolower($mname) === strtolower($currentModule)) {
                http_response_code(400);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = sprintf(__('You couldn\'t modify current module via web based (%1$s).'), $currentModule);
                $actionsFormOk = false;
                break;
            }
        }// endforeach;
        unset($mname);
        if (empty($output['action']) || !in_array($output['action'], $allowedActions)) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select an action.');
            $actionsFormOk = false;
        }
        unset($allowedActions);
        // end validate selected plugins and action. --------------------------

        $output['actionsFormOk'] = $actionsFormOk;
        $output['moduleIdsArray'] = $expModuleIds;

        unset($actionsFormOk, $expModuleIds);

        return $output;
    }// validateActions


}
