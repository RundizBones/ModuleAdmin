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

        // maybe run cron job.
        $this->maybeRunCron();
    }// __construct


    /**
     * Get page HTML classes.
     * 
     * @param array $classes The classes to set for this html page.
     * @return string Return generated html classes names.
     */
    protected function getPageHtmlClasses(array $classes = []): string
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $default = [];
        $default[] = 'rdba-page-' . str_replace('/', '_', trim(ltrim($Url->getPath(), '/')));
        $default[] = 'rdba-class-' . str_replace(['\\', '/'], '_', trim(get_called_class()));

        $classes = array_merge($default, $classes);

        unset($default, $Url);
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
                $Cron->runJobsOnAllModules();
                unset($Cron);
            }
            unset($Config);
        }
    }// maybeRunCron


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
    }// setBasicConfig


}
