<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Sessions;


/**
 * List login sessions controller.
 * 
 * @since 0.1
 */
class SessionsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Traits\UsersTrait;


    /**
     * Do delete login sessions via REST.
     * 
     * @param string $user_id
     * @return string
     */
    public function doDeleteAction($user_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['deleteLogins']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
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
            // if validated token to prevent CSRF.
            unset($_DELETE[$csrfName], $_DELETE[$csrfValue]);

            $action = $this->Input->delete('action');
            $userlogin_ids = $this->Input->delete('userlogin_ids');

            $formValidated = false;
            $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
            if ($UsersRolesDb->isEditingHigherRole((int) ($this->userSessionCookieData['user_id'] ?? 0), (int) $user_id)) {
                http_response_code(403);
                $output['formResultStatus'] = 'error';
                $output['formResultMessage'] = __('Unable to edit user who has higher priority role than you.');
            } else {
                $formValidated = true;
            }
            unset($UsersRolesDb);

            if ($formValidated === true) {
                if (is_null($action) || $action === '') {
                    http_response_code(400);
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = __('Please select an action.');
                    $formValidated = false;
                } elseif ($action !== 'empty' && empty($userlogin_ids)) {
                    http_response_code(400);
                    $output['formResultStatus'] = 'error';
                    $output['formResultMessage'][] = __('Please select at least one session.');
                    $formValidated = false;
                }
            }

            if ($formValidated === true) {
                $sql = 'DELETE FROM `' . $this->Db->tableName('user_logins') . '` WHERE';
                $bindValues = [];
                if ($action === 'empty') {
                    $sql .= ' `user_id` = :user_id';
                    $bindValues[':user_id'] = $user_id;
                } else {
                    $userlogin_ids_array = explode(',', $userlogin_ids);
                    $userlogin_ids_array = array_map('trim', $userlogin_ids_array);
                    $i = 1;
                    $totalLoginIds = count($userlogin_ids_array);
                    foreach ($userlogin_ids_array as $eachUserLoginId) {
                        $sql .= ' `userlogin_id` = :userlogin_id' . $i;
                        $bindValues[':userlogin_id' . $i] = $eachUserLoginId;
                        if ($i < $totalLoginIds) {
                            $sql .= ' OR';
                        }
                        $i++;
                    }
                    unset($eachUserLoginId, $i, $totalLoginIds, $userlogin_ids_array);
                }

                // prepare
                $Sth = $this->Db->PDO()->prepare($sql);
                // bind whereValues
                foreach ($bindValues as $placeholder => $value) {
                    $Sth->bindValue($placeholder, $value);
                }// endforeach;
                unset($placeholder, $value);
                $output['deleted'] = $Sth->execute();
                $Sth->closeCursor();
                unset($sql, $Sth);

                if (isset($output['deleted']) && $output['deleted'] === true) {
                    $output['formResultStatus'] = 'success';
                    $output['formResultMessage'] = __('Deleted successfully.');
                    http_response_code(200);
                }
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
    }// doDeleteAction


    /**
     * Get login sessions.
     * 
     * @param string $user_id
     * @param array $configDb
     * @return array
     */
    protected function doGetSessions($user_id, array $configDb): array
    {
        $columns = $this->Input->get('columns', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        $order = $this->Input->get('order', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
        $DataTablesJs = new \Rdb\Modules\RdbAdmin\Libraries\DataTablesJs();
        $sortOrders = $DataTablesJs->buildSortOrdersFromInput($columns, $order);
        unset($columns, $DataTablesJs, $order);

        $output = [];

        $UserLoginsDb = new \Rdb\Modules\RdbAdmin\Models\UserLoginsDb($this->Container);
        $options = [];
        $options['sortOrders'] = $sortOrders;
        $options['offset'] = $this->Input->get('start', 0, FILTER_SANITIZE_NUMBER_INT);
        $options['limit'] = $this->Input->get('length', $configDb['rdbadmin_AdminItemsPerPage'], FILTER_SANITIZE_NUMBER_INT);
        if (isset($_GET['search']['value']) && !empty(trim($_GET['search']['value']))) {
            $options['search'] = trim($_GET['search']['value']);
        }
        $options['where'] = [];
        $options['where']['user_id'] = $user_id;
        if (
            (
                isset($_GET['filterResult']) && trim($_GET['filterResult']) != ''
            )
        ) {
            $options['where']['userlogin_result'] = $this->Input->get('filterResult', 1, FILTER_SANITIZE_NUMBER_INT);
        }
        $result = $UserLoginsDb->listItems($options);
        unset($options, $sortOrders, $UserLoginsDb);

        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = ($result['total'] ?? 0);
        $output['recordsFiltered'] = $output['recordsTotal'];
        $output['listItems'] = ($result['items'] ?? []);

        return $output;
    }// doGetSessions


    protected function doGetUser($user_id): array
    {
        $output = [];

        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $where = [];
        $where['user_id'] = $user_id;
        $options = [];
        $options['getUserFields'] = false;
        $output['user'] = $UsersDb->get($where, $options);
        unset($options, $UsersDb, $where);

        return $output;
    }// doGetUser


    /**
     * Display login sessions page or get login sessions data via REST.
     * 
     * @param string $user_id
     * @return string
     */
    public function indexAction($user_id): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminUsers', ['viewLogins']);

        if (session_id() === '') {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        if ($this->Input->isNonHtmlAccept()) {
            // if no html accept type.
            $output = array_merge($output, $this->doGetSessions($user_id, $output['configDb']));
            $output = array_merge($output, $this->doGetUser($user_id));
        }

        // set generic values.
        $output = array_merge($output, $this->getUserUrlsMethods($user_id));

        $output['pageTitle'] = __('Login sessions');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            // response the data.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaUserLoginSessions'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaUserLoginSessions',
                'RdbaUserSessions',
                array_merge([
                    'userId' => (int) $user_id,
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'txtAreYouSureDelete' => __('Are you sure to delete?') . "\n" . __('This cannot be undone.'),
                    'txtAreYouSureEmpty' => __('Are you sure to delete them ALL?') . "\n" . __('This cannot be undone.'),
                    'txtCurrentSession' => __('Current session'),
                    'txtFailed' => __('Failed'),
                    'txtPleaseSelectAction' => __('Please select an action.'),
                    'txtPleaseSelectAtLeastOneSession' => __('Please select at least one session.'),
                    'txtSucceeded' => __('Succeeded'),
                    'txtUnknow' => __('Unknow'),
                ], $this->getUserUrlsMethods($user_id))
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Users/sessions_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
