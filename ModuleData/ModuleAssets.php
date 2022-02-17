<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\ModuleData;


/**
 * Module assets data.
 * 
 * @since 0.1
 */
class ModuleAssets
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Get module's assets list.
     * 
     * @see \Rdb\Modules\RdbAdmin\Libraries\Assets::addMultipleAssets() See <code>\Rdb\Modules\RdbAdmin\Libraries\Assets</code> class at <code>addMultipleAssets()</code> method for data structure.
     * @return array Return associative array with `css` and `js` key with its values.
     */
    public function getModuleAssets(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        return [
            'css' => [
                [
                    'handle' => 'rdta',
                    'file' => $publicModuleUrl . '/assets/css/rdta/rdta-bundled.min.css',
                    'version' => '2.1.15',
                ],
                // datatables----------------.
                [
                    'handle' => 'datatables',
                    'file' => $publicModuleUrl . '/assets/vendor/datatables.net/css/datatables-bundled.min.css',
                    'version' => '1.11.4',
                ],
                // end datatables----------.
                [
                    'handle' => 'rdbaCommonAdminMainLayout',
                    'file' => $publicModuleUrl . '/assets/css/common/Admin/mainLayout.css',
                    'dependency' => ['rdta'],
                ],
                [
                    'handle' => 'rdbaLoginLogout',
                    'file' => $publicModuleUrl . '/assets/css/Controllers/Admin/LoginLogoutController.css',
                    'dependency' => ['rdta'],
                ],
                [
                    'handle' => 'rdbaCommonListDataPage',
                    'file' => $publicModuleUrl . '/assets/css/Controllers/Admin/commonListDataPage.css',
                    'dependency' => ['rdta'],
                ],
                [
                    'handle' => 'rdbaAdminIndex',
                    'file' => $publicModuleUrl . '/assets/css/Controllers/Admin/IndexController.css',
                    'dependency' => ['rdta'],
                ],
                [
                    'handle' => 'rdbaUsersEdit',
                    'file' => $publicModuleUrl . '/assets/css/Controllers/Admin/Users/EditController.css',
                    'dependency' => ['rdta'],
                    'attributes' => [
                        'class' => 'ajaxInjectCss'
                    ],
                ],
            ],

            'js' => [
                [
                    'handle' => 'rdta',
                    'file' => $publicModuleUrl . '/assets/js/rdta/rdta-bundled.min.js',
                    'version' => '2.1.15',
                ],
                [
                    'handle' => 'handlebars',
                    'file' => $publicModuleUrl . '/assets/vendor/handlebars/handlebars.min.js',
                    'version' => '4.7.7',
                ],
                [
                    'handle' => 'moment.js',
                    'file' => $publicModuleUrl . '/assets/vendor/moment/moment-bundled.min.js',
                    'version' => '2.29.1',
                ],
                [
                    'handle' => 'lodash',
                    'file' => $publicModuleUrl . '/assets/vendor/lodash/lodash.min.js',
                    'version' => '4.17.21',
                ],
                [
                    'handle' => 'sortableJS',
                    'file' => $publicModuleUrl . '/assets/vendor/sortablejs/Sortable.min.js',
                    'version' => '1.14.0',
                ],
                // datatables----------------.
                [
                    'handle' => 'datatables',
                    'file' => $publicModuleUrl . '/assets/vendor/datatables.net/js/datatables-bundled.min.js',
                    'dependency' => ['rdta'],
                    'version' => '1.11.4',
                ],
                [
                    'handle' => 'datatables-plugins-pagination',
                    'file' => $publicModuleUrl . '/assets/vendor/datatables.net/plugins/pagination/input.js',
                    'dependency' => ['rdta', 'datatables'],
                    'version' => '1.11.4',
                ],
                // end datatables----------.
                [
                    'handle' => 'rdbaCommon',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/RdbaCommon.js',
                    'dependency' => ['rdta', 'lodash'],
                ],
                [
                    'handle' => 'rdbaCommonAdminPublic',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/UI/commonAdminPublic/RdbaCommonAdminPublic.js',
                    'dependency' => ['rdta', 'rdbaCommon'],
                ],
                [
                    'handle' => 'rdbaDatatables',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/UI/RdbaDatatables.js',
                    'dependency' => ['rdta', 'datatables', 'lodash', 'handlebars'],
                ],
                [
                    'handle' => 'rdbaXhrDialog',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/UI/RdbaXhrDialog.js',
                    'dependency' => ['rdta', 'lodash', 'rdbaCommon'],
                ],
                [
                    'handle' => 'rdbaHistoryState',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/UI/RdbaHistoryState.js',
                    'dependency' => ['rdta'],
                ],

                // /ui/xhr-common-data page.
                [
                    'handle' => 'rdbaUiXhrCommonData',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/UI/XhrCommonDataController/indexAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'handlebars', 'lodash'],
                ],

                // admin dashboard.
                [
                    'handle' => 'rdbaAdminIndex',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/IndexController/indexAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'lodash', 'handlebars', 'sortableJS'],
                ],

                // register, confirm register, login, logout, forgot password, reset password.
                [
                    'handle' => 'rdbaLogin',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/LoginController/indexAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaCommonAdminPublic', 'handlebars'],
                ],
                [
                    'handle' => 'rdbaLoginReset',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/LoginController/resetAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaCommonAdminPublic', 'lodash'],
                ],
                [
                    'handle' => 'rdbaLoginMfa',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/LoginController/mfaAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaCommonAdminPublic', 'lodash'],
                ],
                [
                    'handle' => 'rdbaForgotLoginPass',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/ForgotLoginPassController/indexAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaCommonAdminPublic'],
                ],
                [
                    'handle' => 'rdbaForgotLoginPassReset',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/ForgotLoginPassController/resetAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaCommonAdminPublic'],
                ],
                [
                    'handle' => 'rdbaRegister',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/RegisterController/indexAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaCommonAdminPublic'],
                ],
                [
                    'handle' => 'rdbaRegisterConfirm',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/RegisterController/confirmAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaCommonAdminPublic'],
                ],
                [
                    'handle' => 'rdbaLogout',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/LogoutController/indexAction.js',
                    'dependency' => ['rdta', 'rdbaCommon'],
                ],
                // end register, login, and related.

                // users, add user, edit user, delete me, bulk actions, login sessions.
                [
                    'handle' => 'rdbaUsers',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Users/indexAction.js',
                    'dependency' => ['rdta', 'rdbaDatatables', 'rdbaXhrDialog', 'datatables-plugins-pagination', 'rdbaCommon', 'rdbaUiXhrCommonData', 'moment.js'],
                ],
                [
                    'handle' => 'rdbaUsersAdd',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Users/addAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs'
                    ],
                ],
                [
                    'handle' => 'rdbaUsersEdit',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Users/editAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'handlebars', 'moment.js'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs'
                    ],
                ],
                [
                    'handle' => 'rdbaUsersActions',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Users/actionsAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs'
                    ],
                ],
                [
                    'handle' => 'rdbaUsersDeleteMe',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Users/deleteMeAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'moment.js'],
                ],
                [
                    'handle' => 'rdbaUsersPreviousEmails',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Users/previousEmailsAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'handlebars', 'moment.js'],
                ],
                [
                    'handle' => 'rdbaUserLoginSessions',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Users/sessionsAction.js',
                    'dependency' => ['rdta', 'rdbaDatatables', 'datatables-plugins-pagination', 'rdbaCommon', 'rdbaUiXhrCommonData', 'moment.js'],
                ],
                // end users.

                // roles, add role, edit role, bulk actions.
                [
                    'handle' => 'rdbaRoles',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Roles/indexAction.js',
                    'dependency' => ['rdta', 'sortableJS', 'rdbaDatatables', 'rdbaXhrDialog', 'rdbaCommon', 'rdbaUiXhrCommonData', 'moment.js'],
                ],
                [
                    'handle' => 'rdbaRolesAdd',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Roles/addAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs'
                    ],
                ],
                [
                    'handle' => 'rdbaRolesEdit',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Roles/editAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'handlebars', 'moment.js'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs'
                    ],
                ],
                [
                    'handle' => 'rdbaRolesActions',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Roles/actionsAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData'],
                    'attributes' => [
                        'class' => 'ajaxInjectJs'
                    ],
                ],
                // end roles.

                // permissions
                [
                    'handle' => 'rdbaPermissions',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/PermissionsController/indexAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'handlebars', 'lodash'],
                ],

                // modules plugins
                [
                    'handle' => 'rdbaModulesPlugins',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Modules/PluginsController/indexAction.js',
                    'dependency' => ['rdta', 'rdbaDatatables', 'rdbaXhrDialog', 'datatables-plugins-pagination', 'rdbaCommon', 'rdbaUiXhrCommonData', 'lodash'],
                ],
                // modules assets
                [
                    'handle' => 'rdbaModulesAssets',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Modules/AssetsController/indexAction.js',
                    'dependency' => ['rdta', 'rdbaDatatables', 'rdbaXhrDialog', 'datatables-plugins-pagination', 'rdbaCommon', 'rdbaUiXhrCommonData', 'lodash'],
                ],

                // settings
                [
                    'handle' => 'rdbaSettings',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/SettingsController/indexAction.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'lodash'],
                ],

                // tools
                [
                    'handle' => 'rdbaToolsCache',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Tools/CacheController.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'lodash'],
                ],
                [
                    'handle' => 'rdbaToolsEmailTester',
                    'file' => $publicModuleUrl . '/assets/js/Controllers/Admin/Tools/EmailTesterController.js',
                    'dependency' => ['rdta', 'rdbaCommon', 'rdbaUiXhrCommonData', 'lodash'],
                ],
            ],
        ];
    }// getModuleAssets


}
