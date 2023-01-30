<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin;


/**
 * Admin base controller.
 * 
 * @since 0.1
 */
abstract class AdminBaseController extends \Rdb\Modules\RdbAdmin\Controllers\BaseController
{


    use Users\Sessions\Traits\SessionsTrait;


    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        /*
         * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController->__construct.adminInit
         * PluginHookDescription: Runs at beginning of `AdminBaseController`.
         * PluginHookParam: None.
         * PluginHookSince: 1.2.6
         */
        /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
        $Plugins = $this->Container->get('Plugins');
        $Plugins->doHook(__CLASS__.'->'.__FUNCTION__.'.adminInit');
        unset($Plugins);

        // check admin login. always check admin login to redirect user to /admin/login page.
        $this->checkLogin();
    }// __construct


    /**
     * Check for logged in, if not then redirect to login page.
     */
    protected function checkLogin()
    {
        $isLoggedin = $this->isUserLoggedIn();

        if (!$this->Container->has('UsersSessionsTrait')) {
            $TraitAsClass = new \stdClass();
            $TraitAsClass->totalLoggedInSessions = $this->totalLoggedInSessions;
            $TraitAsClass->userSessionCookieData = $this->userSessionCookieData;
            $this->Container['UsersSessionsTrait'] = function ($c) use ($TraitAsClass) {
                return $TraitAsClass;
            };
            unset($TraitAsClass);
        }

        if ($isLoggedin === false) {
            // if not logged in.
            $Url = new \Rdb\System\Libraries\Url($this->Container);
            $redirectQuery = 'goback=' . rawurlencode(
                    $Url->getAppBasedPath() . '/admin/login?goback=' . 
                    $Url->getCurrentUrl() . $Url->getQuerystring()
                );
            $redirectQuery .= '&fastLogout=true';
            $redirectUrl = $Url->getAppBasedPath() . '/admin/logout?' . $redirectQuery;
            $domainProtocol = $Url->getDomainProtocol();
            unset($redirectQuery);

            $this->responseNoCache();
            if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
                // if custom HTTP accept or XHR.
                $output['loggedIn'] = false;
                $output['loggedInAsString'] = 'false';
                $output['loginUrlBaseDomain'] = $domainProtocol;
                $output['loginUrl'] = $redirectUrl;
                $output['loginUrlBase'] = $Url->getAppBasedPath(true) . '/admin/login';
                $output['logoutUrlBase'] = $Url->getAppBasedPath(true) . '/admin/logout';
                http_response_code(401);
                echo $this->responseAcceptType($output);// must echo out.
                unset($domainProtocol, $redirectUrl, $Url);
                exit();
            }

            http_response_code(303);// https://stackoverflow.com/q/2068418/128761
            header('Location: ' . $redirectUrl);
            unset($domainProtocol, $redirectUrl, $Url);
            exit();
        }
    }// checkLogin


    /**
     * Check permission and (redirect OR response error message).
     * 
     * This will be redirect user to /admin page if request from web page.<br>
     * This will be response error message if request via REST API or AJAX.
     * 
     * @param string $moduleSystemName The module (module system name or folder name) to check.
     * @param string $page The page name to check.
     * @param string|array $action The action(s) on that page. Use string if check for single action, use array if check for multiple actions.<br>
     *                                              If checking for multiple actions, any single action matched with certain module, page will be return `true`.
     * @param array $identity The associative array of identity.
     * @see \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb::checkPermission() For reference.
     */
    protected function checkPermission(string $moduleSystemName, string $page, $action, array $identity = [])
    {
        $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
        if ($UserPermissionsDb->checkPermission($moduleSystemName, $page, $action, $identity) !== true) {
            // if permission denied.
            if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
                // if request via REST API or AJAX.
                http_response_code(403);
                $output = [];
                $output['permissionDenied'] = true;
                $output['permissionDeniedAsString'] = 'true';
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Access denied!');
                echo $this->responseAcceptType($output);// must echo out.
                exit();
            } else {
                // if normal web page request.
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                // flash message.
                $output = [];
                $output['pageAlertStatus'] = 'error';
                $output['pageAlertMessage'] = __('Access denied!');
                $output['pageAlertHttpStatus'] = 403;
                $_SESSION['pageAlertMessage'] = json_encode($output);
                unset($output);

                // then redirect.
                http_response_code(303);
                $this->responseNoCache();
                $Url = new \Rdb\System\Libraries\Url($this->Container);
                header('Location: ' . $Url->getAppBasedPath(true) . '/admin');
                unset($Url);
                exit();
            }
        }
        unset($UserPermissionsDb);
    }// checkPermission


    /**
     * Get RdbAdmin module's assets.
     * 
     * These contain the assets that is required for admin page to work.
     * 
     * @return array Return associative array with 'css' and 'js' in keys.
     */
    protected function getRdbAdminAssets(): array
    {
        $ModuleAssets = new \Rdb\Modules\RdbAdmin\ModuleData\ModuleAssets($this->Container);
        return $ModuleAssets->getModuleAssets();
    }// getRdbAdminAssets


}
