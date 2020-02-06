<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Controllers\Admin\Users;


/**
 * Previous emails controller
 * 
 * @since 0.1
 */
class PreviousEmailsController extends \Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\UsersTrait;


    public function indexAction($user_id)
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['edit']);

        $Url = new \System\Libraries\Url($this->Container);
        $this->Languages->getHelpers();

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
                $UsersDb = new \Modules\RdbAdmin\Models\UsersDb($this->Container);
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
            $Assets = new \Modules\RdbAdmin\Libraries\Assets($this->Container);

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
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Users/previousEmails_v', $output);
            $output['pageBreadcrumb'] = renderBreadcrumbHtml($output['breadcrumb']);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


    /**
     * Check if target user has higher priority role than a user who is editing them.
     * 
     * @param string|int $user_id The target user ID.
     * @return bool Return `false` if not editing higher priority role, return `true` if yes.
     */
    protected function isEditingHigherRole($user_id): bool
    {
        if (!is_numeric($user_id)) {
            // if target user_id is not number.
            // return true to prevent user from editing.
            return true;
        }

        if (
            isset($this->userSessionCookieData) && 
            is_array($this->userSessionCookieData) && 
            array_key_exists('user_id', $this->userSessionCookieData)
        ) {
            $UsersRolesDb = new \Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
            $options = [];
            $options['where']['user_id'] = $this->userSessionCookieData['user_id'];
            $options['limit'] = 1;
            $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
            $myRoles = $UsersRolesDb->listItems($options);
            unset($options);

            if (isset($myRoles['items'])) {
                $myRoles = array_shift($myRoles['items']);

                $options = [];
                $options['where']['user_id'] = $user_id;
                $targetRoles = $UsersRolesDb->listItems($options);
                unset($options);

                if (isset($targetRoles['items']) && is_array($targetRoles['items']) && isset($targetRoles['total'])) {
                    $i = 0;
                    foreach ($targetRoles['items'] as $row) {
                        if ($row->userrole_priority < $myRoles->userrole_priority) {
                            // if target user's role is higher priority than user who is editing.
                            // return true to prevent user from editing.
                            unset($myRoles, $row, $targetRoles, $UsersRolesDb);
                            return true;
                        }
                        $i++;
                    }// endforeach;
                    unset($row);

                    if (isset($i) && $i == $targetRoles['total']) {
                        unset($myRoles, $targetRoles, $UsersRolesDb);
                        return false;
                    }
                }

                unset($targetRoles);
            }

            unset($myRoles, $UsersRolesDb);
        }

        // return true to prevent user from editing.
        return true;
    }// isEditingHigherRole


}
