<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\_SubControllers;


/**
 * Menu items class.
 * 
 * Works about menu (including permission check).
 * 
 * @since 0.1
 */
class MenuItems
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
     * Add additional menu items to core menu.
     * 
     * This method was called from `getMenuItems()`.
     * 
     * @param array $menuItems The menu items. This menu items should be converted array key via `convertMenuArrayKeyToFloat()` method before.
     * @param array $additionalMenuItems The additional menu items. This menu items should be converted array key via `convertMenuArrayKeyToFloat()` method before.
     * @return array Return modified menu items that additional menu items was added into it.
     */
    protected function addToMenu(array $menuItems, array $additionalMenuItems): array
    {
        if (is_array($menuItems)) {
            foreach ($menuItems as $key => $item) {
                if (version_compare($key, 0, '<')) {
                    // if menu item contain array key less than zero (above admin home).
                    // remove it.
                    unset($menuItems[$key]);
                    continue;
                }

                if (array_key_exists($key, $additionalMenuItems)) {
                    // if found duplicate array key from additional menu items with RdbAdmin menu items.
                    if (
                        is_array($additionalMenuItems[$key]) && 
                        !array_key_exists('id', $additionalMenuItems[$key]) &&
                        array_key_exists('subMenu', $additionalMenuItems[$key])
                    ) {
                        // if 'id' not exists but contain 'subMenu'.
                        // add this sub menu items to the same menu array key.
                        if (!isset($menuItems[$key]['subMenu'])) {
                            $menuItems[$key]['subMenu'] = [];
                        }
                        $menuItems[$key]['subMenu'] = $this->addToMenu($menuItems[$key]['subMenu'], $additionalMenuItems[$key]['subMenu']);
                        unset($additionalMenuItems[$key]);
                    } else {
                        // add array key +0.1 to menu item (not sub menu).
                        $newKey = $this->increaseArrayKeyDec($key, $menuItems);
                        $additionalMenuItems[$newKey] = $additionalMenuItems[$key];
                        unset($additionalMenuItems[$key]);
                        unset($newKey);
                    }
                }
            }// endforeach;
            unset($item, $key);
        }

        // add additional menu items to main menu items.
        $menuItems = $menuItems + $additionalMenuItems;

        // sort recursive.
        \Rdb\System\Libraries\ArrayUtil::staticRecursiveKsort($menuItems, SORT_NATURAL);

        return $menuItems;
    }// addToMenu


    /**
     * Convert menu items array key to float (string) value.
     * 
     * Also convert sub menu items.
     * 
     * This method was called from `getMenuItems()`.
     * 
     * @param array $menuItems The menu items.
     * @return array Return converted array keys.
     */
    protected function convertMenuArrayKeyToFloat(array $menuItems): array
    {
        foreach ($menuItems as $key => $item) {
            unset($menuItems[$key]);

            if (isset($item['subMenu']) && is_array($item['subMenu'])) {
                $item['subMenu'] = $this->convertMenuArrayKeyToFloat($item['subMenu']);
            }

            if (strpos($key, '.') !== false) {
                $menuItems[$key] = $item;
            } else {
                $menuItems[strval($key . '.0')] = $item;
            }
        }// endforeach;
        unset($item, $key);

        return $menuItems;
    }// convertMenuArrayKeyToFloat


    /**
     * Filter to list only allowed by permissions.
     * 
     * This method was called from `getMenuItems()`.
     * 
     * @param string $moduleSystemName The module system name of this menu items.
     * @param array $menuItems The menu items as seen in the [module_system_name]/ModuleData/ModuleAdmin.php or read more at \Rdb\Modules\RdbAdmin\Interfaces\ModuleAdmin interface
     * @return array Return the same array structure but filtered to list only permission granted.
     */
    protected function filterMenuPermissions(string $moduleSystemName, array $menuItems): array
    {
        if (!empty($menuItems)) {
            $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);

            foreach ($menuItems as $key => $item) {
                if (isset($item['permission']) && is_array($item['permission']) && !empty($item['permission'])) {
                    // if found menu item permission and it is not empty.
                    foreach ($item['permission'] as $permissions) {
                        if (is_array($permissions)) {
                            $permissionPage = $permissions[0];
                            unset($permissions[0]);

                            if ($UserPermissionsDb->checkPermission($moduleSystemName, $permissionPage, $permissions) === true) {
                                $permissionChecked = true;
                                break;// get out of loop $item['permission'].
                            }
                            unset($permissionPage);
                        }
                    }// endforeach;
                    unset($permissions);
                } else {
                    // if not found menu item permission or it is empty.
                    // allow all.
                    $permissionChecked = true;
                }

                if (!isset($permissionChecked) || (isset($permissionChecked) && $permissionChecked !== true)) {
                    // if permission denied.
                    unset($menuItems[$key]);
                } else {
                    if (isset($item['subMenu'])) {
                        $subMenu = $this->filterMenuPermissions($moduleSystemName, $item['subMenu']);
                        if (!empty($subMenu)) {
                            $menuItems[$key]['subMenu'] = $subMenu;
                        } else {
                            unset($menuItems[$key]['subMenu']);
                        }
                        unset($subMenu);
                    }
                }
                unset($permissionChecked);
            }// endforeach;
            unset($item, $key);

            unset($UserPermissionsDb);
        }

        return $menuItems;
    }// filterMenuPermissions


    /**
     * Get menu items from all modules and do permission check to list only allowed.
     * 
     * This method was called from `XhrCommonDataController->getUrlsMenuItems()` controller.
     * 
     * @param array $cookieData The cookie data. This will be safe time to decrypt cookie by use it from `Admin\Users\Sessions\Traits\SessionsTrait`.
     * @return array Return array list of menu items.
     */
    public function getMenuItems(array $cookieData = []): array
    {
        $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
            $this->Container,
            [
                'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Controllers/_SubControllers/MenuItems',
            ]
        ))->getCacheObject();
        // The cache key below will only be set in this controller. This cache has no deletion. It can be cleared from admin > tools menu.
        $cacheKey = 'menuItemsForUserId_' . ($cookieData['user_id'] ?? '0') . '_' . ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? '');

        if ($Cache->has($cacheKey)) {
            // if there are cached.
            $menuItems = $Cache->get($cacheKey, []);
        } else {
            // if there are NO cached.
            // get this module's menu items (core menu) first. -------------------------------
            if (
                defined('MODULE_PATH') && 
                is_file(
                    MODULE_PATH . DIRECTORY_SEPARATOR . 'RdbAdmin' . DIRECTORY_SEPARATOR . 'ModuleData' . DIRECTORY_SEPARATOR . 'ModuleAdmin.php'
                )
            ) {
                $Admin = new \Rdb\Modules\RdbAdmin\ModuleData\ModuleAdmin($this->Container);
                if ($Admin instanceof \Rdb\Modules\RdbAdmin\Interfaces\ModuleAdmin) {
                    $menuItems = $Admin->menuItems();
                    if (!is_array($menuItems) || empty($menuItems)) {
                        unset($menuItems);
                    }
                }
                unset($Admin);
            }

            if (!isset($menuItems) || (isset($menuItems) && empty($menuItems))) {
                // if menu is empty or not exists.
                $Url = new \Rdb\System\Libraries\Url($this->Container);
                $menuItems = [
                    0 => [
                        'id' => 'rdbadmin-home',
                        'permission' => [],
                        'icon' => 'fa-solid fa-gauge-high fa-fw fontawesome-icon',
                        'name' => __('Admin home'),
                        'link' => $Url->getAppBasedPath(true) . '/admin',
                    ],
                ];
                unset($Url);
            }

            $menuItems = $this->convertMenuArrayKeyToFloat($this->filterMenuPermissions('RdbAdmin', $menuItems));
            // end get core menu items. ---------------------------------------------------------

            // get other module's menu items. --------------------------------------------------
            if ($this->Container->has('Modules')) {
                /* @var $Modules \Rdb\System\Modules */
                $Modules = $this->Container->get('Modules');
                $enabledModules = $Modules->getModules();
                unset($Modules);

                if (is_array($enabledModules)) {
                    foreach ($enabledModules as $eachModule) {
                        if (strtolower($eachModule) === 'rdbadmin') {
                            continue;
                        }

                        if (is_file(MODULE_PATH . DIRECTORY_SEPARATOR . $eachModule . DIRECTORY_SEPARATOR . 'ModuleData' . DIRECTORY_SEPARATOR . 'ModuleAdmin.php')) {
                            $ModuleAdminClassName = '\\Rdb\\Modules\\' . $eachModule . '\\ModuleData\\ModuleAdmin';
                            if (class_exists($ModuleAdminClassName)) {
                                $Admin = new $ModuleAdminClassName($this->Container);
                                if ($Admin instanceof \Rdb\Modules\RdbAdmin\Interfaces\ModuleAdmin) {
                                    $additionalMenuItems = $Admin->menuItems();
                                    if (!is_array($additionalMenuItems) || empty($additionalMenuItems)) {
                                        unset($additionalMenuItems);
                                    } else {
                                        $additionalMenuItems = $this->convertMenuArrayKeyToFloat(
                                            $this->filterMenuPermissions($eachModule, $additionalMenuItems)
                                        );
                                        $menuItems = $this->addToMenu($menuItems, $additionalMenuItems);
                                    }

                                    unset($additionalMenuItems);
                                }
                                unset($Admin);
                            }
                            unset($ModuleAdminClassName);
                        }
                    }// endforeach;
                    unset($eachModule);
                }
                unset($enabledModules);
            }
            // end get other module's menu items. ---------------------------------------------

            // the last check to remove empty (filtered out) menu items. ---------------------
            if (is_array($menuItems) && !empty($menuItems)) {
                foreach ($menuItems as $key => $menuItem) {
                    if (empty($menuItem)) {
                        // if empty menu item.
                        // remove it.
                        unset($menuItems[$key]);
                    }
                }// endforeach;
                unset($key, $menuItem);
            }
            // end the last check to remove empty (filtered out) menu items. ----------------

            if ($this->Container->has('Config')) {
                /* @var $Config \Rdb\System\Config */
                $Config = $this->Container->get('Config');
                $Config->setModule('RdbAdmin');
                $cacheMenuItem = $Config->get('cacheMenuItem', 'cache', false);
            } else {
                if (defined('APP_ENV') && APP_ENV === 'development') {
                    $cacheMenuItem = false;
                } else {
                    $cacheMenuItem = true;
                }
            }

            if ($cacheMenuItem === true) {
                $cacheExpires = (1 * 60 * 60);// hour(s).
                $Cache->set($cacheKey, $menuItems, $cacheExpires);
            }

            $Config->setModule('');// restore to default.

            unset($cacheMenuItem, $Config);
        }// endif; cached

        unset($Cache, $cacheExpires, $cacheKey);

        return $menuItems;
    }// getMenuItems


    /**
     * Increase array key decimal part that is not duplicate with existing menu items.
     * 
     * This method was called from `addToMenu()`.
     * 
     * @access private
     * @param float|string $number The array key number. This number should be converted from int to float (number with dot).
     * @param array $menuItems The menu item.
     */
    protected function increaseArrayKeyDec($number, array $menuItems)
    {
        if (!is_numeric($number)) {
            throw new \InvalidArgumentException('The $number must be number.');
        }

        // make sure that number contain dot.
        if (is_float($number) && strpos($number, '.') === false) {
            $number .= '.0';
        } elseif (is_float($number) && strpos($number, '.') !== false) {
            $number = strval($number);
        }

        $found = true;
        $i = 0;
        do {
            if (!array_key_exists($number, $menuItems)) {
                $found = false;
                break;
            }

            @list($fullNumber, $decimal) = explode('.', $number);
            $decimal = $decimal + 1;
            $number = $fullNumber . '.' . $decimal;
            unset($decimal, $fullNumber);
            $i++;

            if ($i > 1000) {
                $number = round(microtime(true) * 1000) . '.0';
                $found = false;
                break;
            }
        } while ($found == true);
        unset($found, $i);

        return $number;
    }// increaseArrayKeyDec


}
