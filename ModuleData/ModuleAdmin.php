<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\ModuleData;


/**
 * The module admin class for set permissions, menu items.
 * 
 * @since 0.1
 */
class ModuleAdmin implements \Modules\RdbAdmin\Interfaces\ModuleAdmin
{


    /**
     * @var \System\Container
     */
    protected $Container;


    /**
     * {@inheritDoc}
     */
    public function __construct(\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function dashboardWidgets(): array
    {
        $output = [];

        $WidgetsController = new \Modules\RdbAdmin\Controllers\Admin\UI\Widgets\WidgetsController($this->Container);
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
            'RdbAdminUsers' => ['add', 'edit', 'delete', 'list', 'viewLogins', 'deleteLogins'],// user cannot add or edit or delete users who are in higher role priority or cannot promote user who has same role priority to higher.
            'RdbAdminRoles' => ['add', 'edit', 'delete', 'list', 'changePriority'],
            'RdbAdminPermissions' => ['managePermissions'],
            'RdbAdminSettings' => ['changeSettings'],
            'RdbAdminTools' => ['manageCache'],
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
            $Languages = new \Modules\RdbAdmin\Libraries\Languages($this->Container);
        }
        $Languages->bindTextDomain(
            'rdbadmin', 
            dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'RdbAdmin' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );

        $keywords = [];

        // pages keywords
        $keywords['RdbAdminPermissions'] = noop__('Permissions');
        $keywords['RdbAdminRoles'] = noop__('Roles');
        $keywords['RdbAdminSettings'] = noop__('Settings');
        $keywords['RdbAdminTools'] = noop__('Tools');
        $keywords['RdbAdminUsers'] = noop__('Users');

        // actions keywords
        $keywords['add'] = noop__('Add');
        $keywords['changeSettings'] = noop__('Change settings');
        $keywords['changePriority'] = noop__('Change priority');
        $keywords['delete'] = noop__('Delete');
        $keywords['deleteLogins'] = noop__('Delete logins');
        $keywords['edit'] = noop__('Edit');
        $keywords['list'] = noop__('List items');
        $keywords['manageCache'] = noop__('Manage cache');
        $keywords['managePermissions'] = noop__('Manage permissions');
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
        $Url = new \System\Libraries\Url($this->Container);

        // declare language object, set text domain to make sure that this is translation for your module.
        if ($this->Container->has('Languages')) {
            $Languages = $this->Container->get('Languages');
        } else {
            $Languages = new \Modules\RdbAdmin\Libraries\Languages($this->Container);
        }
        $Languages->bindTextDomain(
            'rdbadmin', 
            dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'RdbAdmin' . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
        );
        $Languages->getHelpers();

        $urlBaseWithLang = $Url->getAppBasedPath(true);
        $urlBase = $Url->getAppBasedPath();

        return [
            0 => [
                'id' => 'rdbadmin-home',
                'permission' => [],
                'icon' => 'fas fa-tachometer-alt fa-fw',
                'name' => __('Admin home'),
                'link' => $urlBaseWithLang . '/admin',
            ],// 0
            100 => [
                'id' => 'rdbadmin-users',
                'permission' => [
                    ['RdbAdminUsers', 'add', 'edit', 'delete', 'list', 'viewLogins', 'deleteLogins'],
                    ['RdbAdminRoles', 'add', 'edit', 'delete', 'list', 'changePriority'],
                    ['RdbAdminPermissions', 'managePermissions'],
                ],
                'icon' => 'fas fa-users fa-fw',
                'name' => __('Users'),
                'link' => $urlBaseWithLang . '/admin/users',
                'aAttributes' => [
                    'onclick' => 'return false;',
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
                            $urlBase . '/admin/permissions/*',
                        ],
                    ],
                ],// subMenu
            ],// 100
            101 => [
                'id' => 'rdbadmin-settings',
                'icon' => 'fas fa-sliders-h fa-fw',
                'name' => __('Settings'),
                'link' => '#',
                'liAttributes' => [
                    'data-mainmenucontainer' => true,
                ],
                'aAttributes' => [
                    'onclick' => 'return false;',
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
            ],// 101
            102 => [
                'id' => 'rdbadmin-tools',
                'icon' => 'fas fa-tools fa-fw',
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
                ],
            ],// 102
        ];
    }// menuItems


}
