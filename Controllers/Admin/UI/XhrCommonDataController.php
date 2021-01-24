<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\UI;


/**
 * UI XHR common data for many pages controller.
 *
 * @since 0.1
 */
class XhrCommonDataController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use Traits\CommonDataTrait;


    /**
     * Get application version.
     * 
     * Get app version from Modules/[module_name]/Installer.php in the `@version` at file-level doc block.
     * 
     * @param string $module The module name to get version.
     * @return array Return associative array with keys `version` for the app version, and `name` for the app name.
     */
    protected function getAppVersion(string $module = 'RdbAdmin'): array
    {
        $output['name'] = __('Administration module for RundizBones');
        $output['version'] = '';

        if (
            !defined('MODULE_PATH') ||
            (
                defined('MODULE_PATH') &&
                !is_file(MODULE_PATH . '/' . $module . '/Installer.php')
            )
        ) {
            return $output;
        }

        $File = new \SplFileObject(MODULE_PATH . '/' . $module . '/Installer.php');
        $fileContent = '';
        $i = 0;
        while (!$File->eof()) {
            $fileContent .= $File->fgets();
            $i++;
            if ($i >= 30) {
                // grab only 30 max line is enough.
                break;
            }
        }
        $File = null;
        unset($File, $i);
        // replace newlines to unix (\n) only.
        $fileContent = preg_replace('~\R~u', "\n", $fileContent);// https://stackoverflow.com/a/7836692/128761

        preg_match('#(?:\/\*(?:[^*]|(?:\*[^\/]))*\*\/)#iu', $fileContent, $firstDocblock);
        unset($fileContent);
        if (isset($firstDocblock[0])) {
            preg_match_all('#@([0-9a-z\-\_]+) *(.*)\n#iu', $firstDocblock[0], $matches, PREG_SET_ORDER);
            unset($firstDocblock);
            if (isset($matches) && is_array($matches)) {
                foreach ($matches as $key => $item) {
                    if (isset($item[1]) && isset($item[2]) && strtolower($item[1]) === 'version') {
                        unset($matches);
                        $output['version'] = __('Version %1$s', $item[2]);
                        break;
                    }
                }// endforeach;
                unset($item, $key);
            }
            unset($matches);
        }
        unset($firstDocblock);
        return $output;
    }// getAppVersion


    /**
     * Get translation for display in data tables from datatables.net.
     * 
     * @link https://datatables.net/reference/option/language Reference.
     * @return array
     */
    protected function getDataTablesTranslation()
    {
        // the array key must match in datatables.
        return [
            'emptyTable' => p__('translation for datatables.', 'No data'),
            'search' => p__('translation for datatables.', 'Search:'),
            'paginate' => [
                'first' => p__('translation for datatables.', 'First'),
                'last' => p__('translation for datatables.', 'Last'),
                'next' => p__('translation for datatables.', 'Next'),
                'previous' => p__('translation for datatables.', 'Previous'),
                'info' => p__('translation for datatables.', 'Page _INPUT_ of _TOTAL_'),
            ],
            'processing' => p__('translation for datatables.', 'Processing...'),
            'zeroRecords' => p__('translation for datatables.', 'No data'),
        ];
    }// getDataTablesTranslation


    /**
     * Get languages and its configuration.
     * 
     * Get languages and its config such as default language, current language, detect method, set language url and method, etc..
     * 
     * @return mixed Return JSON decoded if it is custom HTTP accept requested with JSON content type, return unserialized array if it is other request content type.
     */
    protected function getLanguages()
    {
        $languagesResult = $this->Modules->execute('Rdb\\Modules\\Languages\\Controllers\\Languages:index', []);

        $languagesResultDecode = json_decode($languagesResult);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $Serializer = new \Rundiz\Serializer\Serializer();
            $languagesResultDecode = $Serializer->maybeUnserialize($languagesResult);
            unset($Serializer);
        }

        unset($languagesResult);
        return $languagesResultDecode;
    }// getLanguages


    /**
     * Get page alert message(s).
     * 
     * The alert array format should be:
     * <pre>
     * array(
     *     0 => array(
     *         'status' => 'error',
     *         'message' => 'The alert message.',
     *     ),
     *     1 => array(
     *         'status' => 'warning',
     *         'message' => array('Ther alert message', 'can be array', 'for many error messages in one alert.'),
     *     ),
     * );
     * </pre>
     * 
     * @return array If there is any alert message, it will return as array in the example above.
     */
    protected function getPageAlertMessages(): array
    {
        if (session_id() === '') {
            session_start();
        }

        $output = [];

        if ($this->Container->has('Plugins')) {
            /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
            $Plugins = $this->Container->get('Plugins');
            /*
             * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\UI->getPageAlertMessages.beforeGetSession
             * PluginHookDescription: Hook before get message from session.
             * PluginHookParam: array $output This argument will be pass by reference, you can alter but variable type must be array. 
             *              The output of alert messages that will be send to browser. 
             *              The format is `array(array('status' => 'error', 'message' => 'My error message.'), array('status' => 'success', 'message' => 'Success message.'))`.
             * PluginHookSince: 0.2.4
             */
            $Plugins->doHook(__CLASS__.'->'.__FUNCTION__.'.beforeGetSession', [&$output]);
            if (!is_array($output)) {
                $output = [];
            }
        }

        if (isset($_SESSION['pageAlert']) && is_array($_SESSION['pageAlert'])) {
            foreach ($_SESSION['pageAlert'] as $key => $item) {
                if (!isset($item['message'])) {
                    continue;
                }
                if (!isset($item['status']) || !is_string($item['status'])) {
                    $item['status'] = 'warning';
                }
                $output[] = [
                    'status' => $item['status'],
                    'message' => $item['message'],
                ];
            }// endforeach;
            unset($item, $key);
        }

        if (isset($Plugins)) {
            /*
             * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\UI->getPageAlertMessages.afterGetSession
             * PluginHookDescription: Hook after get message from session.
             * PluginHookParam: array $output This argument will be pass by reference, you can alter but variable type must be array. 
             *              The output of alert messages that will be send to browser. 
             *              The format is `array(array('status' => 'error', 'message' => 'My error message.'), array('status' => 'success', 'message' => 'Success message.'))`.
             * PluginHookSince: 0.2.4
             */
            $Plugins->doHook(__CLASS__.'->'.__FUNCTION__.'.afterGetSession', [&$output]);
            if (!is_array($output)) {
                $output = [];
            }
        }

        unset($_SESSION['pageAlert']);
        return $output;
    }// getPageAlertMessages


    /**
     * Get URLs and menu items.
     * 
     * @return array Return associative array where URLs are under `urls` key, and menu items are under `menuItems` key.
     */
    protected function getUrlsMenuItems(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $MenuItems = new \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\MenuItems($this->Container);
        $output = [];

        $output['urls'] = [
            'baseDomain' => $Url->getDomainProtocol(),
            'adminHome' => $Url->getAppBasedPath(true) . '/admin',
            'frontHome' => $Url->getAppBasedPath(true) . '/',
        ];

        $output['menuItems'] = $MenuItems->getMenuItems($this->userSessionCookieData);

        unset($MenuItems, $Url);

        return $output;
    }// getUrlsMenuItems


    /**
     * Get user data.
     * 
     * @return array
     */
    protected function getUserData(): array
    {
        $cookieData = $this->userSessionCookieData;

        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];

        if (isset($cookieData['user_id']) && is_numeric($cookieData['user_id'])) {
            $output['user_id'] = (int) $cookieData['user_id'];
        } else {
            $output['user_id'] = 0;
        }

        if (isset($cookieData['sessionKey'])) {
            $output['userlogin_session_key'] = $cookieData['sessionKey'];
        } else {
            $output['userlogin_session_key'] = null;
        }

        if (isset($cookieData['user_display_name'])) {
            $output['user_display_name'] = $cookieData['user_display_name'];
        } else {
            $output['user_display_name'] = __('Guest');
        }
        unset($cookieData);

        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
        $userAvatarType = $UserFieldsDb->get($output['user_id'], 'rdbadmin_uf_avatar_type');
        if (isset($userAvatarType->field_value) && $userAvatarType->field_value === 'gravatar') {
            $Gravatar = new \Rdb\Modules\RdbAdmin\Libraries\Gravatar();
            $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
            $user = $UsersDb->get(['user_id' => $output['user_id']]);
            $output['user_avatar'] = $Gravatar->getImage($user->user_email);
            $output['useGravatar'] = true;
            unset($Gravatar, $user, $UsersDb);
        } else {
            $userAvatar = $UserFieldsDb->get($output['user_id'], 'rdbadmin_uf_avatar');
            if (isset($userAvatar->field_value) && !empty($userAvatar->field_value)) {
                $output['user_avatar'] = $userAvatar->field_value;
            }
            unset($userAvatar);
        }
        unset($userAvatarType, $UserFieldsDb);

        $output['UrlBaseDomain'] = $Url->getDomainProtocol();
        $output['UrlAppBased'] = $Url->getAppBasedPath();
        $output['UrlAppBasedWithLang'] = $Url->getAppBasedPath(true);
        $output['UrlLogin'] = $Url->getAppBasedPath(true) . '/admin/login';
        $output['UrlEditUser'] = $Url->getAppBasedPath(true) . '/admin/users/edit';
        $output['UrlLogout'] = $Url->getAppBasedPath(true) . '/admin/logout';

        unset($Url);
        return $output;
    }// getUserData


    /**
     * Get UI common data.
     * 
     * This method can get 'all' or only specific data via 'getData' query string.<br>
     * To get all data please use 'getData[]' value to 'all'.<br>
     * To get some data please use 'getData[]' and specify value. Example: getData[]=configDb&getdata[]=languages&...
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->Languages->getHelpers();

        if (!$this->Input->isNonHtmlAccept() && !$this->Input->isXhr()) {
            // if not custom HTTP accept.
            http_response_code(403);
            return __('Sorry, this page is for request via XHR, REST API.');
            exit();
        }

        $output = [];

        $getDatas = $this->Input->get('getData', []);
        if (is_array($getDatas)) {
            foreach ($getDatas as $getData) {
                if ($getData === 'configDb' || $getData === 'all') {
                    $output['configDb'] = $this->getConfigDb();
                }
                if ($getData === 'languages' || $getData === 'all') {
                    $output['languages'] = $this->getLanguages();
                }
                if ($getData === 'userData' || $getData === 'all') {
                    $output['userData'] = $this->getUserData();
                }
                if ($getData === 'urlsMenuItems' || $getData === 'all') {
                    $output['urlsMenuItems'] = $this->getUrlsMenuItems();
                }
                if ($getData === 'appVersion' || $getData === 'all') {
                    $output['appVersion'] = $this->getAppVersion();
                }
                if ($getData === 'datatablesTranslation' || $getData === 'all') {
                    $output['datatablesTranslation'] = $this->getDataTablesTranslation();
                }
                if ($getData === 'pageAlertMessages' || $getData === 'all') {
                    $output['pageAlertMessages'] = $this->getPageAlertMessages();
                }
            }// endforeach;
            unset($getData);
        }
        unset($getDatas);

        // display, response part ---------------------------------------------------------------------------------------------
        return $this->responseAcceptType($output);
    }// indexAction


}
