<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Middleware;


/**
 * Check for valid domain or not, if not then redirect to the domain entered in config DB.
 * 
 * This is depend on settings on config DB.
 * 
 * @since 1.2.9
 */
class ValidDomain
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * The class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Initialize to detect if domain.
     * 
     * @param string|null $response
     * @return string|null
     */
    public function init($response = '')
    {
        if (strtolower(PHP_SAPI) === 'cli') {
            // if running from CLI.
            // don't run this middleware here.
            return $response;
        }

        // check settings. -------------------------
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $configVals = $ConfigDb->get(['rdbadmin_SiteDomain', 'rdbadmin_SiteDomainCheckUsage'], ['', '0']);
        if (!isset($configVals['rdbadmin_SiteDomainCheckUsage']) || $configVals['rdbadmin_SiteDomainCheckUsage'] !== '1') {
            // if there is no config to check domain usage.
            unset($configVals);
            return $response;
        }
        // end check settings. -------------------------

        if (!isset($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] !== $configVals['rdbadmin_SiteDomain']) {
            // if domain requested from client doesn't matched.
            // response headers no cache.
            http_response_code(301);
            header('Expires: Fri, 01 Jan 1971 00:00:00 GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            // build new URL.
            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://';
            $url .= $configVals['rdbadmin_SiteDomain'];
            if (isset($_SERVER['REQUEST_URI'])) {
                $url .= $_SERVER['REQUEST_URI'];
            }
            // send redirect location and stop process.
            header('Location: ' . $url);
            unset($configVals, $url);

            if ($this->Container->has('Db')) {
                /* @var $Db \Rdb\System\Libraries\Db */
                $Db = $this->Container->get('Db');
                $Db->disconnectAll();
                unset($Db);
            }
            exit();
        }// endif;
        unset($configVals);

        return $response;
    }// init


}
