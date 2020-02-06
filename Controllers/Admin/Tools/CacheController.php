<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Controllers\Admin\Tools;


/**
 * Tools page controller.
 * 
 * @since 0.1
 */
class CacheController extends \Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    /**
     * Clear cache via REST API.
     * 
     * @return string
     */
    public function clearAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminTools', ['manageCache']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \System\Libraries\Url($this->Container);

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

            $Fs = new \System\Libraries\FileSystem(STORAGE_PATH . DIRECTORY_SEPARATOR . 'cache');
            $output['cleared'] = $Fs->deleteFolder('');
            unset($Fs);
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
     * Cache tool page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminTools', ['manageCache']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if non html accept or ajax request.
            $output['cache'] = [];
            $output['cache']['basePath'] = realpath(STORAGE_PATH . '/cache');
            $RdbaCache = new \Modules\RdbAdmin\Libraries\Cache(
                $this->Container, 
                [
                    'cachePath' => $output['cache']['basePath'],
                ]
            );
            $Cache = $RdbaCache->getCacheObject();
            $output['cache']['driver'] = $RdbaCache->driver;
            if ($RdbaCache->driver === 'filesystem') {
                $Fs = new \System\Libraries\FileSystem($output['cache']['basePath']);
                $output['cache']['totalSize'] = $Fs->getFolderSize('');
                $output['cache']['totalFilesFolders'] = count($Fs->listFilesSubFolders(''));
            }
            unset($Cache, $RdbaCache);
        }

        // set urls and methods.
        $urlAppBased = $Url->getAppBasedPath(true);
        $output['getCacheUrl'] = $urlAppBased . '/admin/tools/cache';// display cache tool page, get data via rest api.
        $output['getCacheMethod'] = 'GET';
        $output['clearCacheUrl'] = $urlAppBased . '/admin/tools/cache';// clear all cache via rest api.
        $output['clearCacheMethod'] = 'DELETE';
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
            $Assets = new \Modules\RdbAdmin\Libraries\Assets($this->Container);

            //$Assets->addMultipleAssets('css', [], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaToolsCache'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaToolsCache',
                'RdbaToolsCache',
                [
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'getCacheUrl' => $output['getCacheUrl'],
                    'getCacheMethod' => $output['getCacheMethod'],
                    'clearCacheUrl' => $output['clearCacheUrl'],
                    'clearCacheMethod' => $output['clearCacheMethod'],
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
