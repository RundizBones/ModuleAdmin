<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Plugins class.
 * 
 * @since 0.2.4
 */
class Plugins
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


}
