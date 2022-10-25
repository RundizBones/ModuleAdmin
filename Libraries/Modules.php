<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Modules class.
 * 
 * @since 1.2.5
 */
class Modules
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
     * List modules with their data.
     * 
     * @param array $options Available options:<br>
     *              `availability` (string) accept '' (empty string - means all), 'enabled', 'disabled'. Default is empty string.,<br>
     * @return array Return associative array with `total` and `items` in keys.
     */
    public function listModules(array $options = []): array
    {
        $availability = ($options['availability'] ?? '');
        // validate availability value.
        if ($availability !== 'enabled' && $availability !== 'disabled') {
            $availability = '';
        }

        $output = [];
        $modules = [];// for store listed module system name and the module's metadata.

        // get module system names. -----------------
        $FI = new \FilesystemIterator(MODULE_PATH);
        $allModules = [];// for store listed module system names from file listing (folders).
        foreach ($FI as $FileInfo) {
            if ($FileInfo->isDir()) {
                $fullPath = $FileInfo->getPathname();
                if ($availability === 'enabled' && !is_file($fullPath . DIRECTORY_SEPARATOR . '.disabled')) {
                    $allModules[] = $FileInfo->getFilename();
                } elseif ($availability === 'disabled' && is_file($fullPath . DIRECTORY_SEPARATOR . '.disabled')) {
                    $allModules[] = $FileInfo->getFilename();
                } elseif ($availability === '') {
                    $allModules[] = $FileInfo->getFilename();
                }
                unset($fullPath);
            }
        }// endforeach;
        unset($FI, $FileInfo);

        if (empty($allModules)) {
            return [
                'total' => 0,
                'items' => [],
            ];
        }

        sort($allModules, SORT_NATURAL);
        // end get module system names. ------------

        // get module's metadata. -------------------------
        $FileSystem = new \Rdb\System\Libraries\FileSystem(MODULE_PATH);
        foreach ($allModules as $moduleSystemName) {
            if ($FileSystem->isFile($moduleSystemName . '/Installer.php', true)) {
                $fileContents = file_get_contents($FileSystem->getFullPathWithRoot($moduleSystemName . '/Installer.php'));
                preg_match ('|Module Name:(.*)$|mi', $fileContents, $moduleName);
                preg_match ('|Description:(.*)$|mi', $fileContents, $Description);
                preg_match ('|Requires PHP:(.*)$|mi', $fileContents, $requiresPhp);
                preg_match ('|Requires Modules:(.*)$|mi', $fileContents, $requiresModules);
                preg_match ('|Author:(.*)$|mi', $fileContents, $author);
                preg_match ('|Gettext Domain:(.*)$|mi', $fileContents, $gettextDomain);
                preg_match ('|@version(\s*)(?<version>.*)$|mi', $fileContents, $version);
                preg_match ('|@license(\s*)(?<license>.*)$|mi', $fileContents, $license);
            }

            $modules[] = [
                'id' => str_replace(['\\', '/', DIRECTORY_SEPARATOR, ':'], '', $FileSystem->getFullPathWithRoot($moduleSystemName)),
                'module_system_name' => $moduleSystemName,
                'module_location' => realpath($FileSystem->getFullPathWithRoot($moduleSystemName)),
                'module_enabled' => ($FileSystem->isFile($moduleSystemName . '/.disabled', false) ? false : true),
                'module_name' => (isset($moduleName[1]) ? trim($moduleName[1]) : $moduleSystemName),
                'module_description' => (isset($Description[1]) ? trim($Description[1]) : ''),
                'module_requires_php' => (isset($requiresPhp[1]) ? trim($requiresPhp[1]) : ''),
                'module_requires_modules' => (isset($requiresModules[1]) ? explode(',', $requiresModules[1]) : []),
                'module_author' => (isset($author[1]) ? trim($author[1]) : ''),
                'module_gettext_domain' => (isset($gettextDomain[1]) ? trim($gettextDomain[1]) : ''),
                'module_version' => (isset($version['version']) ? trim($version['version']) : ''),
                'module_license' => (isset($license['license']) ? trim($license['license']) : ''),
            ];

            unset(
                $moduleName,
                $Description,
                $requiresPhp,
                $requiresModules,
                $author,
                $gettextDomain,
                $version,
                $license
            );
        }// endforeach;
        unset($allModules, $FileSystem, $moduleSystemName);
        // end get module's metadata. --------------------

        $output['total'] = count($modules);
        $output['items'] = $modules;
        unset($modules);

        return $output;
    }// listModules


}
