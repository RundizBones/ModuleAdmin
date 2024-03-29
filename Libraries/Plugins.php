<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Plugins class.
 * 
 * @since 0.2.4
 * @property-read array $callbackHooks The array of callback hooks. The format is `[$tag][$priority][$idHash]['callback' => 'callbackFunctionOrClass']`.
 * @property-read array $didHooks The array of did callback hooks. The format is `[$tag][$priority][$idHash]['did' => true];
 * @property-read array $pluginsRegisteredHooks The array of plugin classes that was registered hooks.
 */
class Plugins
{


    /**
     * @var array The array of callback hooks. The format is `[$tag][$priority][$idHash]['callback' => 'callbackFunctionOrClass']`.
     */
    protected $callbackHooks = [];


    /**
     * @var array The array of did callback hooks. The format is `[$tag][$priority][$idHash]['did' => true];
     */
    protected $didHooks = [];


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
     * You maybe load this class via framework's `Container` object named `Plugins`. Example: `$Plugins = $Container->get('Plugins');`.<br>
     * This is depended on if you extended your controller from `Rdb\Modules\RdbAdmin\Controllers\BaseController`.
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
    public function __get(string $name)
    {
        $allowedProps = [
            'callbackHooks',
            'pluginsRegisteredHooks',
        ];

        if (in_array($name, $allowedProps) && property_exists($this, $name)) {
            return $this->{$name};
        }
    }// __get


    /**
     * Hooks a function onto a specific tag.
     * 
     * @link https://core.trac.wordpress.org/browser/tags/5.3/src/wp-includes/plugin.php Copied from WordPress.
     * @link https://core.trac.wordpress.org/browser/tags/5.3/src/wp-includes/class-wp-hook.php Copied from WordPress.
     * @param string $tag The name of hook.
     * @param string|array|callable $callback The function or class to be called.
     * @param int $priority Priority that function will be executed. Lower number will be execute earlier. Default is 10.
     * @throws \InvalidArgumentException Throw the exception if argument is wrong type or wrong value.
     */
    public function addHook(string $tag, $callback, int $priority = 10)
    {
        if (!is_string($callback) && !is_array($callback) && !is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid argument type for $callback argument.');
        }

        $priority = (int) $priority;

        $idHash = $this->getHookIdHash($tag, $callback);
        $priorityExists = (isset($this->callbackHooks[$tag][$priority]));

        $this->callbackHooks[$tag][$priority][$idHash] = [
            'callback' => $callback,
        ];

        if (!$priorityExists) {
            ksort($this->callbackHooks[$tag], SORT_NUMERIC);
        }

        unset($idHash, $priorityExists);
    }// addHook


    /**
     * Alter the data use callback function that have been added to a hook.
     * 
     * @link https://developer.wordpress.org/reference/functions/apply_filters/ Argument description copied from WordPress.
     * @since 1.2.5
     * @param string $tag The name of hook.
     * @param mixed $data The data to alter.
     * @param mixed $args Additional parameters to pass to callback functions.
     * @return mixed The altered data after hooked all functions are applied to it.
     */
    public function doAlter(string $tag, $data, ...$args)
    {
        if (!isset($this->callbackHooks[$tag])) {
            return $data;
        }

        foreach ($this->callbackHooks[$tag] as $priority => $items) {
            if (is_array($items)) {
                foreach($items as $idHash => $subItem) {
                    array_unshift($args, $data);// put $data to front of $args.
                    $result = call_user_func_array($subItem['callback'], $args);
                    $args = array_slice($args, 1);// now remove first array of $args which is $data out.
                    $data = $result;
                }// endforeach;
                unset($idHash, $result, $subItem);
            }
        }// endforeach;
        unset($items, $priority);

        return $data;
    }// doAlter


    /**
     * Calls the callback function that have been added to a hook.
     * 
     * @link https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Extension!ModuleHandler.php/function/ModuleHandler%3A%3AinvokeAll/ Copied from Drupal.
     * @link https://alanstorm.com/drupal_module_hooks/ How Drupal module/hook works.
     * @param string $tag The name of hook.
     * @param array $args Additional arguments which are passed on to the function hooked. Default is empty.
     * @return array If there is no plugin hooks then it will be return `$args` argument.<br>
     *                  If the plugin that hooked has nothing to return then it will be return empty array otherwise it will be return hooked results.
     */
    public function doHook(string $tag, array $args = [])
    {
        if (!isset($this->callbackHooks[$tag])) {
            return $args;
        }

        $return = [];

        foreach ($this->callbackHooks[$tag] as $priority => $items) {
            if (is_array($items)) {
                foreach($items as $idHash => $subItem) {
                    $result = call_user_func_array($subItem['callback'], array_values($args));
                    $this->didHooks[$tag][$priority][$idHash]['did'] = true;
                    if (isset($result) && is_array($result)) {
                        $return = \Drupal\Component\Utility\NestedArray::mergeDeep($return, $result);
                    } elseif (isset($result)) {
                        $return[] = $result;
                    }
                }// endforeach;
                unset($idHash, $result, $subItem);
            }
        }// endforeach;
        unset($items, $priority);

        return $return;
    }// doHook


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

        if ($callback[0] instanceof \Closure) {
            $id .= spl_object_hash($callback[0]);
        } elseif (is_object($callback[0])) {
            $id .= get_class($callback[0]) . '->' . $callback[1];
        } elseif (is_string($callback[0])) {
            $id .= $callback[0] . '::' . $callback[1];
        } else {
            $id .= json_encode($callback[0]) . $callback[1];
        }

        return $id;
    }// getHookIdHash


    /**
     * Check if any hook has been registered.
     * 
     * @link https://core.trac.wordpress.org/browser/tags/5.3/src/wp-includes/class-wp-hook.php Copied from WordPress.
     * @param string $tag The name of hook.
     * @param string|array|callable $callback The function or class callback to check for. Default is `false`.
     * @return bool|int Return boolean if $callback is set to `false`. If $callback is check for specific function, the priority of that hook is returned, or return `false` if not found.
     * @throws \InvalidArgumentException Throw the exception if argument is wrong type or wrong value.
     */
    public function hasHook(string $tag, $callback = false)
    {
        if ($callback !== false && !is_string($callback) && !is_array($callback) && !is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid argument type for $callback argument.');
        }

        if (!isset($this->callbackHooks[$tag]) || !is_array($this->callbackHooks[$tag])) {
            // if not found this tag registered.
            return false;
        }

        if ($callback === false) {
            foreach ($this->callbackHooks[$tag] as $priority => $subItems) {
                if (!empty($subItems)) {
                    return true;
                }
            }// endforeach;
            unset($priority, $subItems);
            return false;
        }

        $idHash = $this->getHookIdHash($tag, $callback);
        foreach ($this->callbackHooks[$tag] as $priority => $subItems) {
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
                            if (!is_scalar($modulePlugin)) {
                                continue;
                            }

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
                $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);
                foreach ($traces as $trace) {
                    if (isset($trace['file']) && $trace['file'] === __FILE__) {
                        // if found this file means it called infinitely. 
                        // for example: base controller call this method
                        // -> each plugin register hooks and then initialize new controller class
                        // -> each controller extends base controller
                        // -> base controller call this method (again)
                        // -> each plugin register hooks and then initialize new controller class and so on.
                        // now, stop it by break the loop.
                        break 2;
                    }
                }// endforeach;
                unset($trace, $traces);

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


    /**
     * Remove all the hooks.
     * 
     * @link https://core.trac.wordpress.org/browser/tags/5.3/src/wp-includes/class-wp-hook.php Copied from WordPress.
     * @param string $tag The name of hook.
     * @param int|false $priority The priority number to remove. Set to false to remove all priorities. Default is `false`.
     * @throws \InvalidArgumentException Throw the exception if argument is wrong type or wrong value.
     */
    public function removeAllHooks(string $tag, $priority = false)
    {
        if (is_numeric($priority)) {
            $priority = (int) $priority;
        } else {
            $priority = false;
        }

        if (isset($this->callbackHooks[$tag])) {
            if ($priority === false) {
                unset($this->callbackHooks[$tag]);
            } elseif (isset($this->callbackHooks[$tag][$priority])) {
                unset($this->callbackHooks[$tag][$priority]);
            }

            if (empty($this->callbackHooks[$tag])) {
                unset($this->callbackHooks[$tag]);
            }
        }
    }// removeAllHooks


    /**
     * Remove function from specified hook.
     * 
     * @link https://core.trac.wordpress.org/browser/tags/5.3/src/wp-includes/class-wp-hook.php Copied from WordPress.
     * @param string $tag The name of hook.
     * @param string|array|callable $callback The name of function which should be removed.
     * @param int $priority The priority of the function. Default is 10.
     * @return bool Return `true` if function is existed and removed, return `false` for otherwise.
     * @throws \InvalidArgumentException Throw the exception if argument is wrong type or wrong value.
     */
    public function removeHook(string $tag, $callback, int $priority = 10): bool
    {
        if (!is_string($callback) && !is_array($callback) && !is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid argument type for $callback argument.');
        }

        $priority = (int) $priority;
        $output = false;

        if (isset($this->callbackHooks[$tag])) {
            $idHash = $this->getHookIdHash($tag, $callback);
            if (isset($this->callbackHooks[$tag][$priority][$idHash])) {
                // if found tag hook and the specified function callback.
                $output = true;
                // remove it.
                unset($this->callbackHooks[$tag][$priority][$idHash]);
            }

            if (empty($this->callbackHooks[$tag][$priority])) {
                unset($this->callbackHooks[$tag][$priority]);
            }
            if (empty($this->callbackHooks[$tag])) {
                unset($this->callbackHooks[$tag]);
            }
        }

        unset($callback, $idHash);

        return $output;
    }// removeHook


}
