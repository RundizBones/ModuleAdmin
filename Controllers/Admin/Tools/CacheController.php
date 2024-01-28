<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Tools;


/**
 * Tools page controller.
 * 
 * @since 0.1
 */
class CacheController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    /**
     * Clear cache via REST API.
     * 
     * @return string
     */
    public function clearAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminTools', ['manageCache']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make delete data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            $output['cache'] = [];
            $output['cache']['basePath'] = realpath(STORAGE_PATH . '/cache');
            $RdbaCache = new \Rdb\Modules\RdbAdmin\Libraries\Cache(
                $this->Container, 
                [
                    'cachePath' => $output['cache']['basePath'],
                ]
            );
            $Cache = $RdbaCache->getCacheObject();
            $output['cache']['driver'] = $RdbaCache->driver;
            unset($RdbaCache);
            $output['cleared'] = $Cache->clear();

            if ($output['cleared'] === true) {
                $output['formResultStatus'] = 'success';
                $output['formResultMessage'] = __('All cache was cleared.');
                http_response_code(200);
            } else {
                $output['formResultStatus'] = 'warning';
                $output['formResultMessage'] = __('Unable to clear cache.');
                http_response_code(500);
            }
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
    }// clearAction


    /**
     * Get APCu stats.
     * 
     * This method was called from `indexAction()`.
     * 
     * @since 1.2.9
     * @param \Psr\SimpleCache\CacheInterface $Cache
     * @return array
     */
    private function getApcuStats(\Psr\SimpleCache\CacheInterface $Cache): array
    {
        $apcuStats = apcu_cache_info();
        $output = [];
        $totalBytes = 0;
        $totalItems = 0;

        if (is_array($apcuStats)) {
            $totalBytes = ($apcuStats['mem_size'] ?? 0);
            if (isset($apcuStats['cache_list']) && is_countable($apcuStats['cache_list'])) {
                $totalItems = count($apcuStats['cache_list']);
            }
        }

        $output['totalSize'] = $totalBytes;
        $output['totalItems'] = $totalItems;
        unset($apcuStats, $totalBytes, $totalItems);
        return $output;
    }// getApcuStats


    /**
     * Get Memcached stats.
     * 
     * This method was called from `indexAction()`.
     * 
     * @since 1.2.9
     * @param \Psr\SimpleCache\CacheInterface $Cache
     * @return array
     */
    private function getMemcachedStats(\Psr\SimpleCache\CacheInterface $Cache): array
    {
        $memcacheStats = $Cache->getMemcached()->getStats();
        $output = [];
        $totalBytes = 0;
        $totalItems = 0;

        if (is_array($memcacheStats)) {
            foreach ($memcacheStats as $server => $items) {
                if (array_key_exists('bytes', $items) && is_numeric($items['bytes'])) {
                    $totalBytes = ($totalBytes + $items['bytes']);
                }
                if (array_key_exists('curr_items', $items) && is_numeric($items['curr_items'])) {
                    $totalItems = ($totalItems + $items['curr_items']);
                }
            }// endforeach;
            unset($items, $server);
        }

        $output['totalSize'] = $totalBytes;
        $output['totalItems'] = $totalItems;
        unset($memcacheStats, $totalBytes, $totalItems);
        return $output;
    }// getMemcachedStats


    /**
     * Get Memcache stats.
     * 
     * This method was called from `indexAction()`.
     * 
     * @since 1.2.9
     * @param \Psr\SimpleCache\CacheInterface $Cache
     * @return array
     */
    private function getMemcacheStats(\Psr\SimpleCache\CacheInterface $Cache): array
    {
        $memcacheStats = $Cache->getMemcache()->getExtendedStats();
        $output = [];
        $totalBytes = 0;
        $totalItems = 0;

        if (is_array($memcacheStats)) {
            foreach ($memcacheStats as $server => $items) {
                if (array_key_exists('bytes', $items) && is_numeric($items['bytes'])) {
                    $totalBytes = ($totalBytes + $items['bytes']);
                }
                if (array_key_exists('curr_items', $items) && is_numeric($items['curr_items'])) {
                    $totalItems = ($totalItems + $items['curr_items']);
                }
            }// endforeach;
            unset($items, $server);
        }

        $output['totalSize'] = $totalBytes;
        $output['totalItems'] = $totalItems;
        unset($memcacheStats, $totalBytes);
        return $output;
    }// getMemcacheStats


    /**
     * Cache tool page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminTools', ['manageCache']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if non html accept or ajax request.
            $output['cache'] = [];
            $output['cache']['basePath'] = realpath(STORAGE_PATH . '/cache');
            $RdbaCache = new \Rdb\Modules\RdbAdmin\Libraries\Cache(
                $this->Container, 
                [
                    'cachePath' => $output['cache']['basePath'],
                ]
            );
            $Cache = $RdbaCache->getCacheObject();
            $output['cache']['driver'] = $RdbaCache->driver;
            if ($RdbaCache->driver === 'apcu') {
                $output['cache'] = array_merge($output['cache'], $this->getApcuStats($Cache));
            } elseif ($RdbaCache->driver === 'memcache') {
                $output['cache'] = array_merge($output['cache'], $this->getMemcacheStats($Cache));
            } elseif ($RdbaCache->driver === 'memcached') {
                $output['cache'] = array_merge($output['cache'], $this->getMemcachedStats($Cache));
            } elseif ($RdbaCache->driver === 'filesystem') {
                $Fs = new \Rdb\System\Libraries\FileSystem($output['cache']['basePath']);
                $output['cache']['totalSize'] = $Fs->getFolderSize('');
                $output['cache']['totalFilesFolders'] = count($Fs->listFilesSubFolders(''));
            }
            unset($Cache, $RdbaCache);
        }

        // set urls and methods.
        $urlAppBased = $Url->getAppBasedPath(true);
        $output['urls'] = [];
        $output['urls']['getCacheUrl'] = $urlAppBased . '/admin/tools/cache';// display cache tool page, get data via rest api.
        $output['urls']['getCacheMethod'] = 'GET';
        $output['urls']['clearCacheUrl'] = $urlAppBased . '/admin/tools/cache';// clear all cache via rest api.
        $output['urls']['clearCacheMethod'] = 'DELETE';
        unset($urlAppBased);

        $output['pageTitle'] = __('Manage cache');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if custom HTTP accept, response content or ajax request.
            // response the data.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            //$Assets->addMultipleAssets('css', [], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaToolsCache'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaToolsCache',
                'RdbaToolsCache',
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'getCacheUrl' => $output['urls']['getCacheUrl'],
                    'getCacheMethod' => $output['urls']['getCacheMethod'],
                    'clearCacheUrl' => $output['urls']['clearCacheUrl'],
                    'clearCacheMethod' => $output['urls']['clearCacheMethod'],
                    'txtPleaseSelectCommand' => __('Please choose a command'),
                ]
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Tools/cache_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
