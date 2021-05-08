<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


/**
 * Extended System modules to have only this module (RdbAdmin) or the module name contains (ModuleForTest) just for tests.
 */
class PluginsModulesExtended extends \Rdb\System\Modules
{


    /**
     * Register auto load for modules that is not disabled.
     * 
     * This class was called at very first from `\Rdb\System\App` class. So, it has nothing like `$Profiler` to access.
     */
    public function registerAutoload()
    {
        $It = new \FilesystemIterator(MODULE_PATH);
        // use autoload from composer as we already use composer. see https://getcomposer.org/doc/01-basic-usage.md#autoloading for reference.
        $Loader = require ROOT_PATH . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        foreach ($It as $FileInfo) {
            if ($FileInfo->isDir()) {
                if (
                    !is_file($FileInfo->getRealPath() . DIRECTORY_SEPARATOR . '.disabled') &&
                    (
                        $FileInfo->getFilename() === 'RdbAdmin' || // if module name is RdbAdmin OR
                        stripos($FileInfo->getFilename(), 'ModuleForTest') !== false // module name contains `ModuleForTest`
                    )
                ) {
                    // if there is no .disabled file in this module then it is enabled, register auto load for it.
                    $this->modules[] = $FileInfo->getFilename();
                    $Loader->addPsr4('Rdb\\Modules\\' . $FileInfo->getFilename() . '\\', $FileInfo->getRealPath());
                }
            }
        }// endforeach;
        unset($FileInfo, $It, $Loader);
    }// registerAutoload


}
