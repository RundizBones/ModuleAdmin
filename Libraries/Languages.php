<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Languages class that work as connector to Packagist vendor's class.
 * 
 * @since 0.1
 * @link https://github.com/phpmyadmin/motranslator Packagist vendor's class document.
 * @property-read string $currentTextDomain Currently loaded text domain.
 * @property-read array $domains Loaded domains
 * @property-read array $registeredTextDomains  The text domains that was registered via `registerTextDomain()` method.
 */
class Languages
{


    /**
     * Default domain is set to 'rdbadmin' and will be use when none specify.
     */
    const DEFAULT_DOMAIN = 'rdbadmin';


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var string Currently loaded text domain.
     */
    protected $currentTextDomain;


    /**
     * @var array Loaded domains
     */
    protected $domains = [];


    /**
     * @var array The text domains that was registered via `registerTextDomain()` method.
     */
    protected $registeredTextDomains = [];


    /**
     * @var \Rdb\Modules\RdbAdmin\Libraries\Languages
     */
    public static $staticThis;


    /**
     * Class constructor.
     * 
     * You maybe load this class via framework's `Container` object named `Languages`. Example: `$Languages = $Container->get('Languages');`.<br>
     * This is depended on if you extended your controller from `Rdb\Modules\RdbAdmin\Controllers\BaseController`.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        if ($Container instanceof \Rdb\System\Container) {
            $this->Container = $Container;
        } else {
            $this->Container = new \Rdb\System\Container();
        }
    }// __construct


    /**
     * Magic get.
     * 
     * @since 1.2.9
     * @param string $name
     */
    public function __get(string $name)
    {
        $allowedProps = [
            'currentTextDomain',
            'domains',
            'registeredTextDomains',
        ];

        if (in_array($name, $allowedProps) && property_exists($this, $name)) {
            return $this->{$name};
        }
    }// __get


    /**
     * Bind text domain file.
     * 
     * Load the translation file to get ready to use.
     * 
     * @param string $domain The text domain. Example: 'mymdoule'.
     * @param string $directory The full path to folder that contain translations (mo file). No trailing slash. Example: '/var/www/Modules/MyModule/languages/translations'.
     * @return bool Return `true` on success, `false` on failure.
     */
    public function bindTextDomain(string $domain, string $directory = ''): bool
    {
        if ($this->Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $this->Container->get('Logger');
        }

        $directory = rtrim(rtrim($directory, '/'), '\\');// remove trailing slash (+back slash).

        if (empty($directory)) {
            if (is_array($this->registeredTextDomains) && array_key_exists($domain, $this->registeredTextDomains)) {
                $directory = $this->registeredTextDomains[$domain];
            } else {
                if (isset($Logger)) {
                    $Logger->write('modules/rdbadmin/libraries/languages', 2, 'The selected domain ({domain}) did not register with any directory.', ['domain' => $domain]);
                }
            }
        } else {
            $this->registerTextDomain($domain, $directory);
        }

        if ($this->currentTextDomain === $domain) {
            // if already loaded translation.
            $this->getHelpers();
            return true;
        }

        $moFile = $directory . DIRECTORY_SEPARATOR . $domain . '-' . $_SERVER['RUNDIZBONES_LANGUAGE'] . '.mo';

        if (!isset($this->domains[$_SERVER['RUNDIZBONES_LANGUAGE']])) {
            $this->domains[$_SERVER['RUNDIZBONES_LANGUAGE']] = [];
        }

        if (!isset($this->domains[$_SERVER['RUNDIZBONES_LANGUAGE']][$domain])) {
            $Translator = new \PhpMyAdmin\MoTranslator\Translator($moFile);
            $this->domains[$_SERVER['RUNDIZBONES_LANGUAGE']][$domain] = $Translator;
            unset($Translator);
        }

        if (is_file($moFile)) {
            $this->currentTextDomain = $domain;
            $this->getHelpers();
            return true;
        } else {
            if (isset($Logger)) {
                $Logger->write('modules/rdbadmin/libraries/languages', 2, 'The translation file could not be found ({mofile}).', ['mofile' => $moFile]);
            }
        }

        // if translation file was not found, return `false` and not throw any error at all.
        // this is for devs can be use the message from source code directly without translation.
        unset($Logger, $moFile);
        $this->getHelpers();
        return false;
    }// bindTextDomain


    /**
     * Get helpers functions.
     * 
     * This method will be called automatically once call to `bindTextDomain()`.
     * 
     * @return $this
     */
    public function getHelpers()
    {
        static::$staticThis = $this;
        include_once dirname(__DIR__) . '/Helpers/LanguagesFunctions.php';
        return $this;
    }// getHelpers


    /**
     * Get `Translator` object.
     * 
     * @return \PhpMyAdmin\MoTranslator\Translator
     */
    public function getTranslator(string $domain = '') : \PhpMyAdmin\MoTranslator\Translator
    {
        if ('' === $domain) {
            if (isset($this->domains[$_SERVER['RUNDIZBONES_LANGUAGE']][static::DEFAULT_DOMAIN])) {
                $domain = static::DEFAULT_DOMAIN;
            } else {
                $domain = $this->currentTextDomain;
            }
        }

        if (!isset($this->domains[$_SERVER['RUNDIZBONES_LANGUAGE']][$domain])) {
            // if never bind text domain before.
            // just use new translator with file that is not found.
            return new \PhpMyAdmin\MoTranslator\Translator('');
        }

        return $this->domains[$_SERVER['RUNDIZBONES_LANGUAGE']][$domain];
    }// getTranslator


    /**
     * Register text domain.
     * 
     * @param string $domain The text domain. Example: 'mymodule'.
     * @param string $directory The full path to folder that contain translations (mo file). No trailing slash or back slash.
     *      Example: '/var/www/Modules/MyModule/languages/translations'.
     */
    protected function registerTextDomain(string $domain, string $directory)
    {
        if (!is_array($this->registeredTextDomains)) {
            $this->registeredTextDomains = [];
        }

        if (!array_key_exists($domain, $this->registeredTextDomains)) {
            $this->registeredTextDomains[$domain] = $directory;
        }
    }// registerTextDomain


}
