<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * CSRF protection class.
 * 
 * @since 0.1
 */
class Csrf
{


    /**
     * @var \Rdb\Modules\RdbAdmin\Libraries\Extended\SlimGuard
     */
    protected $CsrfClass;


    /**
     * @var array The associative array options. See `options` parameter in class constructor.
     */
    protected $options = [];


    /**
     * CSRF protection.
     * 
     * @param array $options The associative array with keys:<br>
     *                                      'prefix' (string) For allow create and validate token with difference form actions.<br>
     *                                      'failureCallable' (callable) allable to be executed if the CSRF validation fails.<br>
     *                                      'storageLimit' (int) For limit number of token (re)generate in each request.<br>
     *                                      'strength' (int) Length of token that will be generate.<br>
     *                                      'persistentTokenMode' (bool) Persistent token mode. Set to `true` to not re-generate token every request which is good for ajax, set to `false` (default) for re-generate every request.<br>
     */
    public function __construct(array $options = [])
    {
        $default = [
            'prefix' => 'csrf',
            'storage' => null,
            'failureCallable' => null,
            'storageLimit' => 1,
            'strength' => 16,
            'persistentTokenMode' => false,
        ];

        $options = array_merge($default, $options);

        if (!is_string($options['prefix'])) {
            $options['prefix'] = 'csrf';
        }
        if (isset($options['failureCallable']) && !is_callable($options['failureCallable'])) {
            $options['failureCallable'] = null;
        }
        if (!is_int($options['storageLimit'])) {
            $options['storageLimit'] = 1;
        }
        if (!is_int($options['strength'])) {
            $options['strength'] = 16;
        }
        if (!is_bool($options['persistentTokenMode'])) {
            $options['persistentTokenMode'] = false;
        }

        $this->options = $options;

        extract($options);

        if (session_id() === '') {
            session_start();
        }

        $this->CsrfClass = new Extended\SlimGuard($prefix, $storage, $failureCallable, $storageLimit, $strength, $persistentTokenMode);
        $this->CsrfClass->validateStorage();
        $this->CsrfClass->enforceStorageLimit();
    }// __construct


    /**
     * Magic get.
     * 
     * @param string $name Property name.
     * @return mixed Return its value depend on property.
     */
    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
    }// __get


    /**
     * Create token.
     * 
     * Usage:
     * <pre>
     * &lt;?php
     * $generateToken = $Csrf-&gt;createToken();
     * extract($generateToken);
     * ?&gt;
     * 
     * &lt;input type=&quot;hidden&quot; name=&quot;&lt;?php echo $csrfName; ?&gt;&quot; value=&quot;&lt;?php echo $csrfKeyPair[$csrfName]; ?&gt;&quot;&gt;
     * &lt;input type=&quot;hidden&quot; name=&quot;&lt;?php echo $csrfValue; ?&gt;&quot; value=&quot;&lt;?php echo $csrfKeyPair[$csrfValue]; ?&gt;&quot;&gt;
     * </pre>
     * 
     * @return array Return associative array with keys: 'csrfName', 'csrfValue', 'csrfKeyPair'.
     */
    public function createToken(): array
    {
        $output = [];
        $output = array_merge($output, $this->getTokenNameValueKey());
        $output['csrfKeyPair'] = $this->CsrfClass->generateToken();

        return $output;
    }// createToken


    /**
     * Get CSRF class instance.
     * 
     * @return \Rdb\Modules\RdbAdmin\Libraries\Extended\SlimGuard
     */
    public function getInstance(): Extended\SlimGuard
    {
        return $this->CsrfClass;
    }// getInstance


    /**
     * Get token name key and value key.
     * 
     * @param bool $sequential Set to `true` to return sequential array (indexed array), `false` (default) to return associative array.
     * @return array Return sequential or associative array.<br>
     *                          For sequential array (indexed array) first array is name key, second is value key.<br>
     *                          For associative array it will return with keys 'csrfName', 'csrfValue'.
     */
    public function getTokenNameValueKey(bool $sequential = false): array
    {
        $output = [];

        if ($sequential === true) {
            $output[] = $this->CsrfClass->getTokenNameKey();
            $output[] = $this->CsrfClass->getTokenValueKey();
        } else {
            $output['csrfName'] = $this->CsrfClass->getTokenNameKey();
            $output['csrfValue'] = $this->CsrfClass->getTokenValueKey();
        }

        return $output;
    }// getTokenNameValueKey


    /**
     * Validate CSRF token.
     * 
     * @see \Slim\Csrf\Guard::validateToken()
     * @param string $name CSRF name.
     * @param string $value CSRF token value.
     * @return bool Return `true` on success, `false` on failure.
     */
    public function validateToken(string $name, string $value): bool
    {
        return $this->CsrfClass->validateToken($name, $value);
    }// validateToken


}
