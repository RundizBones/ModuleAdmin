<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Languages class that work as connector to oscarotero/gettext class.
 * 
 * @since 0.1
 * @link https://github.com/oscarotero/Gettext Gettext class document.
 */
class Languages
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var string Currently loaded text domain.
     */
    protected $currentTextDomain;


    /**
     * @var array The text domains that was registered via registerTextDomain() method.
     */
    protected $registeredTextDomains = [];


    /**
     * @var \Gettext\Translator
     */
    protected $Translator;


    /**
     * Class constructor.
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

        $this->Translator = new \Gettext\Translator();
    }// __construct


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
            $this->Translator->register();
            return true;
        }

        $directory = rtrim(rtrim($directory, '/'), '\\');
        $moFile = $directory . DIRECTORY_SEPARATOR . $domain . '-' . $_SERVER['RUNDIZBONES_LANGUAGE'] . '.mo';

        if (is_file($moFile)) {
            $Translations = new \Gettext\Translations();
            $Translations->addFromMoFile($moFile);
            $Translations->setDomain($domain);
            $this->Translator->loadTranslations($Translations);
            unset($Translations);

            $this->currentTextDomain = $domain;
            unset($moFile);

            $this->Translator->register();
            return true;
        } else {
            if (isset($Logger)) {
                $Logger->write('modules/rdbadmin/libraries/languages', 2, 'The translation file could not be found ({mofile}).', ['mofile' => $moFile]);
            }
        }

        // if translation file was not found, return `false` and not throw any error at all.
        // this is for devs can be use the message from source code directly without translation.
        unset($Logger, $moFile);
        $this->Translator->register();
        return false;
    }// bindTextDomain


    /**
     * Get helpers functions.
     * 
     * @return $this
     */
    public function getHelpers()
    {
        include_once dirname(__DIR__) . '/Helpers/LanguagesFunctions.php';
        return $this;
    }// getHelpers


    /**
     * Get `Translator` object.
     * 
     * @return \Gettext\Translator
     */
    public function getTranslator() : \Gettext\Translator
    {
        return $this->Translator;
    }// getTranslator


    /**
     * Register text domain.
     * 
     * @param string $domain The text domain. Example: 'mymodule'.
     * @param string $directory The full path to folder that contain translations (mo file). Example: '/var/www/Modules/MyModule/languages/translations'.
     */
    protected function registerTextDomain(string $domain, string $directory)
    {
        if (!is_array($this->registeredTextDomains)) {
            $this->registeredTextDomains = [];
        }

        if (!array_key_exists($domain, $this->registeredTextDomains)) {
            $this->registeredTextDomains = array_merge($this->registeredTextDomains, [$domain => rtrim(rtrim($directory, '/'), '\\') . DIRECTORY_SEPARATOR]);
        }
    }// registerTextDomain


}
