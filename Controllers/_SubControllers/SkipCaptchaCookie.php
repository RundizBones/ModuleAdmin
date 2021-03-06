<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\_SubControllers;


/**
 * Skip "require captcha" cookie sub controller.
 * 
 * This sub controller was called from `LoginController`.
 * 
 * @since 0.1
 */
class SkipCaptchaCookie extends \Rdb\Modules\RdbAdmin\Controllers\BaseController
{


    /**
     * @var string The skip captcha cookie name.
     */
    protected $cookieName = 'rdbadmin_cookie_skipcaptcha';


    /**
     * @var string The configuration name of hash key that will be use in encryption cookie.
     */
    protected $encryptionConfigName = 'rdbaGenericKey';


    /**
     * Check if there is skip "require captcha" cookie and it is valid.
     * 
     * @return bool Return `true` if there is an cookie and is valid, `false` for otherwise.
     */
    public function isSkipCaptcha(): bool
    {
        // get skip "require captcha" cookie.
        $Cookie = new \Rdb\Modules\RdbAdmin\Libraries\Cookie($this->Container);
        $Cookie->setEncryption($this->encryptionConfigName);

        $requireCaptchaCookie = $Cookie->get($this->cookieName);

        if (empty($requireCaptchaCookie)) {
            // if there is no cookie.
            $output = false;
        } else {
            // if there is cookie
            if (
                isset($requireCaptchaCookie['user_id']) &&
                isset($requireCaptchaCookie['noNeedCaptcha']) &&
                isset($requireCaptchaCookie['loginDate']) &&
                isset($requireCaptchaCookie['secretKey']) &&
                isset($requireCaptchaCookie['signature']) &&
                $requireCaptchaCookie['noNeedCaptcha'] === true
            ) {
                $dataForHmac = [
                    'user_id' => $requireCaptchaCookie['user_id'],
                    'loginDate' => $requireCaptchaCookie['loginDate'],
                ];

                if (
                    hash_equals(
                        hash_hmac('sha512', serialize($dataForHmac), $requireCaptchaCookie['secretKey']), 
                        $requireCaptchaCookie['signature']
                    )
                ) {
                    $output = true;
                } else {
                    $output = false;
                }

                unset($dataForHmac);
            } else {
                $output = false;
            }
        }

        unset($Cookie, $requireCaptchaCookie);
        return $output;
    }// isSkipCaptcha


    /**
     * Issue new skip "require captcha" cookie.
     * 
     * This method will send the set cookie header to client.
     * 
     * @param int $user_id The user ID.
     * @param int $cookieExpiresConfig The login cookie expiration in configuration DB (unit in days).
     */
    public function issueSkipCaptchaCookie(int $user_id, int $cookieExpiresConfig)
    {
        $Cookie = new \Rdb\Modules\RdbAdmin\Libraries\Cookie($this->Container);
        $String = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();
        $secretKey = $String->random(30);
        $loginDate = date('Y-m-d H:i:s');
        $dataForHmac = [
            'user_id' => $user_id,
            'loginDate' => $loginDate,
        ];
        $signature = hash_hmac('sha512', serialize($dataForHmac), $secretKey);
        unset($dataForHmac, $String);

        $requireCaptchaCookie = [
            'user_id' => $user_id,
            'noNeedCaptcha' => true,
            'loginDate' => $loginDate,
            'secretKey' => $secretKey,
            'signature' => $signature,
        ];
        unset($loginDate, $secretKey, $signature);

        if ($cookieExpiresConfig == 0) {
            $cookieExpires = intval(time() + (1 * 24 * 60 * 60));
        } else {
            $cookieExpires = intval(abs(time() + (($cookieExpiresConfig * 6) * 24 * 60 * 60)));
        }

        if ($this->Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $this->Container->get('Logger');
            $Logger->write(
                'modules/rdbadmin/controllers/_subcontrollers/skipcaptchacookie', 
                0, 
                'cookie expires config is {cookieExpiresConfig}, cookie expires is {cookieExpires}', 
                ['cookieExpiresConfig' => $cookieExpiresConfig, 'cookieExpires' => $cookieExpires]
            );
        }

        $Cookie->setEncryption($this->encryptionConfigName);
        $Cookie->set($this->cookieName, $requireCaptchaCookie, $cookieExpires, '/');
        unset($Cookie, $cookieExpires, $requireCaptchaCookie);
    }// issueSkipCaptchaCookie


}
