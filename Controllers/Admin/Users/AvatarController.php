<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Controllers\Admin\Users;


/**
 * Avatar (profile picture) controller.
 */
class AvatarController extends \Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\UsersTrait;


    use Traits\UsersEditingTrait;


    public function deleteAction($user_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (!$this->isMe($user_id)) {
            $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['edit']);
        }

        if (session_id() === '') {
            session_start();
        }

        $user_id = (int) $user_id;

        $Csrf = new \Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \System\Libraries\Url($this->Container);

        $output = [];
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        // make patch data into $_DELETE variable.
        $this->Input->delete('');
        global $_DELETE;

        if (
            isset($_DELETE[$csrfName]) &&
            isset($_DELETE[$csrfValue]) &&
            $Csrf->validateToken($_DELETE[$csrfName], $_DELETE[$csrfValue])
        ) {
            // if validate csrf passed.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            $FileSystem = new \System\Libraries\FileSystem(PUBLIC_PATH);

            $formValidated = false;
            if ($this->isEditingHigherRole($user_id) === true) {
                $formValidated = false;
                http_response_code(403);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to edit user who has higher priority role than you.');
            } else {
                $formValidated = true;
            }

            if ($this->Input->delete('user_id') != $user_id) {
                $formValidated = false;
                http_response_code(403);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to edit the selected user.');
            }

            if (isset($formValidated) && $formValidated === true) {
                $UserFieldsDb = new \Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                $userAvatar = $UserFieldsDb->get($user_id, 'rdbadmin_uf_avatar');
                if (!empty($userAvatar) && isset($userAvatar->field_value)) {
                    $output['deletePreviousAvatar'] = true;
                    $output['previousAvatar'] = $userAvatar->field_value;
                    $FileSystem->deleteFile($userAvatar->field_value);
                }
                $output['deleteSuccess'] = $UserFieldsDb->delete($user_id, 'rdbadmin_uf_avatar');
                unset($userAvatar, $UserFieldsDb);
            }

            unset($FileSystem, $formValidated);
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
     * For hold translation from Rundiz/Upload only. It will be get translation in the external translation program such as Poedit.<br>
     * There is no called from anywhere.
     */
    private function forTranslation()
    {
        noop__('The uploaded file exceeds the max file size directive. (%s &gt; %s).');
        noop__('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
        noop__('The uploaded file was only partially uploaded.');
        noop__('You did not upload the file.');
        noop__('Missing a temporary folder.');
        noop__('Failed to write file to disk.');
        noop__('A PHP extension stopped the file upload.');
        noop__('Unable to move uploaded file. (%s =&gt; %s)');
        noop__('Error! Found php embedded in the uploaded file. (%s).');
        noop__('Error! Found cgi/perl embedded in the uploaded file. (%s).');
        noop__('Error! Found shell script embedded in the uploaded file. (%s).');
        noop__('The target location where the uploaded file(s) will be moved to is not folder or directory.');
        noop__('The target location where the uploaded file(s) will be moved to is not writable. Please check the folder permission.');
        noop__('Unable to validate extension for the file %s.');
        noop__('Unable to validate the file extension and mime type. (%s). This file extension was not set in the &quot;file_extensions_mime_types&quot; property.');
        noop__('You have uploaded the file that is not allowed extension. (%s)');
        noop__('The uploaded file has invalid mime type. (%s : %s).');
        noop__('Unable to validate mime type. The format of &quot;file_extensions_mime_types&quot; property is incorrect.');
        noop__('The uploaded image dimensions are larger than allowed max dimensions. (%s &gt; %s).');
        noop__('The uploaded image contain no image or multiple images. (%s).');
    }// forTranslation


    /**
     * Upload new, or change avatar.
     * 
     * @param string $user_id
     * @return string
     */
    public function uploadAction($user_id = ''): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (!$this->isMe($user_id)) {
            $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['edit']);
        }

        if (session_id() === '') {
            session_start();
        }

        $user_id = (int) $user_id;

        $Csrf = new \Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \System\Libraries\Url($this->Container);

        $output = [];
        list($csrfName, $csrfValue) = $Csrf->getTokenNameValueKey(true);

        if (
            isset($_POST[$csrfName]) &&
            isset($_POST[$csrfValue]) &&
            $Csrf->validateToken($_POST[$csrfName], $_POST[$csrfValue])
        ) {
            // if validate csrf passed.
            unset($_POST[$csrfName], $_POST[$csrfValue]);

            $targetAvatarFolder = 'rdbadmin-public/avatar/' . date('Y');
            $FileSystem = new \System\Libraries\FileSystem(PUBLIC_PATH);
            $FileSystem->createFolder($targetAvatarFolder);// create folder if not exists.

            $formValidated = false;
            if ($this->isEditingHigherRole($user_id) === true) {
                $formValidated = false;
                http_response_code(403);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to edit user who has higher priority role than you.');
            } else {
                $formValidated = true;
            }

            if (!isset($_FILES['user_fields']['name']['rdbadmin_uf_avatar'])) {
                $formValidated = false;
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('You did not upload the file.');
                http_response_code(400);
            }

            if (isset($formValidated) && $formValidated === true) {
                $Upload = new \Rundiz\Upload\Upload('user_fields');
                $Upload->allowed_file_extensions = ['jpg', 'jpeg', 'gif', 'png'];
                $Upload->move_uploaded_to = realpath(PUBLIC_PATH . DIRECTORY_SEPARATOR . $targetAvatarFolder);
                $Upload->new_file_name = 'avatar-user-id-' . $user_id . '-' . md5(time());
                $Upload->security_scan = true;
                $uploadResult = $Upload->upload();
                $uploadData = $Upload->getUploadedData();

                if ($uploadResult === true && isset($uploadData['rdbadmin_uf_avatar'])) {
                    // if upload success.
                    // resize image.
                    $resizeImage = false;
                    $Image = new \Rundiz\Image\Drivers\Gd($uploadData['rdbadmin_uf_avatar']['full_path_new_name']);
                    $resizeResult = $Image->resize(400, 400);
                    $saveImageResult = $Image->save($uploadData['rdbadmin_uf_avatar']['full_path_new_name']);
                    if ($resizeResult === false || $saveImageResult === false) {
                        $output['formResultStatus'] = 'error';
                        $output['formResultMessage'] = __($Image->status_msg);
                        http_response_code(400);
                    } else {
                        $resizeImage = true;
                    }
                    unset($Image, $resizeResult, $saveImageResult);

                    if (isset($resizeImage) && $resizeImage === true) {
                        $UserFieldsDb = new \Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                        // delete previous avatar of this user.
                        $userAvatar = $UserFieldsDb->get($user_id, 'rdbadmin_uf_avatar');
                        if (!empty($userAvatar) && isset($userAvatar->field_value)) {
                            $output['deletePreviousAvatar'] = true;
                            $output['previousAvatar'] = $userAvatar->field_value;
                            $FileSystem->deleteFile($userAvatar->field_value);
                        }

                        // save new file relative path to db.
                        $UserFieldsDb->update($user_id, 'rdbadmin_uf_avatar', $targetAvatarFolder . '/' . $uploadData['rdbadmin_uf_avatar']['new_name'], true);
                        unset($userAvatar, $UserFieldsDb);

                        $output['uploadSuccess'] = true;
                        $output['urlAppBased'] = $Url->getAppBasedPath();
                        $output['domainProtocol'] = $Url->getDomainProtocol();
                        $output['relativePublicUrl'] = $targetAvatarFolder . '/' . $uploadData['rdbadmin_uf_avatar']['new_name'];
                        $output['avatarFullUrl'] = $output['domainProtocol'] . $output['urlAppBased'] . '/' . $output['relativePublicUrl'];
                    }

                    unset($resizeImage);
                }// endif; $uploadResult

                if (is_array($Upload->errorMessagesRaw) && !empty($Upload->errorMessagesRaw)) {
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'] = [];
                    foreach ($Upload->errorMessagesRaw as $errorMessage) {
                        if (isset($errorMessage['message']) && isset($errorMessage['replaces'])) {
                            $output['formResultMessage'][] = vsprintf(__($errorMessage['message']), $errorMessage['replaces']);
                        }
                    }// endforeach;
                    unset($errorMessage);
                    http_response_code(400);
                }

                unset($Upload);
            }

            unset($FileSystem, $formValidated);
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
    }// uploadAction


}
