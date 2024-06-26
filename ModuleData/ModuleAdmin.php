<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\ModuleData;


/**
 * The module admin class for set permissions, menu items.
 * 
 * @since 0.1
 */
class ModuleAdmin implements \Rdb\Modules\RdbAdmin\Interfaces\ModuleAdmin
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function dashboardWidgets(): array
    {
        $output = [];

        $WidgetsController = new \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Widgets\WidgetsController($this->Container);
        $output['RdbAdmin.userSummary'] = $WidgetsController->userSummary();
        $output['RdbAdmin.lastLoggedinUsers'] = $WidgetsController->lastLoggedinUsers();
        $output['RdbAdmin.systemSummary'] = $WidgetsController->systemSummary();

        return $output;
    }// dashboardWidgets


    /**
     * {@inheritDoc}
     */
    public function definePermissions(): array
    {
        return [
            'RdbAdminPermissions' => ['managePermissions'],
            'RdbAdminModules' => ['list', 'manageModules'],
            'RdbAdminModulesPlugins' => ['listPlugins', 'managePlugins'],
            'RdbAdminModulesAssets' => ['publishAssets'],
            'RdbAdminRoles' => ['add', 'edit', 'delete', 'list', 'changePriority'],
            'RdbAdminSettings' => ['changeSettings'],
            'RdbAdminTools' => ['manageCache', 'emailTester'],
            'RdbAdminUsers' => ['add', 'edit', 'delete', 'list', 'viewLogins', 'deleteLogins', 'changeRoles'],// user cannot add or edit or delete users who are in higher role priority or cannot promote user who has same role priority to higher.
        ];
    }// definePermissions


    /**
     * {@inheritDoc}
     */
    public function permissionDisplayText(string $key = '', bool $translate = false)
    {
        if ($this->Container->has('Languages')) {
            $Languages = $this->Container->get('Languages');
        } else {
            $Languages = new \Rdb\Modules\RdbAdmin\Libraries\Languages($this->Container);
        }
        $Languages->bindTextDomain(
            'rdbadmin', 
            dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'RdbAdmin' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );

        $keywords = [];

        // pages keywords
        $keywords['RdbAdminPermissions'] = noop__('Permissions');
        $keywords['RdbAdminModules'] = noop__('Modules');
        $keywords['RdbAdminModulesPlugins'] = noop__('Modules Plugins');
        $keywords['RdbAdminModulesAssets'] = noop__('Modules Assets');
        $keywords['RdbAdminRoles'] = noop__('Roles');
        $keywords['RdbAdminSettings'] = noop__('Settings');
        $keywords['RdbAdminTools'] = noop__('Tools');
        $keywords['RdbAdminUsers'] = noop__('Users');

        // actions keywords
        $keywords['add'] = noop__('Add');
        $keywords['changeSettings'] = noop__('Change settings');
        $keywords['changePriority'] = noop__('Change priority');
        $keywords['changeRoles'] = noop__('Change roles');
        $keywords['delete'] = noop__('Delete');
        $keywords['deleteLogins'] = noop__('Delete logins');
        $keywords['edit'] = noop__('Edit');
        $keywords['emailTester'] = noop__('Email tester');
        $keywords['list'] = noop__('List items');
        $keywords['listPlugins'] = noop__('List plugins');
        $keywords['manageCache'] = noop__('Manage cache');
        $keywords['manageModules'] = noop__('Manage modules');
        $keywords['managePermissions'] = noop__('Manage permissions');
        $keywords['managePlugins'] = noop__('Manage plugins');
        $keywords['publishAssets'] = noop__('Publish assets to the public');
        $keywords['viewLogins'] = noop__('View logins');

        if (!empty($key)) {
            if (array_key_exists($key, $keywords)) {
                if ($translate === false) {
                    return $keywords[$key];
                } else {
                    return d__('rdbadmin', $keywords[$key]);
                }
            } else {
                return $key;
            }
        } else {
            return $keywords;
        }
    }// permissionDisplayText


    /**
     * {@inheritDoc}
     */
    public function menuItems(): array
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        // declare language object, set text domain to make sure that this is translation for your module.
        if ($this->Container->has('Languages')) {
            $Languages = $this->Container->get('Languages');
        } else {
            $Languages = new \Rdb\Modules\RdbAdmin\Libraries\Languages($this->Container);
        }
        $Languages->bindTextDomain(
            'rdbadmin', 
            dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'RdbAdmin' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );

        $urlBaseWithLang = $Url->getAppBasedPath(true);
        $urlBase = $Url->getAppBasedPath();

        return [
            0 => [
                'id' => 'rdbadmin-home',
                'permission' => [],
                'icon' => 'fa-solid fa-gauge-high fa-fw fontawesome-icon',
                'name' => __('Admin home'),
                'link' => $urlBaseWithLang . '/admin',
            ],// 0
            100 => [
                'id' => 'rdbadmin-users',
                'icon' => 'fa-solid fa-users fa-fw fontawesome-icon',
                'name' => __('Users'),
                'link' => $urlBaseWithLang . '/admin/users',
                'liAttributes' => [
                    'data-mainmenucontainer' => true,
                ],
                'subMenu' => [
                    0 => [
                        'id' => 'rdbadmin-users-list',
                        'permission' => [
                            ['RdbAdminUsers', 'edit', 'delete', 'list', 'viewLogins', 'deleteLogins'],
                        ],
                        'name' => __('Manage users'),
                        'link' => $urlBaseWithLang . '/admin/users',
                        'linksCurrent' => [
                            $urlBase . '/admin/users/actions',
                            $urlBase . '/admin/users/edit',
                            $urlBase . '/admin/users/edit/*',
                            $urlBase . '/admin/users/delete/me',
                            $urlBase . '/admin/users/*/previous-emails',
                        ],
                    ],
                    1 => [
                        'id' => 'rdbadmin-users-add',
                        'permission' => [
                            ['RdbAdminUsers', 'add']
                        ],
                        'name' => __('Add new user'),
                        'link' => $urlBase . '/admin/users/add',
                    ],
                    2 => [
                        'id' => 'rdbadmin-user-divider1',
                        'permission' => [
                            ['RdbAdminRoles', 'add', 'edit', 'delete', 'list', 'changePriority'],
                        ],
                        'name' => '',
                        'link' => '#',
                        'aAttributes' => [
                            'onclick' => 'return false;',
                        ],
                        'liAttributes' => [
                            'class' => 'divider',
                        ],
                    ],
                    3 => [
                        'id' => 'rdbadmin-user-roles',
                        'permission' => [
                            ['RdbAdminRoles', 'add', 'edit', 'delete', 'list', 'changePriority'],
                        ],
                        'name' => __('Manage roles'),
                        'link' => $urlBaseWithLang . '/admin/roles',
                        'linksCurrent' => [
                            $urlBase . '/admin/roles/add',
                            $urlBase . '/admin/roles/edit/*',
                        ],
                    ],
                    4 => [
                        'id' => 'rdbadmin-user-permissions',
                        'permission' => [
                            ['RdbAdminPermissions', 'managePermissions'],
                        ],
                        'name' => __('Manage permissions'),
                        'link' => $urlBaseWithLang . '/admin/permissions',
                        'linksCurrent' => [
                            $urlBase . '/admin/permissions',
                            $urlBase . '/admin/permissions/*',
                        ],
                    ],
                ],// subMenu
            ],// 100
            101 => [
                'id' => 'rdbadmin-modules',
                'permission' => [
                    ['RdbAdminModules', 'list', 'manageModules'],
                    ['RdbAdminModulesPlugins', 'listPlugins', 'managePlugins'],
                    ['RdbAdminModulesAssets', 'publishAssets'],
                ],
                'icon' => 'fa-solid fa-cubes fa-fw fontawesome-icon',
                'name' => __('Modules'),
                'link' => $urlBaseWithLang . '/admin/modules',
                'liAttributes' => [
                    'data-mainmenucontainer' => true,
                ],
                'subMenu' => [
                    0 => [
                        'id' => 'rdbadmin-modules-list',
                        'permission' => [
                            ['RdbAdminModules', 'list', 'manageModules'],
                        ],
                        'name' => __('Modules'),
                        'link' => $urlBaseWithLang . '/admin/modules',
                    ],
                    1 => [
                        'id' => 'rdbadmin-modules-listplugins',
                        'permission' => [
                            ['RdbAdminModulesPlugins', 'listPlugins', 'managePlugins'],
                        ],
                        'name' => __('Modules Plugins'),
                        'link' => $urlBaseWithLang . '/admin/modules/plugins',
                    ],
                    2 => [
                        'id' => 'rdbadmin-modules-assets',
                        'permission' => [
                            ['RdbAdminModulesAssets', 'publishAssets'],
                        ],
                        'name' => __('Modules Assets'),
                        'link' => $urlBaseWithLang . '/admin/modules/assets',
                    ],
                ],//subMenu
            ],// 101
            102 => [
                'id' => 'rdbadmin-settings',
                'icon' => 'fa-solid fa-sliders fa-fw fontawesome-icon',
                'name' => __('Settings'),
                'link' => $urlBaseWithLang . '/admin/settings',
                'liAttributes' => [
                    'data-mainmenucontainer' => true,
                ],
                'subMenu' => [
                    0 => [
                        'id' => 'rdbadmin-settings-rdbadminmodule',
                        'permission' => [
                            ['RdbAdminSettings', 'changeSettings'],
                        ],
                        'name' => __('Main settings'),
                        'link' => $urlBaseWithLang . '/admin/settings',
                    ],
                ],//subMenu
            ],// 102
            103 => [
                'id' => 'rdbadmin-tools',
                'icon' => 'fa-solid fa-screwdriver-wrench fa-fw fontawesome-icon',
                'name' => __('Tools'),
                'link' => '#',
                'liAttributes' => [
                    'data-mainmenucontainer' => true,
                ],
                'aAttributes' => [
                    'onclick' => 'return false;',
                ],
                'subMenu' => [
                    0 => [
                        'id' => 'rdbadmin-tools-managecache',
                        'permission' => [
                            ['RdbAdminTools', 'manageCache'],
                        ],
                        'name' => __('Manage cache'),
                        'link' => $urlBaseWithLang . '/admin/tools/cache',
                    ],
                    1 => [
                        'id' => 'rdbadmin-tools-emailtester',
                        'permission' => [
                            ['RdbAdminTools', 'emailTester'],
                        ],
                        'name' => __('Email tester'),
                        'link' => $urlBaseWithLang . '/admin/tools/email-tester',
                    ],
                ],
            ],// 103
        ];
    }// menuItems


}
