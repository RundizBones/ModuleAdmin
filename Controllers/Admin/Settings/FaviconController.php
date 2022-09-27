<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Settings;


/**
 * Favicon settings.
 * 
 * @link https://en.wikipedia.org/wiki/Favicon Reference.
 * @link https://stackoverflow.com/questions/48956465/favicon-standard-2022-svg-ico-png-and-dimensions Reference.
 * @link https://learn.microsoft.com/th-th/previous-versions/windows/internet-explorer/ie-developer/samples/dn455106(v=vs.85) For MS Edge.
 * @since 1.2.4
 */
class FaviconController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    /**
     * @var array Allowed file extension for favicon. Extension without dot.
     */
    const allowedFileExtensions = ['ico', 'gif', 'png'];

    /**
     * @var array The all sizes that will be use for resize. Each array contain width (array index 0), height (array index 1) from smallest to largest.
     */
    const imageSizes = [
        [16, 16],
        [32, 32],
        [180, 180],
        [192, 192],
        [270, 270],
    ];

    const maxImgWidth = 2048;

    /**
     * @var int Original image file width. It will be resize original image dimension to this value.
     */
    const origImgWidth = 512;

    const maxImgHeight = 2048;

    /**
     * @var int Original image file height. It will be resize original image dimension to this value.
     */
    const origImgHeight = 512;

    /**
     * @var string The folder path that contain favicon. Related from public path (web root), use forward slash, not begins nor end with slashes.
     */
    const faviconFolderContainer = 'rdbadmin-public/favicon';


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);
    }// __construct


    /**
     * Get allowed file extensions for favicon.
     * 
     * @return array Return file extensions of allowed favicon.
     */
    public static function allowedFileExtensions(): array
    {
        return static::allowedFileExtensions;
    }// allowedFileExtensions


    /**
     * Delete favicon.
     * 
     * @return string
     */
    public function deleteAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminSettings', ['changeSettings']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf(['persistentTokenMode' => true]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make patch data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validated csrf token passed.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            $output['deleteResult'] = $this->deletePreviousFavicon(true);
            http_response_code(204);
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
    }// deleteAction


    /**
     * Delete selected file with all resizes file names.
     * 
     * @param string $filePath Relative image path from public path (root web). Not begins with slash.
     * @return array Return associative array with keys:<br>
     *              `result` (array) The associative array where key is file name found in search with glob pattern. The value is boolean where it is result of `unlink()`.<br>
     *              `allDeleted` (bool) It is `true` if all file size names were deleted, `false` for otherwise.<br>
     */
    protected function deleteFile($filePath): array
    {
        $filePath = str_replace(['\\', DIRECTORY_SEPARATOR], '/', $filePath);// normalize directory separator to use in glob pattern.

        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $filePathNoExt = preg_replace('/.' . $fileExtension . '$/iu', '', $filePath);
        $filesSearch = glob(
            str_replace(['\\', DIRECTORY_SEPARATOR], '/', PUBLIC_PATH) 
                . '/' . $filePathNoExt . '*.' . $fileExtension
        );
        $output = [];
        $output['result'] = [];
        $allDeleted = true;

        if (is_array($filesSearch)) {
            foreach ($filesSearch as $eachFile) {
                if (is_file($eachFile) && is_writable($eachFile)) {
                    $deleteResult = @unlink($eachFile);
                    $output['result'][$eachFile] = $deleteResult;
                    if (false === $deleteResult) {
                        $allDeleted = false;
                    }
                    unset($deleteResult);
                }
            }// endforeach;
            unset($eachFile);
        }

        $output['allDeleted'] = $allDeleted;
        unset($allDeleted, $fileExtension, $filePathNoExt, $filesSearch);
        return $output;
    }// deleteFile


    /**
     * Delete previous favicon.
     * 
     * @param bool $updateToEmpty Set to `true` to update this config value in DB to empty. Set to `false` for update nothing.
     * @return array Return associative array with keys:<br>
     *              `deleteFileResult` (array) The result from `deleteFile()` method. Only exists if found config value and not empty.<br>
     *              `noPreviousValueToDelete` (bool) It will be `true` if there is no previous config value to delete.<br>
     *              `updateToEmptyResult` (bool) The result from update command. Only exists if `$updateToEmpty` attribute is set to `true`.<br>
     */
    protected function deletePreviousFavicon(bool $updateToEmpty = false): array
    {
        // use manual query to get it fresh without data cache.
        $sql = 'SELECT * FROM `' . $this->Db->tableName('config') . '` WHERE `config_name` = :config_name';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        $Sth->bindValue(':config_name', 'rdbadmin_SiteFavicon');
        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($Sth);

        $output = [];
        if (is_object($result) && isset($result->config_value) && !empty($result->config_value)) {
            $deleteResult = $this->deleteFile($result->config_value);
            $output['noPreviousValueToDelete'] = false;
            $output['deleteFileResult'] = $deleteResult;
            unset($deleteResult);
        } else {
            $output['noPreviousValueToDelete'] = true;
        }

        if (true === $updateToEmpty) {
            $data = [
                'config_value' => '',
            ];
            $where = [
                'config_name' => 'rdbadmin_SiteFavicon',
            ];
            $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
            $output['updateToEmptyResult'] = $ConfigDb->update($data, $where);
            unset($ConfigDb, $data, $where);
        }

        return $output;
    }// deletePreviousFavicon


    /**
     * Move uploaded file.
     * 
     * @return array Return associative array with keys:<br>
     *              `formValidated` (bool) Form validated. The value will be `true` on validated all passed, but will be `false` if contain errors.<br>
     *              If only uploaded success, the following array keys exists.<br>
     *                  `uploadData` (array) The uploaded data. See `Rundiz\Upload\Upload::getUploadedData()`.<br>
     *                  `newName` (string) The new file name with extension, no path.<br>
     *                  `fullPathNewName` (string) The full path to new file name.<br>
     *                  `relPathNewName` (string) The relative path from public path (root web). Directory separator is forward slash (/).<br>
     */
    protected function moveUploadedFile(): array
    {
        $output = [];

        $Upload = new \Rundiz\Upload\Upload('rdbadmin_SiteFavicon');
        $Upload->allowed_file_extensions = static::allowedFileExtensions;
        $Upload->max_image_dimensions = [static::maxImgWidth, static::maxImgHeight];
        $Upload->move_uploaded_to = PUBLIC_PATH . DIRECTORY_SEPARATOR . static::faviconFolderContainer;
        $Upload->new_file_name = 'favicon-' . date('YmdHis');
        $Upload->security_scan = true;
        $uploadResult = $Upload->upload();
        $uploadData = $Upload->getUploadedData();

        if ($uploadResult === true && is_array($uploadData)) {
            // if upload success.
            $output['formValidated'] = (is_bool($uploadResult) ? $uploadResult : true);
            $output['uploadData'] = $uploadData;
            $output['newName'] = $uploadData[0]['new_name'];
            $output['fullPathNewName'] = $uploadData[0]['full_path_new_name'];
            $output['relPathNewName'] = static::faviconFolderContainer . '/' . $uploadData[0]['new_name'];
        }// endif; upload success.

        if (is_array($Upload->errorMessagesRaw) && !empty($Upload->errorMessagesRaw)) {
            // if contain error messages.
            $output['formValidated'] = false;
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = [];
            foreach ($Upload->errorMessagesRaw as $errorMessage) {
                if (isset($errorMessage['message']) && isset($errorMessage['replaces'])) {
                    $output['formResultMessage'][] = vsprintf(__($errorMessage['message']), $errorMessage['replaces']);
                }
            }// endforeach;
            unset($errorMessage);
        }// endif; contain error messages.

        unset($Upload, $uploadData, $uploadResult);

        return $output;
    }// moveUploadedFile


    /**
     * Prepare 'rdbadmin-public' folder on public path (root web).
     */
    protected function prepareRdbAdminPublicFolder()
    {
        $FileSystem = new \Rdb\System\Libraries\FileSystem(PUBLIC_PATH);
        $FileSystem->createFolder(static::faviconFolderContainer);// create folder if not exists.
        unset($FileSystem);
    }// prepareRdbAdminPublicFolder


    /**
     * Get recommended size.
     * 
     * Use in views page.
     * 
     * @return string Recommended size.
     */
    public static function recommendedSize(): string
    {
        return '512&times;512';
    }// recommendedSize


    /**
     * Resize image.
     * 
     * @param string $filePath Relative image path from public path (root web). Not begins with slash.
     * @return array Return associative array with keys:<br>
     *              `resizeResult` (bool) Resize result. If success or no resize work it will be `true`, otherwise it will be `false`.<br>
     *              Array key that exists only success.<br>
     *                  `resizedFiles` (array) Contain resized file names from smallest to largest and then uploaded size. If no resize work then it will be return only uploaded where same as `filePath` attribute.<br>
     *                      The array key of `resizedFiles` is width x height for resizes and `'upload'` for uploaded size.<br>
     *              Array keys that exists only failure.<br>
     *                  `formResultStatus` (string) The form result status.<br>
     *                  `formResultMessage` (string) The form result message.<br>
     */
    protected function resizeImage(string $filePath): array
    {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);// ext no dot.
        $supportedExt = ['gif', 'png'];
        if (!in_array(strtolower($fileExtension), $supportedExt)) {
            // if file extension is not supported by image processing driver.
            unset($fileExtension, $supportedExt);
            return [
                'resizeResult' => true,
                'resizedFiles' => ['upload' => $filePath],
            ];
        }
        unset($supportedExt);

        // get actual image size.
        list($actualWidth, $actualHeight) = getimagesize(PUBLIC_PATH . DIRECTORY_SEPARATOR . $filePath);
        // set expect original image size that should be larger or equal to.
        $expectImageSize = [static::origImgWidth, static::origImgHeight];
        if ($actualHeight < static::origImgHeight || $actualWidth < static::origImgWidth) {
            // if actual image (uploaded one) width, height is smaller than expect.
            // find the largest value from `imageSizes` constant that is NOT larger than expect size.
            $imageSizesReverse = array_reverse(static::imageSizes);
            foreach ($imageSizesReverse as $eachSize) {
                if (
                    isset($eachSize[0]) && 
                    isset($eachSize[1]) &&
                    is_numeric($eachSize[0]) &&
                    is_numeric($eachSize[1]) &&
                    (
                        $eachSize[0] <= $actualWidth &&
                        $eachSize[1] <= $actualHeight
                    )
                ) {
                    // if found size in `imageSizes` constant that is largest but smaller than expect size.
                    $expectImageSize = [$eachSize[0], $eachSize[1]];
                    break;
                }
            }// endforeach;
            unset($eachSize, $imageSizesReverse);
        }
        // end set expect size.

        $output = [];

        // resize the original (uploaded) file. -----------
        if (extension_loaded('imagick') === true) {
            $Image = new \Rundiz\Image\Drivers\Imagick(PUBLIC_PATH . DIRECTORY_SEPARATOR . $filePath);
        } else {
            $Image = new \Rundiz\Image\Drivers\Gd(PUBLIC_PATH . DIRECTORY_SEPARATOR . $filePath);
        }
        $Image->resize($expectImageSize[0], $expectImageSize[1]);
        $Image->crop($expectImageSize[0], $expectImageSize[1], 'center', 'middle');
        $result = $Image->save(PUBLIC_PATH . DIRECTORY_SEPARATOR . $filePath);
        if (false === $result) {
            // if failed to resize.
            $output['resizeResult'] = false;
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __($Image->status_msg);
            unset($actualHeight, $actualWidth, $expectImageSize, $fileExtension, $Image, $result);
            return $output;
        }
        unset($expectImageSize, $Image, $result);
        // end resize the original (uploaded) file. -----------

        // prepare new object for image resize.
        if (extension_loaded('imagick') === true) {
            $Image = new \Rundiz\Image\Drivers\Imagick(PUBLIC_PATH . DIRECTORY_SEPARATOR . $filePath);
        } else {
            $Image = new \Rundiz\Image\Drivers\Gd(PUBLIC_PATH . DIRECTORY_SEPARATOR . $filePath);
        }

        $filePathNoExt = preg_replace('/\.' . $fileExtension . '$/iu', '', $filePath);// remove .ext from file path for later use.
        $resizedFiles = [];

        // loop resizes.
        foreach (static::imageSizes as $eachSize) {
            if (!isset($eachSize[0]) || !isset($eachSize[1]) || !is_numeric($eachSize[0]) || !is_numeric($eachSize[1])) {
                // if width, height not exists in array or it is not number.
                $output['resizeResult'] = false;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('The image dimension in `imageSizes` constant is invalid.');
                unset($actualHeight, $actualWidth, $eachSize, $fileExtension, $filePathNoExt, $Image);
                return $output;
            }

            if ($eachSize[0] > $actualWidth && $eachSize[1] > $actualHeight) {
                // if new resize is larger than its actual size.
                // skip this resize.
                continue;
            }

            $Image->resize($eachSize[0], $eachSize[1]);
            $Image->crop($eachSize[0], $eachSize[1], 'center', 'middle');
            $newFileName = $filePathNoExt . '_' . $eachSize[0] . 'x' . $eachSize[1] . '.' . $fileExtension;// full path but append size to file name.
            $result = $Image->save(PUBLIC_PATH . DIRECTORY_SEPARATOR . $newFileName);

            if (false === $result) {
                $output['resizeResult'] = false;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __($Image->status_msg);
                unset($actualHeight, $actualWidth, $eachSize, $fileExtension, $filePathNoExt, $Image, $newFileName, $result);
                return $output;
            } else {
                $resizedFiles[$eachSize[0] . 'x' . $eachSize[1]] = $newFileName;
            }

            $Image->clear();
            unset($newFileName, $result);
        }// endforeach;
        unset($eachSize, $Image);

        $resizedFiles['upload'] = $filePath;
        $output['resizeResult'] = true;
        $output['resizedFiles'] = $resizedFiles;
        unset($actualHeight, $actualWidth, $fileExtension, $filePathNoExt, $resizedFiles);

        return $output;
    }// resizeImage


    /**
     * Upload, change favicon.
     * 
     * @return string
     */
    public function updateAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminSettings', ['changeSettings']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf(['persistentTokenMode' => true]);
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validated csrf token passed.
            unset($_POST[$csrfName], $_POST[$csrfValue]);

            $this->prepareRdbAdminPublicFolder();

            // move uploaded. -----------------------
            $moveResult = $this->moveUploadedFile();
            if (isset($moveResult['formValidated']) && true === $moveResult['formValidated']) {
                // if move uploaded success.
                $formValidated = true;
                unset($moveResult['formValidated']);
            } else {
                // if move uploaded failed.
                $formValidated = false;
                http_response_code(400);
                unset($moveResult['formValidated']);
            }// endif; move result
            $output = array_merge($output, $moveResult);
            unset($moveResult);
            // end move uploaded. -----------------------

            if (isset($formValidated) && true === $formValidated) {
                // if form validated. (that include moved uploaded file successfully.)
                // resize image. ------------------
                $resizeImageResult = $this->resizeImage($output['relPathNewName']);
                if (isset($resizeImageResult['resizeResult']) && true === $resizeImageResult['resizeResult']) {
                    // if resize success.
                    unset($resizeImageResult['resizeResult']);
                } else {
                    // if resize failed.
                    $formValidated = false;
                    unset($resizeImageResult['resizeResult']);
                    // delete uploaded file and resize files.
                    $this->deleteFile($output['relPathNewName']);
                    // set response code and error message.
                    http_response_code(500);
                }
                $output = array_merge($output, $resizeImageResult);
                unset($resizeImageResult);
                // end resize image. ------------------
            }// endif; form validated.

            if (true === $formValidated) {
                // if resize image success.
                // delete previous favicon. --------------
                $deleteResult = $this->deletePreviousFavicon();
                $output = array_merge($output, $deleteResult);
                unset($deleteResult);
                // end delete previous favicon. --------------

                // save new uploaded file to config DB. ---------
                $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
                $data = [
                    'config_value' => $output['resizedFiles']['upload'],
                ];
                $where = [
                    'config_name' => 'rdbadmin_SiteFavicon',
                ];
                $output['uploadResult'] = $ConfigDb->update($data, $where);
                unset($ConfigDb, $data, $where);
                // end save new uploaded file to config DB. ---------

                $output['uploadedUrl'] = $Url->getPublicUrl() . '/' . $output['resizedFiles']['upload'];
            }// endif; form validated after resize image.
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
    }// updateAction


}
