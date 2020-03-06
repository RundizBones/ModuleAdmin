<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Plugins class.
 * 
 * @since 0.2.4
 * @property-read array $pluginsRegisteredHooks The array of plugin classes that was registered hooks.
 * @property-read array $callbackActions The array of callback hook actions.
 * @property-read array $callbackFilters The array of callback hook filters.
 */
class Plugins
{


    /**
     * @var array The array of callback hook actions.
     */
    protected $callbackActions = [];


    /**
     * @var array The array of callback hook filters.
     */
    protected $callbackFilters = [];


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var array The array of plugin classes that was registered hooks.
     */
    protected $pluginsRegisteredHooks = [];


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
     * Magic get.
     * 
     * @param string $name
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
    }// __get


    /**
     * Hooks a function onto a specific action.
     * 
     * @param string $tag The name of action.
     * @param string|array|callable $callback The function or class to be called.
     * @param int $priority Priority that function will be executed. Lower number will be execute earlier. Default is 10.
     */
    public function addAction(string $tag, $callback, int $priority = 10)
    {
        return $this->addHook('action', $tag, $callback, $priority);
    }// addAction


    /**
     * Hooks a function onto a specific filter.
     * 
     * @param string $tag The name of filter.
     * @param string|array|callable $callback The function or class to be called.
     * @param int $priority Priority that function will be executed. Lower number will be execute earlier. Default is 10.
     */
    public function addFilter(string $tag, $callback, int $priority = 10)
    {
        return $this->addHook('filter', $tag, $callback, $priority);
    }// addFilter


    /**
     * Hooks a function onto a specific action or filter.
     * 
     * This method was called from `addAction()` and `addFilter()` methods.
     * 
     * @link https://core.trac.wordpress.org/browser/tags/5.3/src/wp-includes/plugin.php Copied from WordPress.
     * @link https://core.trac.wordpress.org/browser/tags/5.3/src/wp-includes/class-wp-hook.php Copied from WordPress.
     * @param string $type The type of hook. Accept 'action', 'filter'.
     * @param string $tag The name of action or filter.
     * @param string|array|callable $callback The function or class to be called.
     * @param int $priority Priority that function will be executed. Lower number will be execute earlier. Default is 10.
     * @throws \InvalidArgumentException Throw the exception if argument is wrong type or wrong value.
     */
    protected function addHook(string $type, string $tag, $callback, int $priority = 10)
    {
        if ($type !== 'action' && $type !== 'filter') {
            throw new \InvalidArgumentException(sprintf('The argument `$type` accept value action or filter, %s given', $type));
        }

        if (!is_string($callback) && !is_array($callback) && !is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid argument type for $callback argument.');
        }

        $priority = (int) $priority;

        $idHash = $this->getHookIdHash($tag, $callback);
        $callbackType = 'callback' . ucfirst($type) . 's';// create name callbackXxxs where Xxx depend on type. Example: callbackActions, callbackFilters. This is for use in dynamic property
        $priorityExists = (isset($this->{$callbackType}[$tag][$priority]));

        $this->{$callbackType}[$tag][$priority][$idHash] = [
            'callback' => $callback,
        ];

        if (!$priorityExists) {
            ksort($this->{$callbackType}[$tag], SORT_NUMERIC);
        }

        unset($callbackType, $idHash, $priorityExists);
    }// addHook


    /**
     * Get unique hook ID that is generate from arguments.
     * 
     * @param string $tag The name of hook.
     * @param string|array|callable $callback The function or class to create unique ID.
     * @return string Return hashed value.
     */
    protected function getHookIdHash(string $tag, $callback): string
    {
        $id = $tag;

        if (is_object($callback) || is_string($callback)) {
            $callback = [$callback, ''];
        } else {
            $callback = (array) $callback;
        }

        if (is_object($callback[0])) {
            $id .= spl_object_hash($callback[0]) . $callback[1];
        } else {
            $id .= json_encode($callback[0]) . $callback[1];
        }

        return sha1($id);
    }// getHookIdHash


    /**
     * Check if any action has been registered for a hook.
     * 
     * @param string $tag The name of action.
     * @param string|array|callable $callback The function or class callback to check for. Default is `false`.
     * @return bool|int Return boolean if $callback is set to `false`. If $callback is check for specific function, the priority of that hook is returned, or return `false` if not found.
     */
    public function hasAction(string $tag, $callback = false)
    {
        return $this->hasHook('action', $tag, $callback);
    }// hasAction


    /**
     * Check if any action has been registered for a hook.
     * 
     * @param string $tag The name of filter.
     * @param string|array|callable $callback The function or class callback to check for. Default is `false`.
     * @return bool|int Return boolean if $callback is set to `false`. If $callback is check for specific function, the priority of that hook is returned, or return `false` if not found.
     */
    public function hasFilter(string $tag, $callback = false)
    {
        return $this->hasHook('filter', $tag, $callback);
    }// hasFilter


    /**
     * Check if any action has been registered for a hook.
     * 
     * @link https://core.trac.wordpress.org/browser/tags/5.3/src/wp-includes/class-wp-hook.php Copied from WordPress.
     * @param string $tag The name of action or filter.
     * @param string|array|callable $callback The function or class callback to check for. Default is `false`.
     * @return bool|int Return boolean if $callback is set to `false`. If $callback is check for specific function, the priority of that hook is returned, or return `false` if not found.
     * @throws \InvalidArgumentException Throw the exception if argument is wrong type or wrong value.
     */
    protected function hasHook(string $type, string $tag, $callback = false)
    {
        if ($type !== 'action' && $type !== 'filter') {
            throw new \InvalidArgumentException(sprintf('The argument `$type` accept value action or filter, %s given', $type));
        }

        if ($callback !== false && !is_string($callback) && !is_array($callback) && !is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid argument type for $callback argument.');
        }

        $callbackType = 'callback' . ucfirst($type) . 's';// create name callbackXxxs where Xxx depend on type. Example: callbackActions, callbackFilters. This is for use in dynamic property

        if (!isset($this->{$callbackType}[$tag]) || !is_array($this->{$callbackType}[$tag])) {
            // if not found this tag registered.
            return false;
        }

        if ($callback === false) {
            foreach ($this->{$callbackType}[$tag] as $priority => $subItems) {
                if (!empty($subItems)) {
                    return true;
                }
            }// endforeach;
            unset($priority, $subItems);
            return false;
        }

        $idHash = $this->getHookIdHash($tag, $callback);
        foreach ($this->{$callbackType}[$tag] as $priority => $subItems) {
            if (isset($subItems[$idHash])) {
                return $priority;
            }
        }// endforeach;
        unset($priority, $subItems);

        return false;
    }// hasHook


    /**
     * List plugins
     * 
     * @param array $options Available options:<br>
    *                           `availability` (string) accept '' (empty string - means all), 'enabled', 'disabled'. Default is empty string.,<br>
    *                           `unlimited` (bool) set to `true` to show unlimited items, unset or set to `false` to show limited items. Default is `false`.,<br>
     *                          `limit` (int) limit items per page. maximum is 100.,<br>
     *                          `offset` (int) offset or start at record. 0 is first record.,<br>
     * @return array Return associative array with `total` and `items` in keys.
     */
    public function listPlugins(array $options = []): array
    {
        $availability = ($options['availability'] ?? '');
        // validate availability value.
        if ($availability !== 'enabled' && $availability !== 'disabled') {
            $availability = '';
        }

        // prepare options and check if incorrect.
        if (!isset($options['offset']) || !is_numeric($options['offset'])) {
            $options['offset'] = 0;
        }
        if (!isset($options['unlimited']) || (isset($options['unlimited']) && $options['unlimited'] !== true)) {
            if (!isset($options['limit']) || !is_numeric($options['limit'])) {
                $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
                $options['limit'] = $ConfigDb->get('rdbadmin_AdminItemsPerPage', 20);
                unset($ConfigDb);
            } elseif (isset($options['limit']) && $options['limit'] > 100) {
                $options['limit'] = 100;
            }
        }

        $output = [];
        $plugins = [];// list of fetched plugins.

        if ($this->Container->has('Modules')) {
            /* @var $Modules \Rdb\System\Modules */
            $Modules = $this->Container->get('Modules');
        } else {
            $Modules = new \Rdb\System\Modules($this->Container);
        }

        $enabledModules = $Modules->getModules();
        sort($enabledModules, SORT_NATURAL);
        unset($Modules);

        if (is_array($enabledModules)) {
            $FileSystem = new \Rdb\System\Libraries\FileSystem(MODULE_PATH);

            foreach ($enabledModules as $moduleSystemName) {
                // loop each module
                if ($FileSystem->isDir($moduleSystemName . '/Plugins')) {
                    $modulePlugins = $FileSystem->listFiles($moduleSystemName . '/Plugins', 'folders');
                    sort($modulePlugins, SORT_NATURAL);

                    if (is_array($modulePlugins)) {
                        foreach ($modulePlugins as $modulePlugin) {
                            // loop each plugin inside this module.
                            // $modulePlugin is already included $moduleSystemName . '/Plugins/'
                            $expModulePlugin = explode('/', str_replace(['\\', '/'], '/', $modulePlugin));
                            $modulePluginSystemName = $expModulePlugin[(count($expModulePlugin) - 1)];
                            unset($expModulePlugin);

                            // get class, target interface to check instance of without initialize new class object.
                            $modulePluginClass = 'Rdb\\Modules\\' . str_replace('/', '\\', $modulePlugin) . '\\' . $modulePluginSystemName;
                            $ReflectionClassTargetInstance = new \ReflectionClass('\\Rdb\\Modules\\RdbAdmin\\Interfaces\\Plugins');
                            if (class_exists($modulePluginClass)) {
                                $ReflectionPlugin = new \ReflectionClass($modulePluginClass);
                                $pluginInstance = $ReflectionPlugin->newInstanceWithoutConstructor();
                                unset($ReflectionPlugin);
                            } else {
                                $pluginInstance = null;
                            }

                            if (
                                (
                                    $availability === '' || 
                                    ($availability === 'enabled' && !$FileSystem->isFile($modulePlugin . '/.disabled')) ||
                                    ($availability === 'disabled' && $FileSystem->isFile($modulePlugin . '/.disabled'))
                                ) && 
                                $FileSystem->isFile($modulePlugin . '/' . $modulePluginSystemName . '.php') && 
                                class_exists($modulePluginClass) &&
                                $ReflectionClassTargetInstance->isInstance($pluginInstance) // is instance of plugin interface.
                            ) {
                                // if matched availability AND there is module file.
                                $fileContents = file_get_contents(MODULE_PATH . DIRECTORY_SEPARATOR . $modulePlugin . '/' . $modulePluginSystemName . '.php');
                                preg_match ('|Name:(.*)$|mi', $fileContents, $name);
                                preg_match ('|URL:(.*)$|mi', $fileContents, $url);
                                preg_match ('|Version:(.*)|i', $fileContents, $version);
                                preg_match ('|Description:(.*)$|mi', $fileContents, $description);
                                preg_match ('|Author:(.*)$|mi', $fileContents, $author_name);
                                preg_match ('|Author URL:(.*)$|mi', $fileContents, $author_url);
                                unset($fileContents);

                                $plugins[] = [
                                    'id' => str_replace(['\\', '/'], '/', $modulePlugin),
                                    'module_system_name' => $moduleSystemName,
                                    'plugin_system_name' => $modulePluginSystemName,
                                    'plugin_class' => $modulePluginClass,
                                    'plugin_name' => (isset($name[1]) ? trim($name[1]) : $modulePluginSystemName),
                                    'plugin_url' => (isset($url[1]) ? trim($url[1]) : ''),
                                    'plugin_version' => (isset($version[1]) ? trim($version[1]) : ''),
                                    'plugin_description' => (isset($description[1]) ? trim($description[1]) : ''),
                                    'plugin_author' => (isset($author_name[1]) ? trim($author_name[1]) : ''),
                                    'plugin_author_url' => (isset($author_url[1]) ? trim($author_url[1]) : ''),
                                    'plugin_location' => realpath(MODULE_PATH . '/' . $modulePlugin),
                                    'enabled' => !$FileSystem->isFile($modulePlugin . '/.disabled'),
                                ];

                                unset($author_name, $author_url, $description, $name, $url, $version);
                            }

                            unset(
                                $modulePluginClass, 
                                $modulePluginSystemName, 
                                $pluginInstance, 
                                $ReflectionClassTargetInstance
                            );
                        }// endforeach;
                        unset($modulePlugin);
                    }

                    unset($modulePlugins);
                }
            }// endforeach;
            unset($FileSystem, $moduleSystemName);
        }
        unset($availability, $enabledModules);

        $output['total'] = count($plugins);
        if (!isset($options['unlimited']) || (isset($options['unlimited']) && $options['unlimited'] !== true)) {
            $plugins = array_slice($plugins, $options['offset'], $options['limit']);
        }
        $output['items'] = $plugins;
        unset($plugins);

        return $output;
    }// listPlugins


    /**
     * Register hooks for all enabled plugins.
     * 
     * Register hooks for make it ready to use from the other parts of the application.<br>
     * This method was called from `\Rdb\Modules\RdbAdmin\Controllers\BaseController`.
     */
    public function registerAllPluginsHooks()
    {
        $enabledPlugins = $this->listPlugins(['availability' => 'enabled', 'unlimited' => true]);

        if (isset($enabledPlugins['items']) && is_array($enabledPlugins['items'])) {
            foreach ($enabledPlugins['items'] as $plugin) {
                if (isset($plugin['plugin_class']) && !in_array($plugin['plugin_class'], $this->pluginsRegisteredHooks)) {
                    $PluginObject = new $plugin['plugin_class']($this->Container);
                    call_user_func([$PluginObject, 'registerHooks']);
                    unset($PluginObject);
                    $this->pluginsRegisteredHooks[] = $plugin['plugin_class'];
                }
            }// endforeach;
            unset($plugin);
        }

        unset($enabledPlugins);
    }// registerAllPluginsHooks


}
