<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin;


/**
 * Logout page controller.
 * 
 * @since 0.1
 */
class LogoutController extends \Rdb\Modules\RdbAdmin\Controllers\BaseController
{


    use Users\Traits\UsersTrait;


    /**
     * Do logout process.
     */
    protected function doLogout()
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if ($this->Input->delete('logoutAllDevices') === '1') {
            // if logout on ALL devices.
            $this->logoutUser([], true);
        } else {
            // if logout on selected device only.
            $this->logoutUser();
        }
    }// doLogout


    /**
     * Rest API do logout.
     * 
     * Logout use method POST (see link below for description).
     * 
     * @link https://stackoverflow.com/questions/3521290/logout-get-or-post Logout use POST.
     * @return string
     */
    public function doLogoutAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_id() === '') {
            session_start();
        }
        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();

        $output = [];
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if ($Csrf->validateToken($this->Input->delete($csrfName), $this->Input->delete($csrfValue))) {
            // if validated token to prevent CSRF.
            // do logout process.
            $this->doLogout();

            $output['processDateTime'] = date('Y-m-d H:i:s');
            $output['processDateTimeGMT'] = gmdate('Y-m-d H:i:s');
            $output['loggedOut'] = true;
            $output['loggedOutAsString'] = 'true';
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
    }// doLogoutAction


    /**
     * Logout page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output = array_merge($output, $Csrf->createToken());

        if (isset($_GET['fastLogout']) && $_GET['fastLogout'] === 'true') {
            // if fast logout.
            $output['fastLogout'] = true;
        }
        $output['loginUrl'] = ($_GET['goback'] ?? $Url->getAppBasedPath() . '/admin/login');
        if (stripos($output['loginUrl'], '//') !== false) {
            // if found double slash, this means it can go to other domain.
            // do not allow this, change the login URL.
            $output['loginUrl'] = $Url->getAppBasedPath() . '/admin/login';
        } else {
            $output['loginUrl'] = strip_tags($output['loginUrl']);
        }
        $output['logoutUrl'] = $Url->getCurrentUrl() . $Url->getQuerystring();
        $output['logoutMethod'] = 'DELETE';

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $ModuleAssets = new \Rdb\Modules\RdbAdmin\ModuleData\ModuleAssets($this->Container);
            $MyModuleAssets = $ModuleAssets->getModuleAssets();
            unset($ModuleAssets);
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['rdbaLoginLogout'], $MyModuleAssets);
            $Assets->addMultipleAssets('js', ['rdbaLogout'], $MyModuleAssets);
            $Assets->addJsObject(
                'rdbaLogout', 
                'RdbaLogout', 
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'loginUrl' => $output['loginUrl'],
                    'logoutUrl' => $output['logoutUrl'],
                    'logoutMethod' => $output['logoutMethod'],
                    'fastLogout' => (isset($_GET['fastLogout']) ? true : false),
                    'txtLoggintOut' => __('Logging you out.'),
                    'txtYouLoggedOut' => __('You are now logged out.'),
                ]
            );

            $output['pageTitle'] = __('Logout');
            $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle']);
            $output['pageHtmlClasses'] = $this->getPageHtmlClasses(['rdba-login-logout-pages', 'rdba-pagehtml-logout']);
            $output['urlAdminLogin'] = $output['loginUrl'];
            $output['Assets'] = $Assets;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Logout/index_v', $output);

            unset($Assets, $MyModuleAssets, $Csrf, $Url);
            return $this->Views->render('common/Admin/emptyLayout_v', $output);
        }
    }// indexAction


}
