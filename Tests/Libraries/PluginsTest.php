<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


use Rdb\Modules\RdbAdmin\Tests\PHPUnitFunctions\Arrays;


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


    public function setup(): void
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
    public function enable()
    {
        
    }// enable


    /**
     * {@inheritDoc}
     */
    public function disable()
    {
        
    }// disable


    /**
     * {@inheritDoc}
     */
    public function registerHooks()
    {
        /* @var \$Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
        \$Plugins = \$this->Container->get('Plugins');
        \$%PLUGIN%PlugInContentSubClass = new %PLUGIN%PlugInContentSubClass(\$this->Container);

        \$Plugins->addHook('rdbatest.demoaction1', [\$%PLUGIN%PlugInContentSubClass, 'demoAction1'], 10);
        \$Plugins->addHook('rdbatest.demoaction1', [\$%PLUGIN%PlugInContentSubClass, 'demoAction1p1'], 11);
        \$Plugins->addHook('rdbatest.demoaction1', [\$%PLUGIN%PlugInContentSubClass, 'demoAction2'], 11);
        \$Plugins->addHook('rdbatest.demoaction2', [\$%PLUGIN%PlugInContentSubClass, 'demoAction2'], 10);

        \$Plugins->addHook('rdbatest.demoactionalterargsbyref1', [\$%PLUGIN%PlugInContentSubClass, 'demoActionAlterArgsByRef1'], 10);
        \$Plugins->addHook('rdbatest.demoactionalterargsbyref1', [\$%PLUGIN%PlugInContentSubClass, 'demoActionAlterArgsByRef1p1'], 11);
        \$Plugins->addHook('rdbatest.demoactionalterargsbyref1', [\$%PLUGIN%PlugInContentSubClass, 'demoActionAlterArgsByRef1p2'], 11);
        \$Plugins->addHook('rdbatest.demoactionalterargsbyref1', [\$%PLUGIN%PlugInContentSubClass, 'demoActionAlterArgsByRef2'], 12);
        \$Plugins->addHook('rdbatest.demoactionalterargsbyref2', [\$%PLUGIN%PlugInContentSubClass, 'demoActionAlterArgsByRef2'], 10);

        \$Plugins->addHook('rdbatest.demoalter1', [\$%PLUGIN%PlugInContentSubClass, 'demoAlter1Before'], 10);
        \$Plugins->addHook('rdbatest.demoalter1', [\$%PLUGIN%PlugInContentSubClass, 'demoAlter1After'], 10);
        \$Plugins->addHook('rdbatest.demoalter2', [\$%PLUGIN%PlugInContentSubClass, 'demoAlter2MultiArgs'], 10);
    }// registerHooks


    /**
     * {@inheritDoc}
     */
    public function uninstall()
    {
        
    }// uninstall


}
EOT;

        $pluginHookContents = <<< EOT
<?php


namespace Rdb\Modules\%MODULE%\Plugins\%PLUGIN%;


class %PLUGIN%PlugInContentSubClass
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
        return 'hook1(' . \$name . ')';
    }


    public function demoAction1p1(\$name, \$email, \$website = '')
    {
        file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . __FUNCTION__ . '.txt', 'name: ' . \$name . ', email: ' . \$email . ', website: ' . \$website . PHP_EOL, FILE_APPEND);
        return 'hook1p1(' . \$name . ')';
    }


    public function demoAction2(\$name, \$email, \$website = '')
    {
        file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . __FUNCTION__ . '.txt', 'name: ' . \$name . ', email: ' . \$email . ', website: ' . \$website . PHP_EOL, FILE_APPEND);
        return 'hook2(' . \$name . ')';
    }


    public function demoActionAlterArgsByRef1(&\$name, \$email, \$website = '')
    {
        \$name = 'Alter1(' . \$name . ')';
    }


    public function demoActionAlterArgsByRef1p1(&\$name, \$email, \$website = '')
    {
        \$name = 'Alter1p1(' . \$name . ')';
    }


    public function demoActionAlterArgsByRef1p2(&\$name, \$email, \$website = '')
    {
        \$name = 'Alter1p2(' . \$name . ')';
    }


    public function demoActionAlterArgsByRef2(&\$name, \$email, \$website = '')
    {
        \$name = 'Alter2(' . \$name . ')';
        return ['name' => \$name, 'email' => \$email];
    }


    public function demoAlter1Before(\$name)
    {
        return 'before::' . \$name;
    }


    public function demoAlter1After(\$name)
    {
        return \$name . '::after';
    }


    public function demoAlter2MultiArgs(\$name, \$lastname = '', array \$address = [], array \$phones = [])
    {
        if (empty(\$lastname) || empty(\$address) || empty(\$phones)) {
            throw new \Exception('The other arguments are empty.');// help unit test to verify that these arguments must be specified.
        }

        if (count(\$address) < 3) {
            throw new \Exception('The address argument must have 3 or more values.');
        }

        if (count(\$phones) < 2) {
            throw new \Exception('The phones argument must have 2 or more values.');
        }

        if (!array_key_exists('addr1', \$address)) {
            throw new \Exception('The address argument must have addr1 key in it.');
        }

        return \$name . ' Connor';
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
                $this->newModule . '/Plugins/Demo' . $i . '/Demo' . $i . 'PlugInContentSubClass.php',
                str_replace(['%MODULE%', '%PLUGIN%'], [$this->newModule, 'Demo' . $i], $pluginHookContents)
            );
        }
        $this->FileSystem->createFile($this->newModule . '/Plugins/Demo2/.disabled', '');// add disabled to plugin 2
        $this->FileSystem->createFolder($this->newModule . '/Plugins/Demo3');// this plugin will not listed.

        $this->runApp('GET', '/');
        $this->Container = $this->RdbApp->getContainer();

        $Modules = new PluginsModulesExtended($this->Container);
        $Modules->registerAutoload();// make getModules() work!
        $this->Container['Modules'] = function ($c) use ($Modules) {
            return $Modules;
        };
        unset($Modules);

        if (!$this->Container->has('Plugins')) {
            $this->Container['Plugins'] = function ($c) {
                return new PluginsExtended($this->Container);
            };
        }
        $this->Plugins = $this->Container->get('Plugins');
        $this->Plugins->registerAllPluginsHooks();
    }// setup


    public function tearDown(): void
    {
        $this->FileSystem->deleteFolder($this->newModule, true);
    }// tearDown


    protected function isStringAndNotEmpty($string)
    {
        return is_string($string) && !empty($string);
    }


    public function testAddHook()
    {
        $callbackHooks = $this->Plugins->callbackHooks;
        $this->assertTrue(isset($callbackHooks['rdbatest.demoaction1']));
        $this->assertTrue(isset($callbackHooks['rdbatest.demoaction2']));
        $this->assertGreaterThanOrEqual(4, $callbackHooks);
        $this->assertEquals(2, count($callbackHooks['rdbatest.demoaction1']));// number of priorities in use.
        $this->assertEquals(1, count($callbackHooks['rdbatest.demoaction2']));// number of priorities in use.
        $countHook = 0;
        foreach ($callbackHooks['rdbatest.demoaction1'] as $priority => $items) {
            foreach ($items as $idHash => $subItems) {
                $countHook++;
            }
        }
        $this->assertEquals(3, $countHook);// number of hook functions added.

        $this->assertTrue(isset($callbackHooks['rdbatest.demoactionalterargsbyref1']));
        $this->assertTrue(isset($callbackHooks['rdbatest.demoactionalterargsbyref2']));
        $this->assertGreaterThanOrEqual(4, $callbackHooks);
        $this->assertEquals(3, count($callbackHooks['rdbatest.demoactionalterargsbyref1']));// number of priorities in use.
        $this->assertEquals(1, count($callbackHooks['rdbatest.demoactionalterargsbyref2']));// number of priorities in use.
        $countHook = 0;
        foreach ($callbackHooks['rdbatest.demoactionalterargsbyref1'] as $priority => $items) {
            foreach ($items as $idHash => $subItems) {
                $countHook++;
            }
        }
        $this->assertEquals(4, $countHook);// number of hook functions added.
    }// testAddHook


    public function testDoAlter()
    {
        $name = 'Adam';
        $name = $this->Plugins->doAlter('rdbatest.demoalter1', $name);// 1 argument
        $this->assertStringStartsWith('before::', $name);
        $this->assertStringEndsWith('::after', $name);

        $name = 'John';
        $lastname = 'Doe';
        $address = [
            'addr1' => '123 village, road, district',
            'province' => 'Bangkok',
            'country' => 'Thailand',
        ];
        $phones = [
            '021234567',
            '027654321',
        ];
        $this->assertSame('John Connor', $this->Plugins->doAlter('rdbatest.demoalter2', $name, $lastname, $address, $phones));
        $this->assertSame('Doe', $lastname);// must not change.
    }// testDoAlter


    public function testDoHook()
    {
        $name = 'Adam';
        $email = 'adam@domain.tld';

        $result = $this->Plugins->doHook('rdbatest.demoaction1', [$name, $email]);
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(['hook1(Adam)', 'hook1p1(Adam)', 'hook2(Adam)'], $result))
        );

        $result = $this->Plugins->doHook('rdbatest.demoactionalterargsbyref1', [&$name, $email]);
        $this->assertSame('Alter2(Alter1p2(Alter1p1(Alter1(Adam))))', $name);
    }// testDoHook


    public function testGetHookIdHash()
    {
        $this->Plugins->callbackHooks = [];
        $this->Plugins->pluginsRegisteredHooks = [];

        $this->assertTrue($this->isStringAndNotEmpty($this->Plugins->getHookIdHash('hook.name', 'function')));// callback is tring.
        $this->assertTrue($this->isStringAndNotEmpty($this->Plugins->getHookIdHash('hook.name', function() {})));// callback is anonymous function.
        $this->assertTrue($this->isStringAndNotEmpty($this->Plugins->getHookIdHash('hook.name', ['Class', 'method'])));// callback is array.
        $this->assertTrue($this->isStringAndNotEmpty($this->Plugins->getHookIdHash('hook.name', [$this, 'tearDown'])));// callback is array with object in first array.
    }// testGetHookIdHash


    public function testHasHook()
    {
        $this->assertFalse($this->Plugins->hasHook('hook.name'));// return false because did not add any hook.
        $this->Plugins->addHook('hook.name', 'function');
        $this->assertTrue($this->Plugins->hasHook('hook.name'));// now return true.
        $this->assertFalse($this->Plugins->hasHook('hook.name', 'function2'));// return false because specific function was not found.
        $this->assertEquals(10, $this->Plugins->hasHook('hook.name', 'function'));// return number because specific function was found.

        // test hasHook with callback as class (array).
        $this->Plugins->addHook('hook.name2', ['Class', 'method'], 12);
        $this->assertTrue($this->Plugins->hasHook('hook.name2'));
        $this->assertEquals(12, $this->Plugins->hasHook('hook.name2', ['Class', 'method']));

        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoaction1'));
        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoactionalterargsbyref1'));
        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoalter1'));
        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoalter2'));
    }// testHasHook


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


    public function testRemoveAllHooks()
    {
        $pluginClassName = '\Rdb\Modules\\' . $this->newModule . '\Plugins\Demo1\Demo1PlugInContentSubClass';
        $Demo1Plug = new $pluginClassName($this->Container);

        // check that has hook.
        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoaction1'));
        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoaction2'));
        $this->assertEquals(10, $this->Plugins->hasHook('rdbatest.demoaction1', [$Demo1Plug, 'demoAction1']));

        $this->Plugins->removeAllHooks('rdbatest.demoaction1', 10);// remove all hook based on priority 10 name 'rdbatest.demoaction1'.

        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoaction1'));// there are hooks left.
        $this->assertFalse($this->Plugins->hasHook('rdbatest.demoaction1', [$Demo1Plug, 'demoAction1']));// removed
        $this->assertEquals(11, $this->Plugins->hasHook('rdbatest.demoaction1', [$Demo1Plug, 'demoAction1p1']));

        // check that has another hooks.
        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoactionalterargsbyref1'));
        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoactionalterargsbyref2'));

        $this->Plugins->removeAllHooks('rdbatest.demoactionalterargsbyref1', false);// remove all filters without any priority care.

        $this->assertFalse($this->Plugins->hasHook('rdbatest.demoactionalterargsbyref1'));// there are no filters left on this hook name.
        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoactionalterargsbyref2'));
    }// testRemoveAllHooks


    public function testRemoveHook()
    {
        $pluginClassName = '\Rdb\Modules\\' . $this->newModule . '\Plugins\Demo1\Demo1PlugInContentSubClass';
        $Demo1Plug = new $pluginClassName($this->Container);

        // check that has hook.
        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoaction1'));
        $this->assertTrue($this->Plugins->hasHook('rdbatest.demoaction2'));
        $this->assertEquals(11, $this->Plugins->hasHook('rdbatest.demoaction1', [$Demo1Plug, 'demoAction1p1']));
        $this->assertEquals(10, $this->Plugins->hasHook('rdbatest.demoaction2', [$Demo1Plug, 'demoAction2']));

        // remove hooks.
        $this->assertTrue($this->Plugins->removeHook('rdbatest.demoaction1', [$Demo1Plug, 'demoAction1p1'],11));
        $this->assertTrue($this->Plugins->removeHook('rdbatest.demoaction2', [$Demo1Plug, 'demoAction2'],10));

        // check again that has hook.
        $this->assertFalse($this->Plugins->hasHook('rdbatest.demoaction1', [$Demo1Plug, 'demoAction1p1']));
        $this->assertFalse($this->Plugins->hasHook('rdbatest.demoaction2', [$Demo1Plug, 'demoAction2']));

        // check that hook still exists in property or not.
        $this->assertTrue(isset($this->Plugins->callbackHooks['rdbatest.demoaction1']));// 'rdbatest.demoaction1' has more than 1 function but removed only one, so it is still there.
        $this->assertFalse(isset($this->Plugins->callbackHooks['rdbatest.demoaction2']));// 'rdbatest.demoaction2' has only 1 function call, removed then it is not exists anymore.
    }// testRemoveHook


}
