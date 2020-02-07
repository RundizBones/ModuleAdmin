<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Interfaces;


/**
 * Module admin interface.
 * 
 * The interface for set permissions, menu items that will be use in RdbAdmin module.
 * 
 * @since 0.1
 */
interface ModuleAdmin
{


    /**
     * The class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container);


    /**
     * Get admin dashboard (admin home) widgets HTML.
     * 
     * If your module doesn't have any dashboard widget, create this function and return empty array.<br>
     * The return array format should be.
     *<pre>
     *return [
     *    'Modulename.WidgetID' => [
     *        'rowHero' => false, // boolean - `true` or `false`. skip this if dont use it.
     *        'classes' => 'additional css class names', // skip this if dont use it.
     *        'js' => 'path/to/js/file.js', // skip this if dont use it.
     *        'content' => '&lt;h1&gt;Widget title&lt;/h1&gt;Widget HTML contents.',
     *    ],
     *    'Modulename.AnotherWidgetID' => [
     *        // ... same format as above
     *    ],
     *];
     *</pre>
     * Each main array key should begins with your module name and follow with unique ID.
     * 
     * @return array Return array of admin dashboard widgets. Each array item contain one widget.
     */
    public function dashboardWidgets(): array;


    /**
     * Define module's permissions.
     * 
     * If your module doesn't want to use permission feature, create this function and return empty array.<br>
     * All the permissions for your module will be define here to make it able to manage via admin page.<br>
     * Example:
     * <pre>
     * return array(
     *     'page1' => array('action1', 'action2', '...'),// page1 is for set the page name (or controller name) that this permission will be set. 
     *         // action1, action2, and more are the actions for set that what user can do in this page.
     *     'page2' => array('action1', 'action2', '...'),
     *     // the permission page name should begins with your module system name (module folder name).
     *     // below is an example.
     *     // 'RdbAdminUsers' => array('add', 'edit', 'delete', 'list'),
     * )
     * </pre>
     * 
     * @return array Return the module's permissions in associative array.
     */
    public function definePermissions(): array;


    /**
     * Set the menu items.
     * 
     * If your module doesn't want to use menu item feature, create this function and return empty array.<br>
     * The menu items will be appears on admin side bar.<br>
     * Example:
     * <pre>
     * return array(
     *     0 => array(// this array index for your module should start with number 1 - 99 for before users menu, and start with number 105 for the bottom position
     *         'id' => 'menu-id',// the menu id must be unique.
     *         'permission' => array(// permissions to check. leave empty array means all users are accessible.
     *             // in case that you create main menu item and want to allow other module to add sub menu (now we call this main menu container), 
     *                 // leave permission empty or do not set it otherwise if permission check failed this main menu will not be displayed.
     *             // this example will show how to set multiple permission pages and actions for this menu item. 
     *             // the first value in the array is permission page name the other values are actions.
     *             // if one of permission set (permission page name and actions), or one of permission action passed then it is allowed to display this menu and accessible.
     *             array('permissionPage1', 'permissionAction1'),// only 1 action for certain page.
     *             array('permissionPage2', 'permissionAction1', 'permissionAction2'),// 2 actions for certain page.
     *         ),// end permissions to check.
     *         'icon' => 'icon-class-name',// this is for main menu only. If use FontAwesome please add `fa-fw`.
     *         'name' => 'menu item name',
     *         'link' => '/url/to/link',
     * 
     *         // any other links to check as currently open for this menu item, 
     *         // you can also use wildcard such as /admin/users/* will be match /admin/users and /admin/users/add (optional).
     *         // /admin/* /edit/* will be match /admin/xxx/edit/xxx.
     *         // links current no need to add language prefix.
     *         // links current is not checking for query string.
     *         'linksCurrent' => array('/other/possible/link/to/check/as/currently/openned', '/url/to/link2'),
     * 
     *         'liAttributes' => array(// array of main menu li attributes (optional). do not set id here.
     *             'class' => 'my-li-class my-2nd-class',
     *             'data-object' => 'value',
     *             'data-mainmenucontainer' => 'true',// this is required only if this main menu item is main menu container.
     *         ),(// array of main menu li attributes.
     *         'aAttributes' => array(// array of main menu a attributes (optional). do not set id, href link here.
     *             'class' => 'my-a-class my-2nd-class',
     *             'onclick' => 'return false;',
     *         ),(// array of main menu a attributes.
     *         'subMenu' => array(
     *             // sub menu items (optional). the array structure inside this is the same as you see listed above. please add only 1 level of sub menu items.
     *             0 => array(
     *                 'id' => 'sub-menu-id',
     *                 'permission' => array(...),
     *                 'name' => 'sub menu item name',
     *                 'link' => '/link/to/sub/page',
     *                 'linksCurrent' => array(...),// (optional)
     *                 'liAttributes' => array(...),// (optional)
     *                 'aAttributes' => array(...),// (optional)
     *             ),
     *             1 => array(
     *                 ...
     *             ),
     *         ),// end sub menu items.
     *     ),
     *     1 => array(
     *         // ... same structure as above.
     *     ),
     *     // ...
     * )
     * </pre>
     * 
     * You can use this class/method to **add** new menu items (and maybe with its sub menu) to the RdbAdmin module's menu item.<br>
     * You can **add** sub menu items to the RdbAdmin module's menu item by remove 'id' in your menu item and use the same array key as target menu.<br>
     * You cannot replace, make change, delete the existing menu items (including sub menu items) via these menu data.<br>
     * If the ID in main menu item is duplicated with existing menu item, it will be add after the existing one.<br>
     * 
     * 
     * @return array Return the menu items for this module.
     */
    public function menuItems(): array;


    /**
     * Set display text for each permission page, action.
     * 
     * If your module doesn't want to use permission feature, create this function and return empty string.<br>
     * The display text here did not translated yet.<br>
     * For example: the keyword is 'RdbAdminUsers', the display text is 'Manage users' in English.<br>
     * To translate into other languages, you have to use function like `__()` to translate them again.<br>
     * Example source code and translation:
     * <pre>
     * if ($this->Container->has(&#039;Languages&#039;)) {
     *     $Languages = $this->Container->get(&#039;Languages&#039;);
     * } else {
     *     $Languages = new \Rdb\Modules\RdbAdmin\Libraries\Languages($this->Container);
     * }
     * $Languages-&gt;bindTextDomain(
     *     &#039;rdbadmin&#039;, 
     *     MODULE_PATH . DIRECTORY_SEPARATOR . &#039;RdbAdmin&#039; . DIRECTORY_SEPARATOR . &#039;languages&#039; . DIRECTORY_SEPARATOR . &#039;translations&#039;
     * );
     * $Languages-&gt;getTranslator()-&gt;register();
     * $keywords = [];
     * $keywords[&#039;RdbAdminUsers&#039;] = noop__(&#039;Users&#039;);
     * if (!empty($key)) {
     *     if (array_key_exists($key, $keywords)) {
     *         if ($translate === false) {
     *             return $keywords[$key];
     *         } else {
     *             return d__(&#039;rdbadmin&#039;, $keywords[$key]);
     *         }
     *     } else {
     *         return &#039;&#039;;
     *     }
     * } else {
     *     return $keywords;
     * }
     * </pre>
     * 
     * @param string $key To get specific display text for the permission keyword, set the keyword here and it will return readable text value.
     * @param bool $translate Set to `false` (default) to not translate, set to `true` to translate it. The translation will work only if `$key` argument is not empty.
     * @return mixed Return associative array where key is permission keyword if no keyword specified in parameter.<br>
     *                          Return string (or empty string) if permission keyword is specified in parameter.
     */
    public function permissionDisplayText(string $key = '', bool $translate = false);


}
