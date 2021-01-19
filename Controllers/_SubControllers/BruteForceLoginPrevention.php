<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\_SubControllers;


/**
 * Brute-force attack on login prevention.
 * 
 * @link https://www.owasp.org/index.php/Slow_Down_Online_Guessing_Attacks_with_Device_Cookies OWASP device cookie document.
 */
class BruteForceLoginPrevention extends \Rdb\Modules\RdbAdmin\Controllers\BaseController
{


    /**
     * @var \Psr\SimpleCache\CacheInterface 
     */
    protected $Cache;


    /**
     * @var array The configuration values in DB.
     */
    protected $configDb = [];


    /**
     * @var string The cache based folder.
     */
    protected $cacheBasedPath = STORAGE_PATH . '/cache/Modules/RdbAdmin/Controllers/_SubControllers/BruteForceLoginPrevention';


    /**
     * @var string The cache name of device cookie based failed attempts.
     */
    //protected $cacheNameDeviceCookieFailedAttempts = 'devicecookieBasedFailedAttempts';


    /**
     * @var string The cache name of device cookie based accounts lockout.
     */
    //protected $cacheNameDeviceCookieLockout = 'devicecookieLockout';


    /**
     * @var string The cache name of total login failed count. 
     */
    protected $cacheNameFailCount;


    /**
     * @var string The cache name of latest login failed timestamp.
     */
    protected $cacheNameFailTime;


    /**
     * @var string The error message after called to `getDeviceCookie()` method.
     */
    protected $deviceCookieError;


    /**
     * @var int The number of days that this cookie will be expired.
     */
    protected $deviceCookieExpire = 730;


    /**
     * @var string The name of device cookie.
     */
    protected $deviceCookieName = 'rdbadmin_cookie_devicecookie';


    /**
     * @var string|null The device cookie signature string. This will be set after called to `issueNewDeviceCookie()` method.
     */
    protected $deviceCookieSignature;


    /**
     * @var array The decoded device cookie value after called to `getDeviceCookie()`, `validateDeviceCookie()` methods.
     */
    protected $deviceCookieValue = [];


    /**
     * {@inheritDoc}
     * 
     * @param array $configDb The configuration values in DB.
     */
    public function __construct(\Rdb\System\Container $Container, array $configDb = [])
    {
        parent::__construct($Container);

        $this->Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
            $this->Container,
            ['cachePath' => $this->cacheBasedPath]
        ))->getCacheObject();
        $this->cacheNameFailCount = 'ipBasedFailedAttempts.' . str_replace(['.', '/', '\\'], '', $this->Input->server('REMOTE_ADDR', '0.0.0.0')) . '.loginFailedCount';
        $this->cacheNameFailTime = 'ipBasedFailedAttempts.' . str_replace(['.', '/', '\\'], '', $this->Input->server('REMOTE_ADDR', '0.0.0.0')) . '.loginFailedTime';

        $this->configDb = $configDb;

        if ($this->Container->has('Config')) {
            $Config = $this->Container->get('Config');
            $Config->setModule('');
        } else {
            $Config = new \Rdb\System\Config();
        }
        $this->deviceCookieName = 'rdbadmin_cookie_devicecookie' . $Config->get('suffix', 'cookie');
        unset($Config);
    }// __construct


    /**
     * Magic __get
     */
    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
    }// __get


    /**
     * Check for brute-force status.
     * 
     * Call this method before check login credentials. Check the return array key that `status` must be `authenticate` only.
     * Otherwise show the error message to user.
     * 
     * @param string $user_login_email The input login identity (username or email depend on how system use it).
     * @return array Return associative array with these keys.<br>
     *                          `status` will be `authenticate` on success or no problem, `reject` if there is a problem, `rejectvalid` if there is valid device cookie but enter incorrect credentials.<br>
     *                              In case of `rejectvalid` I recommend to send login link with special token to user's email.<br>
     *                          `totalFailed` (may exists or not) Total failed count.<br>
     *                          `failedTime` (may exists or not) The latest failed timestamp.<br>
     *                          `waitUntil` (for failed status such as `reject`, `rejectvalid`) The date/time format for wait until it gets unlocked.<br>
     *                          `errorMessage` (for failed status such as `reject`, `rejectvalid`) The error message.<br>
     *                          `statusCode` (may exists or not) The HTTP response status code.
     *                          `resultBy` (only visible on development mode) Result by which protection. It can be 'ipBase', 'deviceCookie'.
     */
    public function checkBruteForceStatus(string $user_login_email = null): array
    {
        $output = [];
        $configMaxFailed = (int) ($this->configDb['rdbadmin_UserLoginMaxFail'] ?? 10);
        $configMaxFailedWait = ($this->configDb['rdbadmin_UserLoginMaxFailWait'] ?? 60);

        // check brute-force status based on IP. ---------------------------------------------------------------------------
        if (
            isset($this->configDb['rdbadmin_UserLoginBruteforcePreventByIp']) && 
            $this->configDb['rdbadmin_UserLoginBruteforcePreventByIp'] === '1'
        ) {
            // if enabled brute force prevention by IP.
            $bfIpBasedFailedCount = $this->Cache->get($this->cacheNameFailCount, 0);
            $bfIpBasedFailedTime = $this->Cache->get($this->cacheNameFailTime, false);
            $bfIpBasedWaitLeft = ($bfIpBasedFailedTime !== false ? ((time() - $bfIpBasedFailedTime) / 60) : 0);

            if (
                $bfIpBasedFailedCount > $configMaxFailed &&
                $bfIpBasedFailedTime !== false &&
                $bfIpBasedWaitLeft > 0 &&
                $bfIpBasedWaitLeft <= $configMaxFailedWait
            ) {
                // if total login failed is over maximum limit AND currently wait time is less than or equal to max wait time (still in wait time limit).
                $output['status'] = 'reject';
                $output['totalFailed'] = $bfIpBasedFailedCount;
                $output['failedTime'] = $bfIpBasedFailedTime;
                $output['waitUntil'] = date('Y-m-d H:i:s', time()+($configMaxFailedWait * 60));
                $output['errorMessage'] = __(
                    'You have continuous login failed over %1$d times, you have to wait for %2$d minutes. Please try again in %3$s.', 
                    $configMaxFailed, 
                    $configMaxFailedWait, 
                    $output['waitUntil']
                );
                $output['statusCode'] = 429;
            } else {
                // login failed (IP based) is not reach maximum limit.
                if (
                    $bfIpBasedFailedCount > $configMaxFailed &&
                    $bfIpBasedFailedTime !== false &&
                    $bfIpBasedWaitLeft > 0 &&
                    $bfIpBasedWaitLeft > $configMaxFailedWait
                ) {
                    // if total login failed is over maximum limit AND currently wait time is more than max wait time (over wait time limit).
                    // clear login failed count to let user start again.
                    $this->Cache->deleteMultiple([$this->cacheNameFailCount, $this->cacheNameFailTime]);
                }

                $output['status'] = 'authenticate';
                $output['totalFailed'] = $bfIpBasedFailedCount;
                $output['failedTime'] = $bfIpBasedFailedTime;
            }

            if (defined('APP_ENV') && APP_ENV === 'development') {
                $output['resultBy'] = 'ipBase';
            }
        }
        // end check brute-force status based on IP. ----------------------------------------------------------------------

        // check brute-force status based on Device Cookie. --------------------------------------------------------------
        if (
            isset($this->configDb['rdbadmin_UserLoginBruteforcePreventByDc']) && 
            $this->configDb['rdbadmin_UserLoginBruteforcePreventByDc'] === '1' &&
            (
                !isset($output['status']) ||
                (isset($output['status']) && $output['status'] === 'authenticate')
            )
        ) {
            // if enabled brute force prevention by Device Cookie.
            // (even if it was checked and passed by IP based - if IP based enabled, then check again with device cookie based).
            // Entry point for authentication request
            $UserLoginsDb = new \Rdb\Modules\RdbAdmin\Models\UserLoginsDb($this->Container);
            $output = [];// start fresh to prevent mess up with previous check.

            if (isset($_COOKIE[$this->deviceCookieName])) {
                // 1. if the incoming request contains a device cookie.
                // --- a. validate device cookie
                if ($this->validateDeviceCookie($user_login_email) !== true) {
                    // b. if device cookie is not valid.
                    // proceed to step 2.
                    $this->removeDeviceCookie();
                    $step2 = true;
                } elseif ($UserLoginsDb->dcIsInLockoutList(null, ($this->deviceCookieValue['jti'] ?? null))) {
                    // c. if the device cookie is in the lockout list (valid but in lockout list).
                    // reject authentication attempt∎
                    $output['status'] = 'rejectvalid';
                    if (is_array($UserLoginsDb->dcLockoutListResult)) {
                        $row = $UserLoginsDb->dcLockoutListResult[0];
                        $output['failedTime'] = strtotime($row->userlogin_date);
                        $output['waitUntil'] = $row->userlogin_dc_lockout_until;
                        unset($row);
                    } else {
                        $output['waitUntil'] = date('Y-m-d H:i:s', time()+($configMaxFailedWait * 60));
                    }
                    $output['errorMessage'] = __(
                        'You have continuous login failed over %1$d times, you have to wait for %2$d minutes. Please try again in %3$s.', 
                        $configMaxFailed, 
                        $configMaxFailedWait, 
                        $output['waitUntil']
                    );
                    $output['statusCode'] = 429;
                } else {
                    // d. else
                    // authenticate user∎
                    $output['status'] = 'authenticate';
                }
            } else {
                $step2 = true;
            }

            if (isset($step2) && $step2 === true) {
                if ($UserLoginsDb->dcIsInLockoutList($user_login_email, ($this->deviceCookieValue['jti'] ?? null))) {
                    // 2. if authentication from untrusted clients is locked out for the specific user.
                    // reject authentication attempt∎
                    $output['status'] = 'reject';
                    if (is_array($UserLoginsDb->dcLockoutListResult)) {
                        $row = $UserLoginsDb->dcLockoutListResult[0];
                        $output['failedTime'] = strtotime($row->userlogin_date);
                        $output['waitUntil'] = $row->userlogin_dc_lockout_until;
                        unset($row);
                    } else {
                        $output['waitUntil'] = date('Y-m-d H:i:s', time()+($configMaxFailedWait * 60));
                    }
                    $output['errorMessage'] = __(
                        'You have continuous login failed over %1$d times, you have to wait for %2$d minutes. Please try again in %3$s.', 
                        $configMaxFailed, 
                        $configMaxFailedWait, 
                        $output['waitUntil']
                    );
                    $output['statusCode'] = 429;
                } else {
                    // 3. else
                    // authenticate user∎
                    $output['status'] = 'authenticate';
                }
            } else {
                if (empty($output) || !isset($output['status'])) {
                    // if there is something that should not happens.
                    $output['status'] = 'reject';
                    $output['waitUntil'] = date('Y-m-d H:i:s', time()+($configMaxFailedWait * 60));
                    $output['errorMessage'] = __('Please reload the page and try again.');
                    $output['statusCode'] = 403;
                }
            }

            if (isset($output['status']) && $output['status'] === 'authenticate') {
                $where = [];
                if (stripos($user_login_email, '@') !== false) {
                    $where['user_email'] = $user_login_email;
                } else {
                    $where['user_login'] = $user_login_email;
                }
                $where['userlogin_result'] = 0;
                $where['userlogin_dc_sign'] = ($this->deviceCookieValue['jti'] ?? null);
                $output['totalFailed'] = $UserLoginsDb->dcCountFailedAttemptInPeriod($configMaxFailedWait, $where);
                unset($where);
            }

            unset($step2, $UserLoginsDb);

            if (defined('APP_ENV') && APP_ENV === 'development') {
                $output['resultBy'] = 'deviceCookie';
            }
        }
        // end check brute-force status based on Device Cookie. ---------------------------------------------------------

        if (!isset($output['status'])) {
            // if all brute force prevention maybe disabled.
            $output['status'] = 'authenticate';
        }

        return $output;
    }// checkBruteForceStatus


    /**
     * Delete brute-force locked based on IP.
     * 
     * Call this method once login success.
     */
    public function deleteBruteForceIpStatus()
    {
        $this->Cache->deleteMultiple([$this->cacheNameFailCount, $this->cacheNameFailTime]);
    }// deleteBruteForceIpStatus


    /**
     * Get device cookie and decode it.
     * 
     * Once get the cookie and decode successfully, you can access the data from return value or via `deviceCookieValue` property.
     * 
     * @return array Return array content of device cookie. If it is not exists or something wrong then it will return empty array.
     */
    protected function getDeviceCookie(): array
    {
        $this->deviceCookieError = null;
        $this->deviceCookieValue = [];

        if (isset($_COOKIE[$this->deviceCookieName])) {
            $cookieValue = $_COOKIE[$this->deviceCookieName];

            if ($this->Container->has('Config')) {
                $Config = $this->Container->get('Config');
            } else {
                $Config = new \Rdb\System\Config();
            }
            $Config->setModule('RdbAdmin');

            // sometime the server time maybe unsync or skew times.
            \Firebase\JWT\JWT::$leeway = 60;// in seconds.
            $secretKey = $Config->get('rdbaDeviceCookieSecret', 'hash');
            $Config->setModule('');// restore to default.
            unset($Config);

            try {
                $decoded = (array) \Firebase\JWT\JWT::decode($cookieValue, $secretKey, ['HS256', 'HS512']);
            } catch (\Exception $ex) {
                $this->deviceCookieError = 'Error: ' . $ex->getMessage();
                return [];
            }

            if (is_array($decoded) && !empty($decoded)) {
                $this->deviceCookieValue = $decoded;
                return $decoded;
            }
        }

        return [];
    }// getDeviceCookie


    /**
     * Issue new device cookie to user’s client
     * 
     * Call this method once login success.<br>
     * This method will send the set cookie header to client.
     * 
     * @param string $user_login_email The input login identity (username or email depend on how system use it).
     */
    public function issueNewDeviceCookie(string $user_login_email)
    {
        if (
            isset($this->configDb['rdbadmin_UserLoginBruteforcePreventByDc']) && 
            $this->configDb['rdbadmin_UserLoginBruteforcePreventByDc'] === '1'
        ) {
            // if enabled brute force prevention by Device Cookie.
            if ($this->Container->has('Config')) {
                $Config = $this->Container->get('Config');
            } else {
                $Config = new \Rdb\System\Config();
            }
            $Config->setModule('RdbAdmin');

            $secretKey = $Config->get('rdbaDeviceCookieSecret', 'hash');
            $expires = (time() + ($this->deviceCookieExpire * 24 * 60 * 60));
            $this->deviceCookieSignature = hash_hmac('sha512', $user_login_email . ',' . $expires . ',' . $secretKey, $secretKey);
            $token = [
                'aud' => 'device-cookie',
                'exp' => $expires,
                'jti' => $this->deviceCookieSignature,
                'sub' => $user_login_email,
            ];
            $encoded = \Firebase\JWT\JWT::encode($token, $secretKey, 'HS512');

            $Config->setModule('');// restore to default.

            setcookie($this->deviceCookieName, $encoded, $expires, '/');
        }
    }// issueNewDeviceCookie


    /**
     * Register failed authentication.
     * 
     * Call this method once login failed.
     * 
     * @param array $data The associative array with these data.<br>
     *                                  `user_id` If in case that found user_login or email.<br>
     *                                  `user_login_email` User login (username) or email. Depend on how login system use this identity. Use for validate device cookie only.<br>
     *                                  `userlogin_result_text` Login result text (for record login failed).<br>
     *                                  `userlogin_result_text_data` For replace placeholder in `userlogin_result_text` if present.
     */
    public function registerFailedAuth(array $data = [])
    {
        // register authentication failed based on IP. ----------------------------------------------------------------------
        if (
            isset($this->configDb['rdbadmin_UserLoginBruteforcePreventByIp']) && 
            $this->configDb['rdbadmin_UserLoginBruteforcePreventByIp'] === '1'
        ) {
            // if enabled brute force prevention by IP.
            if ($this->Cache->has($this->cacheNameFailCount)) {
                $loginFailedCount = (int) $this->Cache->get($this->cacheNameFailCount, 0);
            } else {
                $loginFailedCount = 0;
            }
            $bfIpBasedCacheTTL = (int) (($this->configDb['rdbadmin_UserLoginMaxFailWait'] ?? 60) * 60);

            $this->Cache->set($this->cacheNameFailCount, (int) ($loginFailedCount + 1), $bfIpBasedCacheTTL);
            $this->Cache->set($this->cacheNameFailTime, time(), $bfIpBasedCacheTTL);
            unset($bfIpBasedCacheTTL, $loginFailedCount);
        }
        // end register authentication failed based on IP. -----------------------------------------------------------------

        // register authentication failed based on device cookie. ---------------------------------------------------------
        if (
            isset($this->configDb['rdbadmin_UserLoginBruteforcePreventByDc']) && 
            $this->configDb['rdbadmin_UserLoginBruteforcePreventByDc'] === '1' &&
            isset($data['user_id']) &&
            isset($data['user_login_email'])
        ) {
            // if enabled brute force prevention by Device Cookie.
            // Register failed authentication attempt

            // get additional data from previous device cookie.
            if (isset($data['user_login_email']) && $this->validateDeviceCookie($data['user_login_email']) === true) {
                // if a valid device cookie presented.
                $validDeviceCookie = true;// mark that valid device cookie is presented.

                if (isset($this->deviceCookieValue['jti']) && !empty($this->deviceCookieValue['jti'])) {
                    $data['userlogin_dc_sign'] = $this->deviceCookieValue['jti'];
                } else {
                    $data['userlogin_dc_sign'] = null;
                    unset($validDeviceCookie);
                }
            }
            // set additional data.
            $data['userlogin_result'] = 0;
            // remove data that is not exists in table fields before insert/update to db.
            unset($data['user_login_email']);

            $UserLoginsDb = new \Rdb\Modules\RdbAdmin\Models\UserLoginsDb($this->Container);
            $configTimePeriod = (int) ($this->configDb['rdbadmin_UserLoginMaxFailWait'] ?? 60);

            // 1. register a failed authentication attempt
            $UserLoginsDb->recordLogins($data);

            // 2. depending on whether a valid device cookie is present in the request, 
            // count the number of failed authentication attempts within period T
            $where = [];
            $where['user_id'] = $data['user_id'];
            $where['userlogin_result'] = 0;
            if (
                !isset($validDeviceCookie) || 
                (isset($validDeviceCookie) && $validDeviceCookie !== true) || 
                !isset($data['userlogin_dc_sign'])
            ) {
                // a. all untrusted clients
                $where['userlogin_dc_sign'] = null;
            } else {
                // b. a specific device cookie
                $where['userlogin_dc_sign'] = $data['userlogin_dc_sign'];
            }
            $bfDcBasedFailedAttempts = $UserLoginsDb->dcCountFailedAttemptInPeriod($configTimePeriod, $where);

            // 3. if "number of failed attempts within period T" > N
            if ($bfDcBasedFailedAttempts > (int) ($this->configDb['rdbadmin_UserLoginMaxFail'] ?? 10)) {
                // if total failed in time period > max attempts allowed.
                $dataUpdate = [];
                $Datetime = new \Datetime();
                $Datetime->add(new \DateInterval('PT' . $configTimePeriod . 'M'));
                $dataUpdate['userlogin_dc_lockout_until'] = $Datetime->format('Y-m-d H:i:s');
                $Datetime->setTimezone(new \DateTimeZone('UTC'));
                $dataUpdate['userlogin_dc_lockout_until_gmt'] = $Datetime->format('Y-m-d H:i:s');
                unset($Datetime);

                if (
                    isset($validDeviceCookie) && 
                    $validDeviceCookie === true
                ) {
                    // a. if a valid device cookie is presented
                    // put the device cookie into the lockout list for device cookies until now+T
                    $dataUpdate['userlogin_dc_lockout'] = 2;
                } else {
                    // b. else
                    // lockout all authentication attempts for a specific user from all untrusted clients until now+T
                    $dataUpdate['userlogin_dc_lockout'] = 1;
                }

                $UserLoginsDb->dcLockoutUser($configTimePeriod, $dataUpdate, $where);
                unset($dataUpdate);
            }
            unset($bfDcBasedFailedAttempts, $configTimePeriod, $where);
        }
        // end register authentication failed based on device cookie. ----------------------------------------------------
    }// registerFailedAuth


    /**
     * Remove a device cookie.
     * 
     * Send set cookie header to client that it is expired.
     */
    protected function removeDeviceCookie()
    {
        $expires = (time() - ($this->deviceCookieExpire * 24 * 60 * 60));
        setcookie($this->deviceCookieName, '', $expires, '/');
    }// removeDeviceCookie


    /**
     * Validate device cookie.
     * 
     * This method will call `getDeviceCookie()` method which is allow you to access device cookie value via `deviceCookieValue` property.
     *
     * @partam string $userLoginEmail The input login identity (username or email depend on how system use it).
     * @return bool Return `true` if device cookie is correct and the `login` contain in the cookie is matched the user who is trying to authenticate. Return `false` for otherwise.
     */
    protected function validateDeviceCookie(string $userLoginEmail): bool
    {
        if (isset($_COOKIE[$this->deviceCookieName])) {
            $cookieValue = $this->getDeviceCookie();

            if (empty($cookieValue)) {
                return false;
            }

            if ($this->Container->has('Config')) {
                $Config = $this->Container->get('Config');
            } else {
                $Config = new \Rdb\System\Config();
            }
            $Config->setModule('RdbAdmin');

            $secretKey = $Config->get('rdbaDeviceCookieSecret', 'hash');
            $Config->setModule('');// restore to default.
            unset($Config);

            if (isset($cookieValue['aud']) && isset($cookieValue['exp']) && isset($cookieValue['jti']) && isset($cookieValue['sub'])) {
                if ($cookieValue['sub'] === $userLoginEmail) {
                    if (
                        hash_equals(
                            hash_hmac('sha512', $userLoginEmail . ',' . $cookieValue['exp'] . ',' . $secretKey, $secretKey), 
                            $cookieValue['jti']
                        )
                    ) {
                        unset($cookieValue, $secretKey);
                        return true;
                    }
                }
            }

            unset($cookieValue, $secretKey);
        }

        return false;
    }// validateDeviceCookie


}
