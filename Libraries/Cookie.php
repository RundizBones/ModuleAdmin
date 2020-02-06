<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Libraries;


use Modules\RdbAdmin\Libraries\Encryption;


/**
 * Cookie class.
 * 
 * @since 0.1
 */
class Cookie
{


    /**
     * @var \System\Container
     */
    protected $Container;


    /**
     * @var string The module name that this hash configuration file stored in.
     */
    protected $hashConfigModule;


    /**
     * @var string Hash configuration name that will be found in config/hash.php in the module.
     */
    protected $hashConfigName;


    /**
     * Class constructor.
     * 
     * @param \System\Container $Container The DI container.
     */
    public function __construct(\System\Container $Container)
    {
        if ($Container instanceof \System\Container) {
            $this->Container = $Container;
        }
    }// __construct


    /**
     * Get a cookie value.
     * 
     * If you want to use encrypted cookie then call to `setEncryption()` method before.<br>
     * The cookie value can be any format such as string, array, object, etc. depend on how it was set.
     * 
     * @param string $name The name of the cookie.
     * @param mixed $default The value of cookie.
     * @return mixed Return value of cookie. The type is depend on how it was set.
     */
    public function get(string $name, $default = null)
    {
        /* @var $Config \System\Config */
        if ($this->Container->has('Config')) {
            $Config = $this->Container->get('Config');
            $Config->setModule('');
        } else {
            $Config = new \System\Config();
        }
        $name .= $Config->get('suffix', 'cookie');

        $Serializer = new \Rundiz\Serializer\Serializer();

        if (isset($_COOKIE[$name])) {
            if ($this->hashConfigName !== null) {
                // if it was mark to use encryption cookie.
                $Encryption = new Encryption();

                $Config->setModule($this->hashConfigModule);
                $value = $Encryption->decrypt($_COOKIE[$name], $Config->get($this->hashConfigName, 'hash'));
                $value = $Serializer->maybeUnserialize($value);
                $Config->setModule('');

                unset($Encryption);
            } else {
                $value = $Serializer->maybeUnserialize($_COOKIE[$name]);
            }

            unset($Config, $Serializer);
            return $value;
        } else {
            return $default;
        }
    }// get


    /**
     * Set a cookie.
     * 
     * If you want to use encrypted cookie then call to `setEncryption()` method before.<br>
     * The cookie value can be any format such as string, array, object, etc.
     * 
     * @link https://php.net/manual/en/function.setcookie.php `setcookie()` function reference.
     * @see setcookie()
     * @param string $name The name of the cookie.
     * @param mixed $value The value of the cookie. If it is not scalar then it will be serialize before.
     * @param int $expires The time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch. In other words, you'll most likely set this with the time() function plus the number of seconds before you want it to expire.
     * @param string $path The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
     * @param string $domain The (sub)domain that the cookie is available to.
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     * @param bool $httponly When `true` the cookie will be made accessible only through the HTTP protocol.
     * @return bool If output exists prior to calling this function, setcookie() will fail and return `false`. If setcookie() successfully runs, it will return `true`. This does not indicate whether the user accepted the cookie.
     */
    public function set(string $name, $value = '', int $expires = 0, string $path = '', string $domain = '', bool $secure = false, bool $httponly = false): bool
    {
        /* @var $Config \System\Config */
        if ($this->Container->has('Config')) {
            $Config = $this->Container->get('Config');
            $Config->setModule('');
        } else {
            $Config = new \System\Config();
        }
        $name .= $Config->get('suffix', 'cookie');

        $Serializer = new \Rundiz\Serializer\Serializer();

        if ($this->hashConfigName !== null) {
            // if it was mark to use encryption cookie.
            $Encryption = new Encryption();

            $Config->setModule($this->hashConfigModule);
            $value = $Encryption->encrypt($Serializer->maybeSerialize($value), $Config->get($this->hashConfigName, 'hash'));
            $Config->setModule('');

            unset($Encryption);
        } else {
            if (!is_scalar($value)) {
                $value = $Serializer->maybeSerialize($value);
            }
        }

        unset($Config, $Serializer);
        return setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
    }// set


    /**
     * Mark that this cookie must use encryption.
     * 
     * If do not use encryption then set `$hashConfigName` to `null`.
     * 
     * @param string $hashConfigName Hash configuration name that will be found in config/hash.php in the module.
     * @param string $hashConfigModule The module name that this hash configuration file stored in.
     */
    public function setEncryption(string $hashConfigName, string $hashConfigModule = 'RdbAdmin')
    {
        $this->hashConfigName = $hashConfigName;
        $this->hashConfigModule = $hashConfigModule;
    }// setEncryption


}
