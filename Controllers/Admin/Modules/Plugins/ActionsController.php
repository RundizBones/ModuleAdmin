<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Modules\Plugins;


/**
 * Plugins actions controller
 * 
 * @since 0.2.4
 */
class ActionsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\PluginsTrait;


    /**
     * Do update action on selected plugins.
     * 
     * @return string
     */
    public function doUpdateAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminModulesPlugins', ['managePlugins']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

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

            // validate plugins and action must be select.
            $output = array_merge($output, $this->validatePluginsAction($_PATCH['plugin_ids'], $_PATCH['action']));
            unset($_PATCH);

            if (isset($output['actionsFormOk']) && $output['actionsFormOk'] === true) {
                $FileSystem = new \Rdb\System\Libraries\FileSystem(MODULE_PATH);
                if ($this->Container->has('Plugins')) {
                    $Plugins = $this->Container->get('Plugins');
                } else {
                    $Plugins = new \Rdb\Modules\RdbAdmin\Libraries\Plugins($this->Container);
                    $Plugins->registerAllPluginsHooks();
                }
                $pluginClassNamePrefix = '\\Rdb\\Modules\\';
                $ReflectionClassTargetInstance = new \ReflectionClass('\\Rdb\\Modules\\RdbAdmin\\Interfaces\\Plugins');

                if (isset($output['pluginsSystemName']) && is_array($output['pluginsSystemName'])) {
                    foreach ($output['pluginsSystemName'] as $pluginSystemName) {
                        $pluginName = explode('\\', str_replace('/', '\\', $pluginSystemName));
                        $pluginClassName = $pluginClassNamePrefix . trim(str_replace('/', '\\', $pluginSystemName), '\\') . '\\' . $pluginName[count($pluginName) - 1];
                        $ReflectionPlugin = new \ReflectionClass($pluginClassName);
                        $pluginInstance = $ReflectionPlugin->newInstanceWithoutConstructor();

                        if (isset($output['action']) && $output['action'] === 'enable') {
                            $FileSystem->deleteFile($pluginSystemName . '/.disabled');
                            if (class_exists($pluginClassName) && $ReflectionClassTargetInstance->isInstance($pluginInstance)) {
                                $PluginClassObject = new $pluginClassName($this->Container);
                                $PluginClassObject->enable();
                            }
                        } elseif (isset($output['action']) && $output['action'] === 'disable') {
                            $FileSystem->writeFile($pluginSystemName . '/.disabled', '');
                            if (class_exists($pluginClassName) && $ReflectionClassTargetInstance->isInstance($pluginInstance)) {
                                $PluginClassObject = new $pluginClassName($this->Container);
                                $PluginClassObject->disable();
                            }
                        }

                        unset($pluginClassName, $pluginInstance, $pluginName, $ReflectionPlugin);
                    }// endforeach;
                    unset($pluginSystemName);
                }

                $output['formResultStatus'] = 'success';
                $output['formResultMessage'] = __('Updated successfully.');
                $output['updated'] = true;

                unset($FileSystem, $Plugins, $pluginClassNamePrefix, $ReflectionClassTargetInstance);
            } else {
                // if form validation failed.
                // it was already send out http response code and message was set, nothing to do here.
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
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// doUpdateAction


    /**
     * Validate plugin(s) and action.
     * 
     * This method was called from `doUpdateAction()` method.
     * 
     * @param string $plugin_ids The selected plugin IDs.
     * @param string $action The selected action.
     * @return array Return associative array with keys:<br>
     *                          `action` The selected action.<br>
     *                          `actionText` The text of selected action, for displaying.<br>
     *                          `plugin_ids` The selected plugin IDs.<br>
     *                          `formResultStatus` (optional) If contain any error, it also send out http response code.<br>
     *                          `formResultMessage` (optional) If contain any error, it also send out http response code.<br>
     *                          `pluginsSystemName` The plugin ids in array.<br>
     *                          `actionsFormOk` The boolean value of form validation. It will be `true` if form validation passed, and will be `false` if it is not.
     */
    protected function validatePluginsAction(string $plugin_ids, string $action): array
    {
        $output = [];

        $output['action'] = $action;
        $output['plugin_ids'] = $plugin_ids;
        $expPluginIds = explode(',', $output['plugin_ids']);
        $pluginsActionOk = false;// if plugins and action were selected, it will be true.

        // validate selected plugins and action. ------------------------------
        if (count($expPluginIds) <= 0) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select at least one plugin.');
        } else {
            $pluginsActionOk = true;
        }
        if (empty($output['action'])) {
            http_response_code(400);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'][] = __('Please select an action.');
            $pluginsActionOk = false;
        }
        // end validate selected plugins and action. --------------------------

        if ($pluginsActionOk === true) {
            if ($output['action'] === 'enable') {
                $output['actionText'] = n__('Enable plugin', 'Enable plugins', count($expPluginIds));
            } elseif ($output['action'] === 'disable') {
                $output['actionText'] = n__('Disable plugin', 'Disable plugins', count($expPluginIds));
            } else {
                $output['actionText'] = $output['action'];

                http_response_code(400);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'][] = __('Please select an action.');
                $pluginsActionOk = false;
            }
        }

        $output['actionsFormOk'] = $pluginsActionOk;
        $output['pluginsSystemName'] = $expPluginIds;

        unset($expPluginIds, $pluginsActionOk);

        return $output;
    }// validatePluginsAction


}
