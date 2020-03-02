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
     * @param \Rdb\System\Container $Container The DI container.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * List plugins
     * 
     * @param array $options Available options:<br>
     *                                      'availability' (string) accept '' (empty string - means all), 'enabled', 'disabled'. Default is empty string.
     * @return array Return array of plugins with associative array in details.
     */
    public function listPlugins(array $options = []): array
    {
        $availability = ($options['availability'] ?? '');
        // validate availability value.
        if ($availability !== 'enabled' && $availability !== 'disabled') {
            $availability = '';
        }

        $plugins = [];// list of fetched plugins.

        if ($this->Container->has('Modules')) {
            /* @var $Modules \Rdb\System\Modules */
            $Modules = $this->Container->get('Modules');
        } else {
            $Modules = new \Rdb\System\Modules($this->Container);
        }

        $enabledModules = $Modules->getModules();
        unset($Modules);

        if (is_array($enabledModules)) {
            $FileSystem = new \Rdb\System\Libraries\FileSystem(MODULE_PATH);

            foreach ($enabledModules as $moduleSystemName) {
                // loop each module
                if ($FileSystem->isDir($moduleSystemName . '/Plugins')) {
                    $modulePlugins = $FileSystem->listFiles($moduleSystemName . '/Plugins', 'folders');

                    if (is_array($modulePlugins)) {
                        foreach ($modulePlugins as $modulePlugin) {
                            // loop each plugin inside this module.
                            // $modulePlugin is already included $moduleSystemName . '/Plugins/'
                            $expModulePlugin = explode('/', str_replace(['\\', '/'], '/', $modulePlugin));
                            $modulePluginSystemName = $expModulePlugin[(count($expModulePlugin) - 1)];
                            unset($expModulePlugin);

                            if (
                                (
                                    $availability === '' || 
                                    ($availability === 'enabled' && !$FileSystem->isFile($modulePlugin . '/.disabled')) ||
                                    ($availability === 'disabled' && $FileSystem->isFile($modulePlugin . '/.disabled'))
                                ) && 
                                $FileSystem->isFile($modulePlugin . '/' . $modulePluginSystemName . '.php')
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
                        }// endforeach;
                        unset($modulePlugin);
                    }

                    unset($modulePlugins);
                }
            }// endforeach;
            unset($FileSystem, $moduleSystemName);
        }
        unset($availability, $enabledModules);

        return $plugins;
    }// listPlugins


}
