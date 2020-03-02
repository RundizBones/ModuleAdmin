<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


class PluginsTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\System\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * @var string New module name for create and tests.
     */
    protected $newModule = '';


    public function setup()
    {
        $this->newModule = 'ModuleForTest' . date('YmdHis') . mt_rand(1, 999) . 'M' . round(microtime(true) * 1000);

        $pluginPhpContents = <<< EOT
<?php
/**
 * Name: Test Demo plugin
 * Description: A plugin for unit test, create by unit test.
 * Version: 0.0.1-test
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\%MODULE%\Plugins\%PLUGIN%;


class %PLUGIN%
{
}
EOT;

        $this->FileSystem = new \Rdb\System\Libraries\FileSystem(MODULE_PATH);
        $this->FileSystem->createFolder($this->newModule);
        $this->FileSystem->createFile($this->newModule . '/Installer.php', '<?php');
        $this->FileSystem->createFolder($this->newModule . '/Plugins/Demo1');
        $this->FileSystem->createFile(
            $this->newModule . '/Plugins/Demo1/Demo1.php', 
            str_replace(['%MODULE%', '%PLUGIN%'], [$this->newModule, 'Demo1'], $pluginPhpContents)
        );
        $this->FileSystem->createFolder($this->newModule . '/Plugins/Demo2');
        $this->FileSystem->createFile(
            $this->newModule . '/Plugins/Demo2/Demo2.php', 
            str_replace(['%MODULE%', '%PLUGIN%'], [$this->newModule, 'Demo2'], $pluginPhpContents)
        );
        $this->FileSystem->createFile($this->newModule . '/Plugins/Demo2/.disabled', '');
        $this->FileSystem->createFolder($this->newModule . '/Plugins/Demo3');// this plugin will not listed.

        $this->runApp('GET', '/');
    }// setup


    public function tearDown()
    {
        $this->FileSystem->deleteFolder($this->newModule, true);
    }// tearDown


    public function testListPlugins()
    {
        $this->Container = $this->RdbApp->getContainer();

        $Modules = new \Rdb\System\Modules($this->Container);
        $Modules->registerAutoload();// make getModules() work!
        $this->Container['Modules'] = function ($c) use ($Modules) {
            return $Modules;
        };
        unset($Modules);

        $Plugins = new \Rdb\Modules\RdbAdmin\Libraries\Plugins($this->Container);

        // test with all plugins (no enabled, disabled filtered).
        $listPlugins = $Plugins->listPlugins(['unlimited' => true]);
        $listPlugins = ($listPlugins['items'] ?? []);
        $this->assertGreaterThanOrEqual(1, $listPlugins);
        $countEnabled = 0;
        $countDisabled = 0;
        if (is_array($listPlugins)) {
            foreach ($listPlugins as $plugin) {
                if (isset($plugin['module_system_name']) && $plugin['module_system_name'] == $this->newModule) {
                    if (isset($plugin['enabled'])) {
                        if ($plugin['enabled'] === true) {
                            $countEnabled++;
                        } elseif ($plugin['enabled'] === false) {
                            $countDisabled++;
                        }
                    }
                }
            }
            unset($plugin);
        }
        unset($listPlugins);
        $this->assertSame(1, $countDisabled);
        $this->assertSame(1, $countEnabled);

        // test with all enabled filter.
        $listPlugins = $Plugins->listPlugins(['unlimited' => true, 'availability' => 'enabled']);
        $listPlugins = ($listPlugins['items'] ?? []);
        $countEnabled = 0;
        $countDisabled = 0;
        if (is_array($listPlugins)) {
            foreach ($listPlugins as $plugin) {
                if (isset($plugin['module_system_name']) && $plugin['module_system_name'] == $this->newModule) {
                    if (isset($plugin['enabled'])) {
                        if ($plugin['enabled'] === true) {
                            $countEnabled++;
                        } elseif ($plugin['enabled'] === false) {
                            $countDisabled++;
                        }
                    }
                }
            }
            unset($plugin);
        }
        unset($listPlugins);
        $this->assertSame(0, $countDisabled);
        $this->assertSame(1, $countEnabled);

        // test with all disabled filter.
        $listPlugins = $Plugins->listPlugins(['unlimited' => true, 'availability' => 'disabled']);
        $listPlugins = ($listPlugins['items'] ?? []);
        $countEnabled = 0;
        $countDisabled = 0;
        if (is_array($listPlugins)) {
            foreach ($listPlugins as $plugin) {
                if (isset($plugin['module_system_name']) && $plugin['module_system_name'] == $this->newModule) {
                    if (isset($plugin['enabled'])) {
                        if ($plugin['enabled'] === true) {
                            $countEnabled++;
                        } elseif ($plugin['enabled'] === false) {
                            $countDisabled++;
                        }
                    }
                }
            }
            unset($plugin);
        }
        unset($listPlugins);
        $this->assertSame(1, $countDisabled);
        $this->assertSame(0, $countEnabled);
    }// testListPlugins


}
