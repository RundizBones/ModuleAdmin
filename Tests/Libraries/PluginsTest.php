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


    /**
     * @var Rdb\Modules\RdbAdmin\Tests\Libraries\PluginsExtended
     */
    protected $Plugins;


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


class %PLUGIN% implements \Rdb\Modules\RdbAdmin\Interfaces\Plugins
{


    /**
     * @var \Rdb\System\Container 
     */
    protected \$Container;



    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container \$Container)
    {
        \$this->Container = \$Container;
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function registerHooks()
    {
        /* @var \$Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
        \$Plugins = \$this->Container['Plugins'];
        \$%PLUGIN%Plug = new %PLUGIN%Plug(\$this->Container);

        \$Plugins->addAction('rdbatest.demoaction1', [\$%PLUGIN%Plug, 'demoAction1'], 10);
        \$Plugins->addAction('rdbatest.demoaction1', [\$%PLUGIN%Plug, 'demoAction1p1'], 11);
        \$Plugins->addAction('rdbatest.demoaction1', [\$%PLUGIN%Plug, 'demoAction2'], 11);
        \$Plugins->addAction('rdbatest.demoaction2', [\$%PLUGIN%Plug, 'demoAction2'], 10);

        \$Plugins->addFilter('rdbatest.demofilter1', [\$%PLUGIN%Plug, 'demoFilter1'], 10);
        \$Plugins->addFilter('rdbatest.demofilter1', [\$%PLUGIN%Plug, 'demoFilter1p1'], 11);
        \$Plugins->addFilter('rdbatest.demofilter1', [\$%PLUGIN%Plug, 'demoFilter1p2'], 11);
        \$Plugins->addFilter('rdbatest.demofilter1', [\$%PLUGIN%Plug, 'demoFilter2'], 12);
        \$Plugins->addFilter('rdbatest.demofilter2', [\$%PLUGIN%Plug, 'demoFilter2'], 10);
    }// registerHooks


}
EOT;

        $pluginHookContents = <<< EOT
<?php


namespace Rdb\Modules\%MODULE%\Plugins\%PLUGIN%;


class %PLUGIN%Plug
{


    /**
     * @var \Rdb\System\Container 
     */
    protected \$Container;



    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container \$Container)
    {
        \$this->Container = \$Container;
    }// __construct


    public function demoAction1(\$name, \$email, \$website = '')
    {
        file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . __FUNCTION__ . '.txt', 'name: ' . \$name . ', email: ' . \$email . ', website: ' . \$website . PHP_EOL, FILE_APPEND);
    }


    public function demoAction1p1(\$name, \$email, \$website = '')
    {
        file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . __FUNCTION__ . '.txt', 'name: ' . \$name . ', email: ' . \$email . ', website: ' . \$website . PHP_EOL, FILE_APPEND);
    }


    public function demoAction2(\$name, \$email, \$website = '')
    {
        file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . __FUNCTION__ . '.txt', 'name: ' . \$name . ', email: ' . \$email . ', website: ' . \$website . PHP_EOL, FILE_APPEND);
    }


    public function demoFilter1(\$name, \$email, \$website = '')
    {
        return \$name . ':filtered:' . __FUNCTION__ . ':' . \$email . \$website;
    }


    public function demoFilter1p1(\$name, \$email, \$website = '')
    {
        return \$name . ':filtered:' . __FUNCTION__ . ':' . \$email . \$website;
    }


    public function demoFilter1p2(\$name, \$email, \$website = '')
    {
        return \$name . ':filtered:' . __FUNCTION__ . ':' . \$email . \$website;
    }


    public function demoFilter2(\$name, \$email, \$website = '')
    {
        return \$name . ':filtered:' . __FUNCTION__ . ':' . \$email . \$website;
    }


}
EOT;

        $this->FileSystem = new \Rdb\System\Libraries\FileSystem(MODULE_PATH);
        $this->FileSystem->createFolder($this->newModule);
        $this->FileSystem->createFile($this->newModule . '/Installer.php', '<?php');
        for ($i = 1; $i <= 2; $i++) {
            $this->FileSystem->createFolder($this->newModule . '/Plugins/Demo' . $i);
            $this->FileSystem->createFile(
                $this->newModule . '/Plugins/Demo' . $i . '/Demo' . $i . '.php', 
                str_replace(['%MODULE%', '%PLUGIN%'], [$this->newModule, 'Demo' . $i], $pluginPhpContents)
            );
            $this->FileSystem->createFile(
                $this->newModule . '/Plugins/Demo' . $i . '/Demo' . $i . 'Plug.php',
                str_replace(['%MODULE%', '%PLUGIN%'], [$this->newModule, 'Demo' . $i], $pluginHookContents)
            );
        }
        $this->FileSystem->createFile($this->newModule . '/Plugins/Demo2/.disabled', '');// add disabled to plugin 2
        $this->FileSystem->createFolder($this->newModule . '/Plugins/Demo3');// this plugin will not listed.

        $this->runApp('GET', '/');
        $this->Container = $this->RdbApp->getContainer();

        $Modules = new \Rdb\System\Modules($this->Container);
        $Modules->registerAutoload();// make getModules() work!
        $this->Container['Modules'] = function ($c) use ($Modules) {
            return $Modules;
        };
        unset($Modules);

        $this->Plugins = new PluginsExtended($this->Container);
        $Plugins = $this->Plugins;
        if (!$this->Container->has('Plugins')) {
            $this->Container['Plugins'] = function ($c) use ($Plugins) {
                return $Plugins;
            };
        }
        unset($Plugins);
        $this->Plugins->registerAllPluginsHooks();
    }// setup


    public function tearDown()
    {
        $this->FileSystem->deleteFolder($this->newModule, true);
    }// tearDown


    protected function isStringAndNotEmpty($string)
    {
        return is_string($string) && !empty($string);
    }


    public function testAddHook()
    {
        $this->Plugins->registerAllPluginsHooks();

        $callbackActions = $this->Plugins->callbackActions;
        $this->assertTrue(isset($callbackActions['rdbatest.demoaction1']));
        $this->assertTrue(isset($callbackActions['rdbatest.demoaction2']));
        $this->assertGreaterThanOrEqual(4, $callbackActions);
        $this->assertEquals(2, count($callbackActions['rdbatest.demoaction1']));// number of priorities in use.
        $this->assertEquals(1, count($callbackActions['rdbatest.demoaction2']));// number of priorities in use.
        $countHook = 0;
        foreach ($callbackActions['rdbatest.demoaction1'] as $tag => $items) {
            foreach ($items as $priority => $subItems) {
                foreach ($subItems as $idHash => $subItem) {
                    $countHook++;
                }
            }
        }
        $this->assertEquals(3, $countHook);// number of hook functions added.

        $callbackFilters = $this->Plugins->callbackFilters;
        $this->assertTrue(isset($callbackFilters['rdbatest.demofilter1']));
        $this->assertTrue(isset($callbackFilters['rdbatest.demofilter2']));
        $this->assertGreaterThanOrEqual(4, $callbackFilters);
        $this->assertEquals(3, count($callbackFilters['rdbatest.demofilter1']));// number of priorities in use.
        $this->assertEquals(1, count($callbackFilters['rdbatest.demofilter2']));// number of priorities in use.
        $countHook = 0;
        foreach ($callbackFilters['rdbatest.demofilter1'] as $tag => $items) {
            foreach ($items as $priority => $subItems) {
                foreach ($subItems as $idHash => $subItem) {
                    $countHook++;
                }
            }
        }
        $this->assertEquals(4, $countHook);// number of hook functions added.
    }// testAddHook


    public function testGetHookIdHash()
    {
        $this->assertTrue($this->isStringAndNotEmpty($this->Plugins->getHookIdHash('hook.name', 'function', 10)));// callback is tring.
        $this->assertTrue($this->isStringAndNotEmpty($this->Plugins->getHookIdHash('hook.name', function() {}, 10)));// callback is anonymous function.
        $this->assertTrue($this->isStringAndNotEmpty($this->Plugins->getHookIdHash('hook.name', ['Class', 'method'], 10)));// callback is array.
        $this->assertTrue($this->isStringAndNotEmpty($this->Plugins->getHookIdHash('hook.name', [$this, 'tearDown'], 10)));// callback is array with object in first array.
    }// testGetHookIdHash


    public function testListPlugins()
    {
        // test with all plugins (no enabled, disabled filtered).
        $listPlugins = $this->Plugins->listPlugins(['unlimited' => true]);
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
        $listPlugins = $this->Plugins->listPlugins(['unlimited' => true, 'availability' => 'enabled']);
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
        $listPlugins = $this->Plugins->listPlugins(['unlimited' => true, 'availability' => 'disabled']);
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


    public function testRegisterAllPluginsHooks()
    {
        $this->Plugins->registerAllPluginsHooks();
        $pluginsRegisteredHooks = $this->Plugins->pluginsRegisteredHooks;

        $this->assertGreaterThanOrEqual(1, $pluginsRegisteredHooks);

        $foundThisModulePlugin = 0;
        foreach ($pluginsRegisteredHooks as $plugin) {
            if (strpos($plugin, $this->newModule) !== false && strpos($plugin, 'Demo1') !== false) {
                $foundThisModulePlugin++;
            }
        }
        $this->assertSame(1, $foundThisModulePlugin);
    }// testRegisterAllPluginsHooks


}
