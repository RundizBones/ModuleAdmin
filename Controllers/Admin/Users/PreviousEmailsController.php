<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Users;


/**
 * Previous emails controller
 * 
 * @since 0.1
 */
class PreviousEmailsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\UsersTrait;


    use Traits\UsersEditingTrait;


    public function indexAction($user_id)
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['edit']);

        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();

        // make sure that selected user has not higher role than user who is viewing.
        if ($this->isEditingHigherRole($user_id) === true) {
            $formValidated = false;
            http_response_code(403);
            $output['formResultStatus'] = 'error';
            $output['formResultMessage'] = __('Unable to edit user who has higher priority role than you.');
        }

        if ($this->Input->isNonHtmlAccept() || $this->Input->isXhr()) {
            // if request type is not html or it is ajax.
            $isRESTorXHR = true;
            if (!isset($formValidated) || (isset($formValidated) && $formValidated === true)) {
                // get previous emails data.
                $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
                $output['user'] = $UsersDb->get(['user_id' => (int) $user_id], ['getUserFields' => true]);
                unset($UsersDb);
            }
        }

        // set generic values.
        $output = array_merge($output, $this->getUserUrlsMethods($user_id));

        $output['pageTitle'] = __('Previous emails');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();
        $output['breadcrumb'] = [
            [
                'item' => __('Admin home'),
                'link' => $Url->getAppBasedPath(true) . '/admin',
            ],
            [
                'item' => __('Users'),
                'link' => $Url->getAppBasedPath(true) . '/admin/users',
            ],
            [
                'item' => __('Edit user'),
                'link' => $Url->getAppBasedPath(true) . '/admin/users/edit/' . $user_id,
            ],
            [
                'item' => __('Previous emails'),
                'link' => $Url->getCurrentUrl(true),
            ],
        ];

        // display, response part ---------------------------------------------------------------------------------------------
        if (isset($isRESTorXHR) && $isRESTorXHR === true) {
            // if custom HTTP accept, response content.
            // response the data.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('js', ['rdbaUsersPreviousEmails'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaUsersPreviousEmails',
                'RdbaPreviousEmails',
                array_merge([
                    'userId' => (int) $user_id,
                ], $this->getUserUrlsMethods($user_id))
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            include_once MODULE_PATH . '/RdbAdmin/Helpers/HTMLFunctions.php';

            $output['Assets'] = $Assets;
            $output['pageContent'] = $this->Views->render('Admin/Users/previousEmails_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
