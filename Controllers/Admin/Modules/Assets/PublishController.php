<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Modules\Assets;


/**
 * Publish module's assets controller.
 * 
 * @since 1.1.8
 */
class PublishController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\AssetsTrait;


    /**
     * @var string Full path to publish log file.
     */
    protected $publishLog = '';


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->publishLog = STORAGE_PATH . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'publish-assets_' . date('Y-m-d-H-00-00') . '.log';
    }// __construct


    /**
     * Copy module's assets to public folder.
     * 
     * @param array $modules
     */
    protected function copyModuleAssetsToPublic(array $modules)
    {
        foreach ($modules as $moduleSystemName) {
            $moduleAssetsPath = MODULE_PATH . DIRECTORY_SEPARATOR . $moduleSystemName . DIRECTORY_SEPARATOR . 'assets';
            if (!is_dir($moduleAssetsPath)) {
                continue;
            }

            file_put_contents($this->publishLog, $moduleSystemName . ' ' . str_repeat('#', 20) . PHP_EOL, FILE_APPEND);

            $MDI = new \RecursiveDirectoryIterator(
                $moduleAssetsPath, 
                \FilesystemIterator::SKIP_DOTS
            );
            $MDI = new \RecursiveIteratorIterator(
                $MDI,
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            // create <module name>/assets folder if not exists.
            $FileSystem = new \Rdb\System\Libraries\FileSystem(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'Modules');
            $FileSystem->createFolder($moduleSystemName . DIRECTORY_SEPARATOR . 'assets');
            unset($FileSystem);

            $publicModuleAssetsPath = PUBLIC_PATH . DIRECTORY_SEPARATOR . 
                'Modules' . DIRECTORY_SEPARATOR . 
                $moduleSystemName . DIRECTORY_SEPARATOR . 
                'assets';
            $FileSystem = new \Rdb\System\Libraries\FileSystem(
                $publicModuleAssetsPath
            );

            foreach ($MDI as $filename => $File) {
                $relatePath = str_replace($moduleAssetsPath, '', $File->getPathname());
                $relatePath = ltrim($relatePath, '\\/' . DIRECTORY_SEPARATOR);

                $targetFullPath = $FileSystem->getFullPathWithRoot($relatePath);
                $sourceMTime = $File->getMTime();
                $targetMTime = $FileSystem->getTimestamp($relatePath);
                $sourceSize = $File->getSize();
                $targetSize = (file_exists($targetFullPath) ? filesize($targetFullPath) : -1);

                $action = '';
                $actionResult = null;
                $sizeOrMTimeDifferent = false;
                if ($File->isDir() && !file_exists($targetFullPath)) {
                    // if folder is not exists in target.
                    $action = 'create folder';
                    $actionResult = $FileSystem->createFolder($relatePath);
                }// endif folder not exists in target.

                if (($File->isFile() || $File->isLink())) {
                    // if is file.
                    if (!file_exists($targetFullPath)) {
                        // if file is not exists in target.
                        $action = 'new file';
                        $actionResult = copy($File->getPathname(), $targetFullPath);
                    } elseif (
                        $sourceMTime != $targetMTime || 
                        $sourceSize != $targetSize
                    ) {
                        // if file modify time or file size different.
                        $sizeOrMTimeDifferent = true;
                        $action = 'copy file';
                        $actionResult = copy($File->getPathname(), $targetFullPath);
                        if ($actionResult === true) {
                            touch($targetFullPath, $sourceMTime);
                        }
                    }// endif file not exists in target.
                }// endif is file.

                $logContent = "\t" . $File->getPathname() . ' -> ' . $publicModuleAssetsPath . DIRECTORY_SEPARATOR . $relatePath;
                $logContent .= PHP_EOL;
                $logContent .= "\t\t" . 'exists: ' . var_export(file_exists($targetFullPath), true);
                $logContent .= "\t\t" . 'different: ' . var_export($sizeOrMTimeDifferent, true);
                $logContent .= "\t\t" . 'mtime: ' . $sourceMTime . ' :: ' . $targetMTime;
                $logContent .= "\t\t" . 'size: ' . $sourceSize . ' :: ' . $targetSize;
                $logContent .= PHP_EOL;
                $logContent .= "\t\t" . 'action: ' . $action;
                $logContent .= "\t\t" . 'action result: ' . var_export($actionResult, true);
                $logContent .= PHP_EOL;
                file_put_contents($this->publishLog, $logContent, FILE_APPEND);
                unset($logContent);

                unset($action, $actionResult, $sizeOrMTimeDifferent, $relatePath);
                unset($sourceMTime, $sourceSize, $targetFullPath, $targetMTime, $targetSize);
            }// endforeach;
            unset($File, $filename);

            unset($FileSystem, $MDI, $moduleAssetsPath, $publicModuleAssetsPath);

        }// endforeach;
        unset($moduleSystemName);
    }// copyModuleAssetsToPublic


    /**
     * Delete assets in public folder that is no longer exists in module folder.
     * 
     * @param array $modules
     */
    protected function deleteNotExists(array $modules)
    {
        foreach ($modules as $moduleSystemName) {
            $moduleAssetsPath = MODULE_PATH . DIRECTORY_SEPARATOR . $moduleSystemName . DIRECTORY_SEPARATOR . 'assets';
            $publicModuleAssetsPath = PUBLIC_PATH . DIRECTORY_SEPARATOR . 
                'Modules' . DIRECTORY_SEPARATOR . 
                $moduleSystemName . DIRECTORY_SEPARATOR . 
                'assets';
            if (!is_dir($publicModuleAssetsPath)) {
                continue;
            }

            $PDI = new \RecursiveDirectoryIterator($publicModuleAssetsPath, \FilesystemIterator::SKIP_DOTS);
            $PDI = new \RecursiveIteratorIterator(
                $PDI,
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            $FileSystem = new \Rdb\System\Libraries\FileSystem(
                $moduleAssetsPath
            );

            foreach ($PDI as $filename => $File) {
                $relatePath = str_replace($publicModuleAssetsPath, '', $File->getPathname());
                $relatePath = ltrim($relatePath, '\\/' . DIRECTORY_SEPARATOR);

                $sourceFullPath = $FileSystem->getFullPathWithRoot($relatePath);

                if (!file_exists($sourceFullPath)) {
                    // if source file or folder does not exists. this file or folder is not exists in module folder.
                    $targetMTime = $File->getMTime();
                    $sourceMTime = $FileSystem->getTimestamp($relatePath);
                    $targetSize = $File->getSize();
                    $sourceSize = (file_exists($sourceFullPath) ? filesize($sourceFullPath) : -1);

                    if ($File->isDir()) {
                        $action = 'delete folder';
                        $FSPublic = new \Rdb\System\Libraries\FileSystem($publicModuleAssetsPath);
                        $actionResult = $FSPublic->deleteFolder($relatePath, true);
                        unset($FSPublic);
                    } else {
                        $action = 'delete file';
                        $FSPublic = new \Rdb\System\Libraries\FileSystem($publicModuleAssetsPath);
                        $actionResult = $FSPublic->deleteFile($relatePath);
                        unset($FSPublic);
                    }
                } else {
                    // if source file or folder exists.
                    // skip it.
                    continue;
                }

                $logContent = "\t" . $sourceFullPath . ' <-X-> ' . $File->getPathname();
                $logContent .= PHP_EOL;
                $logContent .= "\t\t" . 'exists: ' . var_export(file_exists($sourceFullPath), true);
                $logContent .= "\t\t" . 'mtime: ' . $sourceMTime . ' :: ' . $targetMTime;
                $logContent .= "\t\t" . 'size: ' . $sourceSize . ' :: ' . $targetSize;
                $logContent .= PHP_EOL;
                $logContent .= "\t\t" . 'action: ' . $action;
                $logContent .= "\t\t" . 'action result: ' . var_export($actionResult, true);
                $logContent .= PHP_EOL;
                file_put_contents($this->publishLog, $logContent, FILE_APPEND);
                unset($logContent);

                unset($relatePath);
                unset($sourceFullPath, $sourceMTime, $sourceSize, $targetMTime, $targetSize);
            }// endforeach;
            unset($File, $filename);

            unset($FileSystem, $moduleAssetsPath, $PDI, $publicModuleAssetsPath);
        }// endforeach;
        unset($moduleSystemName);
    }// deleteNotExists


    /**
     * Do publish action. requested via REST API.
     * 
     * @return string
     */
    public function doPublishAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminModulesAssets', ['publishAssets']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated token to prevent CSRF.
            unset($_POST[$csrfName], $_POST[$csrfValue]);

            // form validation. -------------------------------
            $formValidated = true;
            if ($this->Input->post('action') !== 'publish') {
                $formValidated = false;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Invalid form action');
                http_response_code(400);
            }

            if ($formValidated === true) {
                if (empty(trim($this->Input->post('module_system_name')))) {
                    $formValidated = false;
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = __('Please select at least one module.');
                    http_response_code(400);
                }
            }// endif form validated.
            // end form validation. ---------------------------

            if ($formValidated === true) {
                if (strpos(MODULE_PATH, PUBLIC_PATH) === false) {
                    // if module is not in the same or child of public path.
                    set_time_limit(3*60);// increase execution time to x seconds.

                    $modules = explode(',', $this->Input->post('module_system_name'));
                    if (is_array($modules)) {
                        $this->prepareCopyLog();
                        // copy module's assets to public folder.
                        $this->copyModuleAssetsToPublic($modules);
                        // delete assets files and folders in public that is no longer exists in module folder.
                        $this->deleteNotExists($modules);

                        // success.
                        $output['updated'] = true;
                        $output['processLogPath'] = $this->publishLog;
                        $output['formResultStatus'] = 'success';
                        $output['formResultMessage'] = sprintf(__('Success. The results is in a log file located at %1$s.'), $this->publishLog);
                    }// endif; is array for $modules
                    unset($modules);
                } else {
                    $output['formResultStatus'] = 'info';
                    $output['formResultMessage'] = __('Your module folder is already in public, it is no need to publish anything.');
                    http_response_code(200);
                }// endif module is not in the same or child of public path.
            }// endif form validated.

            unset($formValidated);
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
    }// doPublishAction


    /**
     * Prepare copy log file.
     * 
     * Delete previous one and create new one.
     */
    protected function prepareCopyLog()
    {
        if (is_file($this->publishLog) && is_writable($this->publishLog)) {
            unlink($this->publishLog);
        }

        file_put_contents($this->publishLog, '');
    }// prepareCopyLog


}
