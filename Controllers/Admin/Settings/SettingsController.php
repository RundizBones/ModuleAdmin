<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Settings;


/**
 * Settings controller.
 * 
 * @since 0.1
 */
class SettingsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    /**
     * @since 1.1.7
     * @var \Rdb\Modules\RdbAdmin\Libraries\Plugins
     */
    protected $Plugins;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        if ($Container->has('Plugins')) {
            $this->Plugins = $Container->get('Plugins');
        }
    }// __construct


    /**
     * Do update.
     * 
     * @global array $_PATCH
     * @return string
     */
    public function doUpdateAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminSettings', ['changeSettings']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf(['persistentTokenMode' => true]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $Serializer = new \Rundiz\Serializer\Serializer();

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
            // if validated csrf token passed.
            unset($_PATCH[$csrfName], $_PATCH[$csrfValue]);

            // prepare data for save.
            $data = [];
            foreach ($this->getConfigNames() as $configName) {
                if (isset($_PATCH[$configName])) {
                    $data[$configName] = $this->Input->patch($configName);
                }

                // sanitize and validate value again.
                if (isset($data[$configName]) && is_scalar($data[$configName])) {
                    // if string, number, scalar...
                    $data[$configName] = trim($data[$configName]);
                } else {
                    // if anything else... (array, object, null or anything non scalar).
                    if ($configName === 'rdbadmin_UserRegisterDefaultRoles' && isset($data[$configName])) {
                        // if config name is default roles.
                        // use comma to merge array values into string.
                        $data[$configName] = implode(',', $data[$configName]);
                    } elseif (isset($data[$configName]) && !is_null($data[$configName])) {
                        // if the rest of config ... and it is not null.
                        // serialize it.
                        $data[$configName] = $Serializer->maybeSerialize($data[$configName]);
                    }
                }
            }
            unset($configName);

            // form validation. ----------------------------------------------------------------------------
            $formValidated = true;
            if (isset($data['rdbadmin_SiteName']) && empty($data['rdbadmin_SiteName'])) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Please enter all required fields.');
                http_response_code(400);
                $formValidated = false;
            }
            // end form validation. ------------------------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                // if form validation passed.
                // update to DB.
                try {
                    $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
                    $updateResult = $ConfigDb->updateMultipleValues($data);
                    unset($ConfigDb);

                    if (is_object($this->Plugins)) {
                        /*
                         * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\Settings\SettingsController->doUpdateAction.afterMainUpdate
                         * PluginHookDescription: Hook after RdbAdmin settings was updated in settings page controller.
                         * PluginHookParam: <br>
                         *              `data` (array) The data to update in RdbAdmin module's settings only.<br>
                         *              `updateResult` (bool) The main settings updated result.<br>
                         * PluginHookReturn: None.
                         * PluginHookSince: 1.1.7
                         */
                        $Plugins = $this->Container->get('Plugins');
                        $updateResultFromHooks = $Plugins->doHook(
                            __CLASS__ . '->' . __FUNCTION__ . '.afterMainUpdate',
                            [
                                'data' => $data,
                                'updateResult' => $updateResult,
                            ]
                        );

                        if (is_array($updateResultFromHooks) && true === $updateResult) {
                            foreach ($updateResultFromHooks as $eachResult) {
                                if (false === $eachResult) {
                                    $updateResult = false;
                                    break;
                                }
                            }// endforeach;
                            unset($eachResult);
                        }
                        unset($updateResultFromHooks);
                    }// endif; Plugins
                } catch (\Exception $e) {
                    $output['exceptionMessage'] = $e->getMessage();
                }

                if (isset($updateResult) && $updateResult === true) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Updated successfully.');
                    http_response_code(200);
                } else {
                    $output['formResultStatus'] = 'warning';
                    $output['formResultMessage'] = __('Some setting value has not been update, please reload the page and see what was changed.');
                    if (isset($output['exceptionMessage'])) {
                        $output['formResultMessage'] .= '<br>' . PHP_EOL . $output['exceptionMessage'];
                    }
                    http_response_code(400);
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
        unset($Csrf, $Serializer, $Url);
        return $this->responseAcceptType($output);
    }// doUpdateAction


    /**
     * Get list of **ALL** roles (for display in select box in default role option).
     * 
     * @return array
     */
    protected function getRoles(): array
    {
        $options = [];
        $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
        $options['unlimited'] = true;
        $options['where'] = [
            'userrole_priority' => '< 10000',
        ];
        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
        $output = $UserRolesDb->listItems($options);

        unset($options, $UserRolesDb);
        return $output;
    }// getRoles


    /**
     * Get config (settings) values.
     * 
     * @return array
     */
    protected function getConfigData(): array
    {
        $configNames = $this->getConfigNames();

        // get data from db not model to get it fresh (without cache).
        $placeholders = array_fill(0, (int) count($configNames), '?');
        $sql = 'SELECT * FROM `' . $this->Db->tableName('config') . '` WHERE `config_name` IN (' . implode(', ', $placeholders) . ')';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($placeholders, $sql);
        $Sth->execute($configNames);
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);

        if (is_array($result)) {
            return $result;
        }
        return [];
    }// getConfigData


    /**
     * Get config names that will be work on settings page.
     * 
     * This method was called from `getConfigData()`, `doUpdateAction()` methods.
     * 
     * @return array
     */
    protected function getConfigNames(): array
    {
        return [
            'rdbadmin_SiteName',
            'rdbadmin_SiteTimezone',
            'rdbadmin_UserRegister',
            'rdbadmin_UserRegisterNotifyAdmin',
            'rdbadmin_UserRegisterNotifyAdminEmails',
            'rdbadmin_UserRegisterVerification',
            'rdbadmin_UserRegisterWaitVerification',
            'rdbadmin_UserRegisterDisallowedName',
            'rdbadmin_UserRegisterDefaultRoles',
            'rdbadmin_UserLoginCaptcha',
            'rdbadmin_UserLoginBruteforcePreventByIp',
            'rdbadmin_UserLoginBruteforcePreventByDc',
            'rdbadmin_UserLoginMaxFail',
            'rdbadmin_UserLoginMaxFailWait',
            'rdbadmin_UserLoginNotRememberLength',
            'rdbadmin_UserLoginRememberLength',
            'rdbadmin_UserLoginLogsKeep',
            'rdbadmin_UserConfirmEmailChange',
            'rdbadmin_UserConfirmWait',
            'rdbadmin_UserDeleteSelfGrant',
            'rdbadmin_UserDeleteSelfKeep',
            'rdbadmin_MailProtocol',
            'rdbadmin_MailPath',
            'rdbadmin_MailSmtpHost',
            'rdbadmin_MailSmtpPort',
            'rdbadmin_MailSmtpSecure',
            'rdbadmin_MailSmtpUser',
            'rdbadmin_MailSmtpPass',
            'rdbadmin_MailSenderEmail',
            'rdbadmin_AdminItemsPerPage'
        ];
    }// getConfigNames


    /**
     * Get all timezones using PHP class.
     * 
     * @return array
     */
    protected function getTimezones(): array
    {
        $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
            $this->Container,
            [
                'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Controllers/Admin/Settings/SettingsController',
            ]
        ))->getCacheObject();
        $cacheKey = 'timezonesList';
        $cacheExpire = (15 * 24 * 60 * 60);// 15 days

        if ($Cache->has($cacheKey)) {
            return $Cache->get($cacheKey);
        } else {
            $timezones = \DateTimeZone::listIdentifiers();
            $options = [];
            $lastRegion = '';
            if (is_array($timezones)) {
                foreach ($timezones as $key => $timezone) {
                    $DateTimeZone = new \DateTimeZone($timezone);
                    $expTimezone = explode('/', $timezone);
                    if (isset($expTimezone[0])) {
                        if ($expTimezone[0] !== $lastRegion) {
                            $lastRegion = $expTimezone[0];
                        }
                        $getOffset = $DateTimeZone->getOffset(new \DateTime());
                        $offset = ($getOffset < 0 ? '-' : '+');
                        $offset .= gmdate('H:i', abs($getOffset));
                        $options[$expTimezone[0]][$timezone] = [
                            'name' => $timezone,
                            'offset' => $offset,
                        ];
                        unset($getOffset, $offset);
                    }
                    unset($DateTimeZone, $expTimezone);
                }// endforeach;
                unset($key, $timezone);
            }
            unset($lastRegion, $timezones);

            if (!empty($options)) {
                $Cache->set($cacheKey, $options, $cacheExpire);
            }
            return $options;
        }
    }// getTimezones


    /**
     * Main settings page.
     * 
     * @link https://www.w3schools.com/howto/howto_js_filter_lists.asp JS search html content example.
     * @link https://github.com/stidges/jquery-searchable as JS library.
     * @link https://listjs.com/ as JS library.
     * @link https://github.com/bvaughn/js-search as JS library.
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminSettings', ['changeSettings']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        // urls & methods
        $urlAppBased = $Url->getAppBasedPath(true);
        $output['urls'] = [];
        $output['urls']['getSettingsUrl'] = $urlAppBased . '/admin/settings';// get settings page, also get data via rest.
        $output['urls']['getSettingsMethod'] = 'GET';
        $output['urls']['editSettingsSubmitUrl'] = $urlAppBased . '/admin/settings';// edit, save settings via rest.
        $output['urls']['editSettingsSubmitMethod'] = 'PATCH';
        $output['urls']['editSettingsTestSmtpConnectionUrl'] = $urlAppBased . '/admin/settings/test-smtp';
        $output['urls']['editSettingsTestSmtpConnectionMethod'] = 'POST';
        unset($urlAppBased);

        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if request via REST API or AJAX
            $output['configData'] = $this->getConfigData();
        }

        $output['pageTitle'] = __('Main settings');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();
        $output['timezones'] = $this->getTimezones();
        $output['listRoles'] = $this->getRoles();

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom HTTP accept, response content.
            // response the data.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets(
                'js', 
                ['rdbaSettings'], 
                $rdbAdminAssets
            );
            $Assets->addJsObject(
                'rdbaSettings',
                'RdbaSettings',
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'getSettingsUrl' => $output['urls']['getSettingsUrl'],
                    'getSettingsMethod' => $output['urls']['getSettingsMethod'],
                    'editSettingsSubmitUrl' => $output['urls']['editSettingsSubmitUrl'],
                    'editSettingsSubmitMethod' => $output['urls']['editSettingsSubmitMethod'],
                    'editSettingsTestSmtpConnectionUrl' => $output['urls']['editSettingsTestSmtpConnectionUrl'],
                    'editSettingsTestSmtpConnectionMethod' => $output['urls']['editSettingsTestSmtpConnectionMethod'],
                ]
            );

            if (is_object($this->Plugins)) {
                /*
                 * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\Settings\SettingsController->indexAction.afterAddAssets
                 * PluginHookDescription: Hook after added assets in settings page controller.
                 * PluginHookParam: <br>
                 *              `Assets` (object) The `\Rdb\Modules\RdbAdmin\Libraries\Assets` class.<br>
                 *              `rdbAdminAssets` (array) The RdbAdmin module's assets.<br>
                 * PluginHookReturn: None.
                 * PluginHookSince: 1.1.7
                 */
                $Plugins = $this->Container->get('Plugins');
                $Plugins->doHook(
                    __CLASS__ . '->' . __FUNCTION__ . '.afterAddAssets',
                    [
                        'Assets' => $Assets,
                        'rdbAdminAssets' => $rdbAdminAssets,
                    ]
                );
            }// endif; Plugins

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Settings/index_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


    /**
     * Test SMTP connection.
     * 
     * @link https://github.com/PHPMailer/PHPMailer/blob/master/examples/smtp_check.phps Original source code of test smtp.
     * @return string
     */
    public function testSmtpAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminSettings', ['changeSettings']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $Serializer = new \Rundiz\Serializer\Serializer();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated csrf token passed.
            unset($_POST[$csrfName], $_POST[$csrfValue]);

            // prepare data
            $data = [];
            $data['rdbadmin_MailProtocol'] = trim($this->Input->post('rdbadmin_MailProtocol', '', FILTER_SANITIZE_STRING));
            $data['rdbadmin_MailPath'] = trim($this->Input->post('rdbadmin_MailPath', '', FILTER_SANITIZE_STRING));
            $data['rdbadmin_MailSmtpHost'] = trim($this->Input->post('rdbadmin_MailSmtpHost', '', FILTER_SANITIZE_STRING));
            $data['rdbadmin_MailSmtpPort'] = trim($this->Input->post('rdbadmin_MailSmtpPort', '', FILTER_SANITIZE_NUMBER_INT));
            $data['rdbadmin_MailSmtpSecure'] = trim($this->Input->post('rdbadmin_MailSmtpSecure', '', FILTER_SANITIZE_STRING));
            $data['rdbadmin_MailSmtpUser'] = trim($this->Input->post('rdbadmin_MailSmtpUser'));
            $data['rdbadmin_MailSmtpPass'] = trim($this->Input->post('rdbadmin_MailSmtpPass'));
            if (empty($data['rdbadmin_MailSmtpPort'])) {
                $data['rdbadmin_MailSmtpPort'] = 25;
            }

            $validated = false;
            if (strtolower($data['rdbadmin_MailProtocol']) !== 'smtp') {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to test other mail protocol.');
                http_response_code(400);
                $validated = false;
            } else {
                $validated = true;
            }

            if (isset($validated) && $validated === true) {
                $Smtp = new \PHPMailer\PHPMailer\SMTP();
                $Smtp->do_debug = \PHPMailer\PHPMailer\SMTP::DEBUG_LOWLEVEL;
                global $debugMessage;
                $debugMessage = '';
                $Smtp->Debugoutput = function($str, $level) {
                    global $debugMessage;
                    $debugMessage .= 'debug level: ' . $level . ': ' . $str . '<br>' . PHP_EOL;
                };

                try {
                    // test connect to server.
                    if (!$Smtp->connect((strtolower($data['rdbadmin_MailSmtpSecure']) === 'ssl' ? 'ssl://' : '') . $data['rdbadmin_MailSmtpHost'], $data['rdbadmin_MailSmtpPort'])) {
                        throw new \Exception('Connect failed');
                    }

                    // say hello.
                    if (!$Smtp->hello(gethostname())) {
                        throw new \Exception('EHLO failed: ' . $Smtp->getError()['error']);
                    }

                    // test tls.
                    if (strtolower($data['rdbadmin_MailSmtpSecure']) === 'tls') {
                        $tlsok = @$Smtp->startTLS();
                        if (!$tlsok) {
                            // set error and return now because it was stucked since called `$Smtp->startTLS()`. The throw error cannot be shown.
                            $output['formResultStatus'] = 'error';
                            $output['formResultMessage'] = __('An error has been occurred.') . ' ' . $Smtp->getError()['error'];
                            http_response_code(500);
                            $output['debugMessage'] = $debugMessage;
                            $output = array_merge($output, $Csrf->createToken());
                            return $this->responseAcceptType($output);
                        }
                        // Repeat EHLO after STARTTLS
                        if (!$Smtp->hello(gethostname())) {
                            throw new \Exception('EHLO (2) failed: ' . $Smtp->getError()['error']);
                        }
                    }

                    // test auth.
                    if (!empty($data['rdbadmin_MailSmtpUser']) || !empty($data['rdbadmin_MailSmtpPass'])) {
                        if (!$Smtp->authenticate($data['rdbadmin_MailSmtpUser'], $data['rdbadmin_MailSmtpPass'])) {
                            throw new \Exception('Authentication failed: ' . $Smtp->getError()['error']);
                        }
                    }

                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Connection was successfull.');
                } catch (\Exception $ex) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('An error has been occurred.') . ' ' . $ex->getMessage();
                    http_response_code(500);
                }

                $Smtp->quit();
                $Smtp->close();
                unset($Smtp);

                $output['debugMessage'] = $debugMessage;
                unset($debugMessage);
            }

            unset($data, $validated);
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
        unset($Csrf, $Serializer, $Url);
        return $this->responseAcceptType($output);
    }// testSmtpAction


}
