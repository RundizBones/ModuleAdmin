<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers;


/**
 * Extended base controller from the framework.
 * 
 * @since 0.1
 */
abstract class BaseController extends \Rdb\System\Core\Controllers\BaseController
{


    /**
     * @var \Rdb\Modules\RdbAdmin\Libraries\Input
     */
    protected $Input;


    /**
     * @var \Rdb\Modules\RdbAdmin\Libraries\Languages
     */
    protected $Languages;


    /**
     * @var array Runned cron jobs result. 
     * This is for use in case that set cron job, cron tab to run by URL.
     * The `CronController` will be call to this `BaseController`.
     * So, it is no need to using `Libraries\Cron` class to run jobs again.
     * Just get the run result from this property.
     * This property will be set by `maybeRunCron()` method.
     */
    protected $runnedCronResult = [];


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        // set default timezone, etc.
        $this->setBasicConfig();

        $this->Input = new \Rdb\Modules\RdbAdmin\Libraries\Input($Container);

        $Languages = new \Rdb\Modules\RdbAdmin\Libraries\Languages($Container);
        if (!$this->Container->has('Languages')) {
            $this->Container['Languages'] = function ($c) use ($Languages) {
                return $Languages;
            };
        }
        unset($Languages);

        $this->Languages = $this->Container['Languages'];
        $this->Languages->bindTextDomain(
            'rdbadmin', 
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );

        // initialize plugin class.
        // @since 0.2.4
        $Plugins = new \Rdb\Modules\RdbAdmin\Libraries\Plugins($Container);
        if (!$this->Container->has('Plugins')) {
            $this->Container['Plugins'] = function ($c) use ($Plugins) {
                return $Plugins;
            };
        }
        $Plugins->registerAllPluginsHooks();
        unset($Plugins);

        // maybe run cron job.
        $this->maybeRunCron();
    }// __construct


    /**
     * Get page HTML classes.
     * 
     * @todo [rdb] Remove auto generate class name `rdba-page-`, use new one `rdba-pagehtml-` to prevent duplicate use in many cases. Remove this in v2.0
     * @todo [rdb] Remove auto generate class name `rdba-class-`, use new one `rdba-calledclass-` to prevent duplicate use in many cases. Remove this in v2.0
     * @param array $classes The classes to set for this html page.
     * @return string Return generated html classes names.
     */
    protected function getPageHtmlClasses(array $classes = []): string
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $default = [];
        $currentPage = trim(trim($Url->getCurrentUrl(true), '/'));
        $currentPage = preg_replace('/[^\w\- \/]+/iu', '', $currentPage);
        $currentPage = (empty($currentPage) ? '/' : $currentPage);
        $default[] = 'rdba-page-' . str_replace('/', '_', $currentPage);// deprecated. remove this html class in v2.0
        $default[] = 'rdba-pagehtml-' . str_replace('/', '_', $currentPage);
        $default[] = 'rdba-class-' . str_replace(['\\', '/'], '_', trim(get_called_class()));// deprecated. remove this html class in v2.0
        $default[] = 'rdba-calledclass-' . str_replace(['\\', '/'], '_', trim(get_called_class()));

        $classes = array_merge($default, $classes);

        unset($currentPage, $default, $Url);
        return implode(' ', $classes);
    }// getPageHtmlClasses


    /**
     * Get page HTML title including site name if it was set.
     * 
     * @param string $title The site title.
     * @param string|null|false $siteName Site name should be string.<br>
     *                                                          Set to empty string or `null` will not include the site name.<br>
     *                                                          Set to `false` to automatic get the site name from config DB.
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getPageHtmlTitle(string $title, $siteName = false): string
    {
        if (!is_string($siteName) && !is_null($siteName) && $siteName !== false) {
            throw new \InvalidArgumentException('The $siteName argument type must be string, null, false.');
        }

        if ($siteName === false) {
            // if `$siteName` is `false` means get site name from config db automatically.
            $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
            $siteName = $ConfigDb->get('rdbadmin_SiteName');
            unset($ConfigDb);
        }

        if (!empty($siteName)) {
            // if not empty.
            $siteName = ' | ' . $siteName;
        } else {
            // if empty (including `null`).
            $siteName = '';
        }

        return $title . $siteName;
    }// getPageHtmlTitle


    /**
     * Maybe run cron job if config is set to not use server cron.
     */
    protected function maybeRunCron()
    {
        if (
            (
                !isset($_SERVER['RUNDIZBONES_MODULEEXECUTE']) || 
                (
                    isset($_SERVER['RUNDIZBONES_MODULEEXECUTE']) && 
                    $_SERVER['RUNDIZBONES_MODULEEXECUTE'] !== 'true'
                )
            ) &&
            $this->Container->has('Config')
        ) {
            /* @var $Config \Rdb\System\Config */
            $Config = $this->Container->get('Config');
            $Config->setModule('RdbAdmin');

            if (
                $Config->get('useServerCron', 'cron', false) === false && 
                $Config->get('enableCron', 'cron', true) === true
            ) {
                /* @var $Modules \Rdb\System\Modules */
                $Cron = new \Rdb\Modules\RdbAdmin\Libraries\Cron($this->Container);
                $this->runnedCronResult = $Cron->runJobsOnAllModules();
                unset($Cron);
            }

            $Config->setModule('');// restore to default.

            unset($Config);
        }
    }// maybeRunCron


    /**
     * {@inheritDoc}
     */
    protected function responseJson($output): string
    {
        $this->setHeaderAllowOrigin();

        return parent::responseJson($output);
    }// responseJson


    /**
     * {@inheritDoc}
     */
    protected function responseXml($output): string
    {
        $this->setHeaderAllowOrigin();

        return parent::responseXml($output);
    }// responseXml


    /**
     * Setup basic PHP configurations such as default timezone.
     */
    protected function setBasicConfig()
    {
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);

        $configNames = [
            'rdbadmin_SiteTimezone',
        ];
        $configDefaults = [
            'Asia/Bangkok',
        ];

        $configValues = $ConfigDb->get($configNames, $configDefaults);
        unset($ConfigDb, $configDefaults, $configNames);

        date_default_timezone_set($configValues['rdbadmin_SiteTimezone']);

        unset($configValues);
    }// setBasicConfig'


    /**
     * Set header allow origin for CORS.
     */
    protected function setHeaderAllowOrigin()
    {
        if (!headers_sent()) {
            $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
            $allowOrigins = $ConfigDb->get('rdbadmin_SiteAllowOrigins');
            unset($ConfigDb);

            if (!empty($allowOrigins)) {
                $allowOriginsArray = array_map('trim', explode(',', $allowOrigins));
                $headers = array_change_key_case(apache_request_headers());
                if (isset($headers['origin']) && is_array($allowOriginsArray)) {
                    foreach ($allowOriginsArray as $allowOrigin) {
                        $allowOrigin = strtolower(trim($allowOrigin));
                        if (strtolower(trim($headers['origin'])) === $allowOrigin) {
                            // if found allowed origin matched the request origin.
                            break;
                        }
                    }// endforeach;
                } elseif (is_array($allowOriginsArray)) {
                    $allowOriginsArray = array_values($allowOriginsArray);
                    $allowOrigin = array_shift($allowOriginsArray);
                    $allowOrigin = strtolower(trim($allowOrigin));
                }
                unset($allowOriginsArray, $headers);
            }
            unset($allowOrigins);

            if (isset($allowOrigin)) {
                header('Access-Control-Allow-Origin: ' . $allowOrigin);
                unset($allowOrigin);
            }
        }
    }// setHeaderAllowOrigin


}
