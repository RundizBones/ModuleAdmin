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
     * @var array Config value that must be save as `null` if it is empty.
     */
    const configValueNullOnEmpty = [
        'rdbadmin_AdminItemsPerPage',
        'rdbadmin_UserConfirmWait',
        'rdbadmin_UserDeleteSelfKeep',
        'rdbadmin_UserLoginLogsKeep',
        'rdbadmin_UserLoginMaxFail',
        'rdbadmin_UserLoginMaxFailWait',
        'rdbadmin_UserLoginNotRememberLength',
        'rdbadmin_UserLoginRememberLength',
        'rdbadmin_UserRegisterWaitVerification',
    ];


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

        /*
        * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\Settings\SettingsController->__construct
        * PluginHookDescription: Hook after RdbAdmin settings class and constructor method is being called.  
         *      You can use this to bind text domain while plugin have hooks to this controller and views.
        * PluginHookParam: None.
        * PluginHookReturn: None.
        * PluginHookSince: 1.2.9
        */
       $this->Plugins->doHook(
           __CLASS__ . '->' . __FUNCTION__,
           [
           ]
       );
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

        if (session_status() === PHP_SESSION_NONE) {
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
            $dataDesc = [];
            foreach ($this->getConfigNames() as $configName => $configDescription) {
                if (isset($_PATCH[$configName])) {
                    $data[$configName] = $this->Input->patch($configName);
                    $dataDesc[$configName] = $configDescription;
                }

                // sanitize and validate value again.
                if (isset($data[$configName]) && is_scalar($data[$configName])) {
                    // if string, number, scalar...
                    $data[$configName] = trim($data[$configName]);
                    if ($data[$configName] === '' && in_array($configName, static::configValueNullOnEmpty)) {
                        $data[$configName] = null;
                    }
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

            if (!empty($data['rdbadmin_SiteDomain']) && stripos($data['rdbadmin_SiteDomain'], '/') !== false) {
                // if found slash in the domain name.
                $parsedUrl = parse_url($data['rdbadmin_SiteDomain']);
                $data['rdbadmin_SiteDomain'] = ($parsedUrl['host'] ?? '');
                unset($parsedUrl);

                if (empty($data['rdbadmin_SiteDomain']) || filter_var($data['rdbadmin_SiteDomain'], FILTER_VALIDATE_DOMAIN) === false) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Please enter valid domain name.');
                    http_response_code(400);
                    $formValidated = false;
                }
            }

            if (!isset($data['rdbadmin_SiteDomainCheckUsage']) || $data['rdbadmin_SiteDomainCheckUsage'] !== '1') {
                $data['rdbadmin_SiteDomainCheckUsage'] = '0';
            }
            if (
                $formValidated === true && 
                $data['rdbadmin_SiteDomainCheckUsage'] === '1' &&
                (
                    empty($data['rdbadmin_SiteDomain'])
                )
            ) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Please enter domain name.');
                http_response_code(400);
                $formValidated = false;
            }
            if (!isset($data['rdbadmin_SiteAPILimitAccess']) || $data['rdbadmin_SiteAPILimitAccess'] !== '1') {
                $data['rdbadmin_SiteAPILimitAccess'] = '0';
            }
            if (
                $formValidated === true &&
                $data['rdbadmin_SiteAPILimitAccess'] === '1' && 
                (
                    !isset($data['rdbadmin_SiteAPIKey']) ||
                    empty($data['rdbadmin_SiteAPIKey'])
                )
            ) {
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Please enter API key.');
                http_response_code(400);
                $formValidated = false;
            }
            // end form validation. ------------------------------------------------------------------------

            if (isset($formValidated) && $formValidated === true) {
                // if form validation passed.
                // update to DB.
                try {
                    $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
                    $updateResult = $ConfigDb->updateMultipleValues($data, $dataDesc);
                    unset($ConfigDb);

                    if (is_object($this->Plugins)) {
                        /*
                         * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\Settings\SettingsController->doUpdateAction.afterMainUpdate
                         * PluginHookDescription: Hook after RdbAdmin settings was updated in settings page controller.
                         * PluginHookParam: <br>
                         *              array $data The data to update in RdbAdmin module's settings only.<br>
                         *              bool $updateResult The main settings updated result.<br>
                         * PluginHookReturn: None.
                         * PluginHookSince: 1.1.7
                         */
                        /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
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

            unset($data, $dataDesc);
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
        $Sth->execute(array_keys($configNames));
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
     * @return array Return associative array where key is the config name and value is config description.
     */
    protected function getConfigNames(): array
    {
        return [
            'rdbadmin_SiteName' => "Website name.",
            'rdbadmin_SiteDomain' => "A domain for this website.",
            'rdbadmin_SiteDomainCheckUsage' => "Check usage that current requested domain must match site domain or not. Require `rdbadmin_SiteDomain` value.",
            'rdbadmin_SiteTimezone' => "Website timezone.",
            'rdbadmin_SiteAllowOrigins' => "Allow origins for CORS.",
            'rdbadmin_SiteAPILimitAccess' => "0 to not limited access, 1 to limited access and required API key.",
            'rdbadmin_SiteAPIKey' => "API key to access via REST API.",
            'rdbadmin_SiteFavicon' => "Favicon file related from public (root web) path. Do not begins with slash.",
            'rdbadmin_UserRegister' => "0 to not allowed register (add by admin only), 1 to allowed.",
            'rdbadmin_UserRegisterNotifyAdmin' => "Send email to notify admin when new member registered? 0=no, 1=yes.",
            'rdbadmin_UserRegisterNotifyAdminEmails' => "The emails of administrator to notify when new member registered. Use comma (,) to add more than one.",
            'rdbadmin_UserRegisterVerification' => "User registration verification method.\n0=never verify (always activated)\n1=by user''s email\n2=by admin.",
            'rdbadmin_UserRegisterWaitVerification' => "How many days that user needs to take action to verify their email on register or added by admin?",
            'rdbadmin_UserRegisterDisallowedName' => "Disallowed user_login, user_email, user_display_name. Use comma (,) to add multiple values, use double quote to escape and enclosure (\"name contain, comma\").",
            'rdbadmin_UserRegisterDefaultRoles' => "Default roles for newly register user. Use comma (,) to add multiple values.",
            'rdbadmin_UserLoginCaptcha' => "Use captcha for login?\n0=do not use\n1=use until login success and next time do not use it\n2=always use.",
            'rdbadmin_UserLoginBruteforcePreventByIp' => "Use brute-force prevention by IP address?\n0=do not use\n1=use it.",
            'rdbadmin_UserLoginBruteforcePreventByDc' => "Use brute-force prevention by Device cookie?\n0=do not use\n1=use it.",
            'rdbadmin_UserLoginMaxFail' => "Maximum times that client can login failed continuously. (For brute-force prevent by IP).\n\nMaximum times that client can login failed continuously during time period. (For brute-force prevent by Device Cookie).",
            'rdbadmin_UserLoginMaxFailWait' => "How many minutes that client have to wait until they are able to try login again? (For brute-force prevent by IP).\n\nHow many minutes in time period that client can try login until maximum attempts? (For brute-force prevent by Device Cookie).",
            'rdbadmin_UserLoginNotRememberLength' => "How many days to keep cookie when user login without remember ticked? 0 = until browser close",
            'rdbadmin_UserLoginRememberLength' => "How many days that user can remember their logins?",
            'rdbadmin_UserLoginLogsKeep' => "How many days that user logins data to keep in database?",
            'rdbadmin_UserConfirmEmailChange' => "When user change their email, do they need to confirm? 1=yes, 0=no.",
            'rdbadmin_UserConfirmWait' => "How many minutes that the user needs to take action such as confirm reset password, change email?",
            'rdbadmin_UserDeleteSelfGrant' => "Allow user to delete themself?\n0=do not allowed\n1=allowed.",
            'rdbadmin_UserDeleteSelfKeep' => "On delete user wether delete themself or by admin, How many days before it gets actual delete?",
            'rdbadmin_MailProtocol' => "The mail sending protocol.\nmail, sendmail, smtp",
            'rdbadmin_MailPath' => "The sendmail path.",
            'rdbadmin_MailSmtpHost' => "SMTP host",
            'rdbadmin_MailSmtpPort' => "SMTP port",
            'rdbadmin_MailSmtpSecure' => "SMTP encryption\n'''' (empty), ssl, tls",
            'rdbadmin_MailSmtpUser' => "SMTP username",
            'rdbadmin_MailSmtpPass' => "SMTP password",
            'rdbadmin_MailSenderEmail' => "The sender email (send from this email).",
            'rdbadmin_AdminItemsPerPage' => "Number of items will be display per page for admin pages.",
        ];
    }// getConfigNames


    /**
     * Get all timezones using PHP classes.
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
        // This cache has no deletion (no need). It can be cleared from admin > tools menu.
        $cacheKey = 'settingsTimezonesList';
        // This cache's value retrieved from PHP classes. So, it will be rarely changed until admin upgraded PHP version.
        $cacheExpire = (360 * 24 * 60 * 60);// days

        if ($Cache->has($cacheKey)) {
            return $Cache->get($cacheKey);
        } else {
            $timezones = \DateTimeZone::listIdentifiers();
            $options = [];
            $lastRegion = '';
            if (is_array($timezones)) {
                foreach ($timezones as $key => $timezone) {
                    if (!is_scalar($timezone)) {
                        continue;
                    }

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

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        // urls & methods
        $urlAppBased = $Url->getAppBasedPath(true);
        $output['urls'] = [];
        $output['urls']['publicUrl'] = $Url->getPublicUrl();
        $output['urls']['getSettingsUrl'] = $urlAppBased . '/admin/settings';// get settings page, also get data via rest.
        $output['urls']['getSettingsMethod'] = 'GET';
        $output['urls']['editSettingsSubmitUrl'] = $urlAppBased . '/admin/settings';// edit, save settings via rest.
        $output['urls']['editSettingsSubmitMethod'] = 'PATCH';
        $output['urls']['editSettingsTestSmtpConnectionUrl'] = $urlAppBased . '/admin/settings/test-smtp';
        $output['urls']['editSettingsTestSmtpConnectionMethod'] = 'POST';
        $output['urls']['uploadFaviconUrl'] = $urlAppBased . '/admin/settings/favicon';
        $output['urls']['uploadFaviconMethod'] = 'POST';
        $output['urls']['deleteFaviconUrl'] = $urlAppBased . '/admin/settings/favicon';
        $output['urls']['deleteFaviconMethod'] = 'DELETE';
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

        $output['favicon']['recommendedSize'] = FaviconController::recommendedSize();
        $output['favicon']['allowedFileExtensions'] = implode(',', array_map(function($value) {return '.' . $value;}, FaviconController::allowedFileExtensions()));

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
                'css',
                ['rdbaSettingsFavicon'],
                $rdbAdminAssets
            );
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
                    'urls' => $output['urls'],
                    'txtConfirmDelete' => __('Are you sure to delete? This action cannot be undone.'),
                    'txtPleaseChooseOneFile' => __('Please choose one file.'),
                    'txtUploading' => __('Uploading.'),
                ]
            );

            if (is_object($this->Plugins)) {
                /*
                 * PluginHook: Rdb\Modules\RdbAdmin\Controllers\Admin\Settings\SettingsController->indexAction.afterAddAssets
                 * PluginHookDescription: Hook after added assets (such as CSS, JS) in settings page controller.
                 * PluginHookParam: <br>
                 *              \Rdb\Modules\RdbAdmin\Libraries\Assets $Assets The `\Rdb\Modules\RdbAdmin\Libraries\Assets` class.<br>
                 *              array $rdbAdminAssets The RdbAdmin module's assets.<br>
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

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

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
            $data['rdbadmin_MailProtocol'] = strip_tags(trim($this->Input->post('rdbadmin_MailProtocol', '')));
            $data['rdbadmin_MailPath'] = htmlspecialchars(trim($this->Input->post('rdbadmin_MailPath', '')), ENT_QUOTES);
            $data['rdbadmin_MailSmtpHost'] = htmlspecialchars(trim($this->Input->post('rdbadmin_MailSmtpHost', '')), ENT_QUOTES);
            $data['rdbadmin_MailSmtpPort'] = trim($this->Input->post('rdbadmin_MailSmtpPort', '', FILTER_SANITIZE_NUMBER_INT));
            $data['rdbadmin_MailSmtpSecure'] = strip_tags(trim($this->Input->post('rdbadmin_MailSmtpSecure', '')));
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
        unset($Csrf, $Url);
        return $this->responseAcceptType($output);
    }// testSmtpAction


}
