<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\UI;


/**
 * UI XHR admin dashboard widgets.
 * 
 * @since 0.1
 */
class XhrDashboardWidgetsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    /**
     * Get admin dashboard widgets HTML and response as JSON via REST API.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (!$this->Input->isNonHtmlAccept() && !$this->Input->isXhr()) {
            // if not custom HTTP accept.
            http_response_code(403);
            return __('Sorry, this page is for request via XHR, REST API.');
            exit();
        }

        $output = [];
        $output['widgets'] = [];

        // get admin widgets HTML contents from ModuleAdmin class of each enabled modules. -----------------
        // get enabled modules.
        $modules = $this->Modules->getModules();
        if (is_array($modules)) {
            // if modules list is array.
            // loop each module.
            foreach ($modules as $module) {
                if (is_file(MODULE_PATH . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'ModuleData' . DIRECTORY_SEPARATOR . 'ModuleAdmin.php')) {
                    $ModuleAdminClassName = '\\Rdb\\Modules\\' . $module . '\\ModuleData\\ModuleAdmin';

                    if (class_exists($ModuleAdminClassName)) {
                        $ReflectionClassTargetInstance = new \ReflectionClass('\\Rdb\\Modules\\RdbAdmin\\Interfaces\\ModuleAdmin');
                        $ReflectionClass = new \ReflectionClass($ModuleAdminClassName);
                        $classInstance = $ReflectionClass->newInstanceWithoutConstructor();

                        if (
                            $ReflectionClassTargetInstance->isInstance($classInstance) &&
                            $ReflectionClass->hasMethod('dashboardWidgets')
                        ) {
                            $ModuleAdmin = $ReflectionClass->newInstance($this->Container);
                            // get admin widgets HTML contents from ModuleAdmin->dashboardWidgets().
                            $moduleWidgets = $ModuleAdmin->dashboardWidgets();
                            if (is_array($moduleWidgets) && !empty($moduleWidgets)) {
                                $output['widgets'] = array_merge($output['widgets'], $moduleWidgets);
                            }
                            unset($ModuleAdmin, $moduleWidgets);
                        }// endif check instance of module admin class and check has method.

                        unset($classInstance, $ReflectionClass, $ReflectionClassTargetInstance);
                    }// endif check class exists.

                    unset($ModuleAdminClassName);
                }
            }// endforeach;
            unset($module);
        }
        unset($modules);
        // end get admin widgets HTML contents from ModuleAdmin class of each enabled modules. -------------

        // re-order widgets to user's saved data. ------------------------------------------------------------------------
        // get current user's ID.
        $user_id = (isset($this->userSessionCookieData['user_id']) ? (int) $this->userSessionCookieData['user_id'] : 0);
        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
        $fieldResult = $UserFieldsDb->get($user_id, 'rdbadmin_uf_admindashboardwidgets_order');
        if (is_object($fieldResult) && isset($fieldResult->field_value)) {
            $savedWidgetsOrdered = $fieldResult->field_value;
            if (is_array($savedWidgetsOrdered)) {
                $reOrderedWidgets = [];

                if (array_key_exists('rowHero', $savedWidgetsOrdered)) {
                    $reOrderedWidgets = array_merge(
                        $reOrderedWidgets,
                        $this->reOrderWidgets($savedWidgetsOrdered['rowHero'], $output['widgets'], 'hero')
                    );
                }
                if (array_key_exists('rowNormal', $savedWidgetsOrdered)) {
                    $reOrderedWidgets = array_merge(
                        $reOrderedWidgets,
                        $this->reOrderWidgets($savedWidgetsOrdered['rowNormal'], $output['widgets'])
                    );
                }

                if (!empty($reOrderedWidgets)) {
                    if (is_array($output['widgets']) && !empty($output['widgets'])) {
                        // if there may have some widgets that is new and never re-order before.
                        // append to the end of re-ordered widgets.
                        $reOrderedWidgets = $reOrderedWidgets + $output['widgets'];
                    }
                    $output['widgets'] = $reOrderedWidgets;
                }
    
                unset($reOrderedWidgets);
            }
            unset($savedWidgetsOrdered);
        }
        unset($fieldResult, $user_id, $UserFieldsDb);
        // end re-order widgets to user's saved data. -------------------------------------------------------------------

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// indexAction


    /**
     * Re-order module widgets from saved widgets key in DB.
     * 
     * @param array $savedWidgetsOrdered The saved ordered widgets key in DB.
     * @param array $moduleWidgets Modules widgets that were retrieved. These data will be removed once re-ordered.
     * @param string $widgetsType Widgets type. Value should be 'hero', 'normal'.
     * @return array Return re-order module widgets to the same ordered key in DB.
     */
    protected function reOrderWidgets(array $savedWidgetsOrdered, array &$moduleWidgets, string $widgetsType = 'normal'): array
    {
        $newWidgetsOrdered = [];

        foreach ($savedWidgetsOrdered as $widgetId) {
            if (array_key_exists($widgetId, $moduleWidgets)) {
                if ($widgetsType === 'hero') {
                    if (isset($moduleWidgets[$widgetId]['rowHero']) && $moduleWidgets[$widgetId]['rowHero'] === true) {
                        $newWidgetsOrdered[$widgetId] = $moduleWidgets[$widgetId];
                        unset($moduleWidgets[$widgetId]);
                    }
                } else {
                    $newWidgetsOrdered[$widgetId] = $moduleWidgets[$widgetId];
                    unset($moduleWidgets[$widgetId]);
                }
            }
        }// endforeach;
        unset($widgetId);

        return $newWidgetsOrdered;
    }// reOrderWidgets


    /**
     * Save re-order of the admin dashboard widgets.
     * 
     * @global array $_PATCH
     * @return string
     */
    public function saveOrderAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();

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
            // if validated token to prevent CSRF.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            // get current user's ID.
            $user_id = (isset($this->userSessionCookieData['user_id']) ? (int) $this->userSessionCookieData['user_id'] : 0);
            $updateData = json_decode($_PATCH['updateData'], true);
            $widgetsType = $this->Input->patch('widgetsType', 'normal');

            $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
            $fieldResult = $UserFieldsDb->get($user_id, 'rdbadmin_uf_admindashboardwidgets_order');
            if (is_object($fieldResult) && isset($fieldResult->field_value)) {
                $dashboardWidgetsOrder = $fieldResult->field_value;
            }
            unset($fieldResult);

            if (!isset($dashboardWidgetsOrder) || !is_array($dashboardWidgetsOrder)) {
                $dashboardWidgetsOrder = [];
            }
            if (!array_key_exists('rowHero', $dashboardWidgetsOrder)) {
                $dashboardWidgetsOrder['rowHero'] = [];
            }
            if (!array_key_exists('rowNormal', $dashboardWidgetsOrder)) {
                $dashboardWidgetsOrder['rowNormal'] = [];
            }

            if ($widgetsType === 'hero') {
                $dashboardWidgetsOrder['rowHero'] = $updateData;
            } else {
                $dashboardWidgetsOrder['rowNormal'] = $updateData;
            }
            unset($updateData, $widgetsType);
            $output['updateResult'] = $UserFieldsDb->update($user_id, 'rdbadmin_uf_admindashboardwidgets_order', $dashboardWidgetsOrder, true);
            unset($dashboardWidgetsOrder, $user_id, $UserFieldsDb);
        } else {
            // if unable to validate token.
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Please reload the page and try again.');
            http_response_code(400);
        }

        unset($csrfName, $csrfValue);
        // generate new token for re-submit the form continueously without reload the page.
        $output = array_merge($output, $Csrf->createToken());

        // display, response part ---------------------------------------------------------------------------------------------
        unset($Csrf);
        return $this->responseAcceptType($output);
    }// saveOrderAction


}
