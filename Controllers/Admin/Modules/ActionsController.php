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
     * Doing a selected action on selected modules.
     * 
     * @since 1.2.5 It was `doUpdateAction()`, renamed to `doActionsAction()`.
     * @return string
     */
    public function doActionsAction(): string
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
                        } elseif (isset($output['action']) && $output['action'] === 'update') {
                            $updateResult = $this->doUpdateModule($mname);
                            if (isset($updateResult['formResultStatus']) && $updateResult['formResultStatus'] !== 'success') {
                                $output['formResultStatus'] = $updateResult['formResultStatus'];
                                if (isset($updateResult['formResultMessage'])) {
                                    if (!isset($output['formResultMessage']) || !is_array($output['formResultMessage'])) {
                                        $output['formResultMessage'] = [];
                                    }
                                    $output['formResultMessage'][] = $updateResult['formResultMessage'];
                                }
                            }
                            unset($updateResult);
                        }
                    }// endforeach;
                    unset($mname);
                }// endif; there is selected module(s).

                // set form result status and message if not exists. usually this will be success message. ------
                if (!isset($output['formResultStatus'])) {
                    $output['formResultStatus'] = 'success';
                }
                if (!isset($output['formResultMessage'])) {
                    $totalSelectedModules = (is_countable($output['moduleSystemNameArray']) ? count($output['moduleSystemNameArray']) : 0);
                    if (isset($output['action']) && $output['action'] === 'enable') {
                        $output['formResultMessage'] = dn__('rdbadmin', 'Enabled the selected module successfully.', 'Enabled the selected modules successfully.', $totalSelectedModules);
                    } elseif (isset($output['action']) && $output['action'] === 'disable') {
                        $output['formResultMessage'] = dn__('rdbadmin', 'Disabled the selected module successfully.', 'Disabled the selected modules successfully.', $totalSelectedModules);
                    } else {
                        $output['formResultMessage'] = __('Updated successfully.');
                        if (isset($output['action']) && $output['action'] === 'update') {
                            $output['formResultMessage'] .= '<br>' . PHP_EOL
                                . sprintf(
                                    __('Due to some composer package maybe changed but it is unable to run command %1$s on this process, please run this command manually.'),
                                    '<code>composer update</code>'
                                );
                        }
                    }
                    $output['updated'] = true;
                    unset($totalSelectedModules);
                }// endif; isset $output['formResultMessage']
                // end set form result status and message. -------------------------------------------------------------
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
    }// doActionsAction


    /**
     * Do update the module.
     * 
     * This will run module update command and `Installer->update()` of selected module will be execute.<br>
     * It is the same as running via command line `php rdb system:module update --mname=xxx` (where xxx is Module folder name).
     * 
     * @since 1.2.10
     * @see \Rdb\System\Core\Console\Module::executeUpdate()
     * @param string $mname Module system name to be run with update command.
     * @return array Return associative array:<br>
     *              `formResultStatus` (string) The form result error status. This key exists only when there is an error.<br>
     *              `formResultMessage` (string) The error message. This key exists only when there is an error.<br>
     */
    private function doUpdateModule(string $mname): array
    {
        $output = [];

        // validation. ==========================================
        $validated = $this->Modules->exists($mname);

        if (true !== $validated) {
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = sprintf(
                __('The module you selected is not exists or not enabled (%1$s).'),
                $mname
            );
            http_response_code(400);
        }

        if (true === $validated && !method_exists($this->Modules, 'getComposerDefault')) {
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = sprintf(
                __('Please update the framework to version %1$s or newer.'),
                '1.1.8'
            );
            $validated = false;
            http_response_code(500);
        }

        if (
            true === $validated &&
            is_file(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json') &&
            is_writable(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json') &&
            is_dir(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules') &&
            is_writable(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules')
        ) {
            // if composer.json file exists and writable, and public modules folder exists and writable.
            // OK.
            $updated = true;
        } else {
            // Not OK.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Please make sure that this file and folder are exists and writable.') . '<br>' . PHP_EOL .
                sprintf(
                    '%1$s<br>' . PHP_EOL . '%2$s',
                    ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json',
                    PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules'
                );
            http_response_code(400);
            $validated = false;
            $updated = false;
        }
        unset($validated);
        // end validation. ======================================

        // the code below has been copied from `\Rdb\System\Core\Console\Module->executeUpdate()`.
        // try to call installer class if exists (for update). ----------------------------------------------------------
        $InstallerClassName = '\\Rdb\\Modules\\' . $mname . '\\Installer';
        if ($updated === true && class_exists($InstallerClassName)) {
            // if class exists.
            $Installer = new $InstallerClassName($this->Container);
            if ($Installer instanceof \Rdb\System\Interfaces\ModuleInstaller) {
                // if class really is the installer.
                try {
                    $Installer->update();
                } catch (\Exception $e) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = $e->getMessage();
                    http_response_code(500);
                    $updated = false;
                }
            }
            unset($Installer);
        }
        unset($InstallerClassName);

        // try to do something else after update was success. ----------------------------------------------------
        if ($updated === true) {
            // if updated or installer class for update was called.
            // delete public/Modules/[module_name] folder.-----------------------------------------
            if (realpath(ROOT_PATH) !== realpath(PUBLIC_PATH) && is_dir(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $mname) && $mname != 'SystemCore') {
                $Fs = new \Rdb\System\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules');
                $Fs->deleteFolder($mname, true);
                unset($Fs);
            }

            // then copy assets folder to public/Modules/[module_name]/assets folder. ----------
            if (is_dir(MODULE_PATH . DIRECTORY_SEPARATOR . $mname . DIRECTORY_SEPARATOR . 'assets')) {
                $Fs = new \Rdb\System\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules');
                $Fs->copyFolderRecursive(
                    MODULE_PATH . DIRECTORY_SEPARATOR . $mname . DIRECTORY_SEPARATOR . 'assets', 
                    $mname . DIRECTORY_SEPARATOR . 'assets'
                );
                unset($Fs);

                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $Logger->write('modules/rdbadmin/controllers/admin/modules/actionscontroller', 0, 'This module contain assets folder ({assetdir}).', ['assetdir' => MODULE_PATH . DIRECTORY_SEPARATOR . $mname . DIRECTORY_SEPARATOR . 'assets']);
                    $Logger->write('modules/rdbadmin/controllers/admin/modules/actionscontroller', 0, 'Copied to destination ({dest}).', ['dest' => (PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $mname . DIRECTORY_SEPARATOR . 'assets')]);
                }
            }

            // copy moduleComposer.json --------------------------------------------------------------
            $composerDefault = $this->Modules->getComposerDefault();
            if (
                is_file(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json') &&
                !empty($composerDefault)
            ) {
                // delete main app's composer.json
                unlink(ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json');
                // copy composer default json file to main app's composer.json
                copy($composerDefault, ROOT_PATH . DIRECTORY_SEPARATOR . 'composer.json');
                // copy composer.json from **ALL enabled** modules into main app's composer.json
                $copiedResult = $this->Modules->copyComposerAllModules();

                if (
                    is_array($copiedResult) &&
                    isset($copiedResult['modulesWithComposer']) &&
                    isset($copiedResult['successCopied']) &&
                    $copiedResult['modulesWithComposer'] === $copiedResult['successCopied']
                ) {
                    // if success.
                    // there is message about update composer but we will not display it here.
                } else {
                    $output['formResultStatus'] = 'warning';
                    $output['formResultMessage'] = sprintf(
                        __('Unable to copy %1$s from some modules, here is the list (%2$s).'),
                        '<strong>moduleComposer.json</strong>',
                        (isset($copiedResult['failedModules']) ? implode(', ', $copiedResult['failedModules']) : '')
                    )
                    . ' '
                    . sprintf(
                        __('Please try to manually edit your composer by merge the required dependency and then run %1$s command.'),
                        '<code>composer update</code>'
                    );
                    http_response_code(500);
                }
                unset($copiedResult);
            }
            unset($composerDefault);
        }// endif; $updated is `true`.
        unset($updated);

        return $output;
    }// doUpdateModule


    /**
     * Validate form actions.
     * 
     * This method was called from `doActionsAction()` method.
     * 
     * This method will be send out HTTP status if failed to validate.
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
    private function validateActions(string $module_ids, string $modulesystemnames, string $currentModule, string $action): array
    {
        $output = [];

        $output['action'] = $action;
        $output['module_ids'] = $module_ids;
        $output['moduleSystemNameArray'] = explode(',', $modulesystemnames);
        $expModuleIds = explode(',', $output['module_ids']);
        $actionsFormOk = false;// if modules and action were selected, it will be true.
        $allowedActions = ['enable', 'disable', 'update'];// select action values in array.

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

        if (strtolower($output['action']) === 'update' && count($output['moduleSystemNameArray']) > 1) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select only one module.');
            $actionsFormOk = false;
        }
        // end validate selected plugins and action. --------------------------

        $output['actionsFormOk'] = $actionsFormOk;
        $output['moduleIdsArray'] = $expModuleIds;

        unset($actionsFormOk, $expModuleIds);

        return $output;
    }// validateActions


}
