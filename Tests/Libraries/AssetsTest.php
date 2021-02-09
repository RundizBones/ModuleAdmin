<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


use Rdb\Modules\RdbAdmin\Tests\PHPUnitFunctions\Arrays;


class AssetsTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\Modules\RdbAdmin\Tests\Libraries\AssetsExtended 
     */
    protected $Assets;


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var \Rdb\System\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * @var string Asset folder name for test.
     */
    protected $testAssetFolderName;


    /**
     * @var string Full path to assets folder for test.
     */
    protected $testAssetFolderPath;


    /**
     * @var string The URL to test asset folder.
     */
    protected $testAssetUrl;


    /**
     * @var string Full path to this module.
     */
    protected $thisModulePath;


    public function setup(): void
    {
        $this->runApp('GET', '/');

        $this->testAssetFolderName = 'test' . time() . mt_rand(1, 999) . round(microtime(true) * 1000);
        $this->testAssetFolderPath =  PUBLIC_PATH . '/Modules/RdbAdmin/assets/' . $this->testAssetFolderName;

        if (!is_dir($this->testAssetFolderPath)) {
            $umask = umask(0);
            $output = mkdir($this->testAssetFolderPath, 0755, true);
            umask($umask);
        }

        $this->FileSystem = new \Rdb\System\Libraries\FileSystem(PUBLIC_PATH);
        $this->FileSystem->createFolder('Modules/RdbAdmin/assets/' . $this->testAssetFolderName);

        $this->FileSystem = new \Rdb\System\Libraries\FileSystem($this->testAssetFolderPath);
        $this->FileSystem->createFile('jquery.js', 'test only');
        $this->FileSystem->createFile('bootstrap.js', 'test only');
        $this->FileSystem->createFile('bootstrap.css', 'test only');
        $this->FileSystem->createFile('bootstrap-theme.css', 'test only');

        $this->Container = new \Rdb\System\Container();
        $Modules = new \Rdb\System\Modules($this->Container);
        $this->Container['Modules'] = function ($c) use ($Modules) {
            return $Modules;
        };
        unset($Modules);
        $this->Container['Logger'] = function ($c) {
            return new \Rdb\System\Libraries\Logger($c);
        };

        $this->Assets = new AssetsExtended($this->Container);
    }// setup


    public function tearDown(): void
    {
        $this->FileSystem->deleteFolder('', true);
        @rmdir($this->testAssetFolderPath);
    }// tearDown


    public function testAddAsset()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        $this->Assets->addAsset('js', 'jquery', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js', [], '3.x.x', [], 'theJsGroup');
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                ['jquery' => [
                        'handle' => 'jquery',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js',
                        'dependency' => [],
                        'version' => '3.x.x',
                        'attributes' => [],
                        'group' => 'theJsGroup',
                        'inline' => null,
                        'printed' => false,
                    ],
                ],
                $addedAssets['js']
            ))
        );
        $this->assertCount(1, $addedAssets['js']);

        $this->Assets->addAsset('js', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.js', ['jquery'], '4.x.x', [], 'theJsGroup');
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                [
                    'jquery' => [
                        'handle' => 'jquery',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js',
                        'dependency' => [],
                        'version' => '3.x.x',
                        'attributes' => [],
                        'group' => 'theJsGroup',
                        'inline' => null,
                        'printed' => false,
                    ],
                    'bootstrap' => [
                        'handle' => 'bootstrap',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.js',
                        'dependency' => ['jquery'],
                        'version' => '4.x.x',
                        'attributes' => [],
                        'group' => 'theJsGroup',
                        'inline' => null,
                        'printed' => false,
                    ],
                ],
                $addedAssets['js']
            ))
        );
        $this->assertCount(2, $addedAssets['js']);

        $this->Assets->addAsset('css', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css', [], '4.x.x', [], 'theCssGroup');
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                [
                    'bootstrap' => [
                        'handle' => 'bootstrap',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css',
                        'dependency' => [],
                        'version' => '4.x.x',
                        'attributes' => [],
                        'group' => 'theCssGroup',
                        'inline' => null,
                        'printed' => false,
                    ],
                ],
                $addedAssets['css']
            ))
        );
        $this->assertCount(1, $addedAssets['css']);

        $this->Assets->addAsset('css', 'bootstrap-theme', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap-theme.css', ['bootstrap'], '1.2.x', [], 'theCssGroup');
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                [
                    'bootstrap' => [
                        'handle' => 'bootstrap',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css',
                        'dependency' => [],
                        'version' => '4.x.x',
                        'attributes' => [],
                        'group' => 'theCssGroup',
                        'inline' => null,
                        'printed' => false,
                    ],
                    'bootstrap-theme' => [
                        'handle' => 'bootstrap-theme',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap-theme.css',
                        'dependency' => ['bootstrap'],
                        'version' => '1.2.x',
                        'attributes' => [],
                        'group' => 'theCssGroup',
                        'inline' => null,
                        'printed' => false,
                    ],
                ],
                $addedAssets['css']
            ))
        );
        $this->assertCount(2, $addedAssets['css']);
    }// testAddAsset


    public function testAddCssInline()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        $this->Assets->addAsset('css', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css');
        $this->Assets->addCssInline('bootstrap', 'body {background-color: white;}');

        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                [
                    'bootstrap' => [
                        'handle' => 'bootstrap',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css',
                        'dependency' => [],
                        'version' => true,
                        'attributes' => [],
                        'group' => null,
                        'inline' => 'body {background-color: white;}',
                        'printed' => false,
                    ],
                ],
                $addedAssets['css']
            ))
        );
        $this->assertCount(1, $addedAssets['css']);
    }// testAddCssInline


    public function testAddJsInline()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        $this->Assets->addAsset('js', 'jquery', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js');
        $this->Assets->addJsInline('jquery', 'function thisIsjustTest() {}');
        $this->Assets->addJsInline('jquery', 'function thisIsjustTestJsInlineBefore() {}', 'before');

        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                [
                    'jquery' => [
                        'handle' => 'jquery',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js',
                        'dependency' => [],
                        'version' => true,
                        'attributes' => [],
                        'group' => null,
                        'inline' => [
                            'after' => 'function thisIsjustTest() {}',
                            'before' => 'function thisIsjustTestJsInlineBefore() {}',
                        ],
                        'printed' => false,
                    ],
                ],
                $addedAssets['js']
            ))
        );
        $this->assertCount(1, $addedAssets['js']);
    }// testAddJsInline


    public function testAddJsObject()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        $this->Assets->addAsset('js', 'jquery', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js');
        $this->Assets->addJsObject('jquery', 'myJqueryObject', ['name' => 'TestJQueryObj', 'version' => '3.x.x']);

        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                [
                    'jquery' => [
                        'handle' => 'jquery',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js',
                        'dependency' => [],
                        'version' => true,
                        'attributes' => [],
                        'group' => null,
                        'inline' => null,
                        'jsobject' => [
                            'myJqueryObject' => [
                                'name' => 'TestJQueryObj', 
                                'version' => '3.x.x',
                            ],
                        ],
                        'printed' => false,
                    ],
                ],
                $addedAssets['js']
            ))
        );
        $this->assertCount(1, $addedAssets['js']);
    }// testAddJsObject


    public function testAddMultipleAssets()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        $assetsData = [
            'css' => [
                [
                    'handle' => 'bootstrap',
                    'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css',
                    'dependency' => [],
                    'version' => '4.x.x',
                    'attributes' => ['data-assetname' => 'bootstrap4', 'data-assetversion' => '4.x.x', 'data-addby' => 'multiple'],
                    'group' => 'CssGroup',
                ],
                [
                    'handle' => 'bootstrap-theme',
                    'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap-theme.css',
                    'dependency' => ['bootstrap'],
                ],
            ],
            'js' => [
                [
                    'handle' => 'jquery',
                    'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js',
                    'version' => '3.x.x',
                    'group' => 'JsGroup',
                ],
                [
                    'handle' => 'bootstrap',
                    'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.js',
                    'dependency' => ['jquery'],
                    'version' => '4.x.x',
                    'attributes' => ['data-addby' => 'multiple'],
                    'group' => 'JsGroup',
                ],
            ],
        ];

        $this->Assets->addMultipleAssets('js', ['bootstrap', 'jquery'], $assetsData);
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                [
                    'jquery' => [
                        'handle' => 'jquery',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js',
                        'dependency' => [],
                        'version' => '3.x.x',
                        'attributes' => [],
                        'group' => 'JsGroup',
                        'inline' => null,
                        'printed' => false,
                    ],
                    'bootstrap' => [
                        'handle' => 'bootstrap',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.js',
                        'dependency' => ['jquery'],
                        'version' => '4.x.x',
                        'attributes' => [],
                        'group' => 'JsGroup',
                        'inline' => null,
                        'printed' => false,
                    ],
                ],
                $addedAssets['js']
            ))
        );
        $this->assertCount(2, $addedAssets['js']);
        $this->assertCount(0, $addedAssets['css']);

        $this->Assets->addMultipleAssets('css', ['bootstrap-theme', 'bootstrap'], $assetsData);
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                [
                    'bootstrap' => [
                        'handle' => 'bootstrap',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css',
                        'dependency' => [],
                        'version' => '4.x.x',
                        'attributes' => ['data-assetname' => 'bootstrap4', 'data-assetversion' => '4.x.x', 'data-addby' => 'multiple'],
                        'group' => 'CssGroup',
                        'inline' => null,
                        'printed' => false,
                    ],
                    'bootstrap-theme' => [
                        'handle' => 'bootstrap-theme',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap-theme.css',
                        'dependency' => ['bootstrap'],
                        'version' => true,
                        'attributes' => [],
                        'group' => null,
                        'inline' => null,
                        'printed' => false,
                    ],
                ],
                $addedAssets['css']
            ))
        );
        $this->assertCount(2, $addedAssets['css']);

        // test for automatically add missed dependency.
        $this->Assets->addMultipleAssets('css', ['bootstrap'], $assetsData);// missed bootstrap-theme dependency.
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                [
                    'bootstrap' => [
                        'handle' => 'bootstrap',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css',
                        'dependency' => [],
                        'version' => '4.x.x',
                        'attributes' => [],
                        'group' => 'CssGroup',
                        'inline' => null,
                        'printed' => false,
                    ],
                    'bootstrap-theme' => [
                        'handle' => 'bootstrap-theme',
                        'file' => $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap-theme.css',
                        'dependency' => ['bootstrap'],
                        'version' => true,
                        'attributes' => [],
                        'group' => null,
                        'inline' => null,
                        'printed' => false,
                    ],
                ],
                $addedAssets['css']
            ))
        );// assert same as above test
        $this->assertCount(2, $addedAssets['css']);// assert same as above test
    }// testAddMultipleAssets


    public function testAssetIs()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        $this->Assets->addAsset('js', 'jquery', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js', [], '3.x.x', [], 'theJsGroup');
        $this->assertTrue($this->Assets->assetIs('js', 'jquery'));
        $this->assertFalse($this->Assets->assetIs('js', 'bootstrap'));
        $this->assertFalse($this->Assets->assetIs('css', 'bootstrap'));

        $this->Assets->addAsset('css', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css', [], '4.x.x', [], 'theCssGroup');
        $this->assertTrue($this->Assets->assetIs('js', 'jquery'));
        $this->assertFalse($this->Assets->assetIs('js', 'bootstrap'));
        $this->assertTrue($this->Assets->assetIs('css', 'bootstrap'));
    }// testAssetIs


    public function testClear()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertCount(0, $addedAssets['css']);
        $this->assertCount(0, $addedAssets['js']);

        // test clear only js
        $this->Assets->addAsset('js', 'jquery', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js');
        $this->Assets->addAsset('css', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css');
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertCount(1, $addedAssets['css']);
        $this->assertCount(1, $addedAssets['js']);
        $this->Assets->clear('js');
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertCount(1, $addedAssets['css']);
        $this->assertCount(0, $addedAssets['js']);

        // test clear only css
        $this->Assets->addAsset('js', 'jquery', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js');
        $this->Assets->addAsset('css', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css');
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertCount(1, $addedAssets['css']);
        $this->assertCount(1, $addedAssets['js']);
        $this->Assets->clear('css');
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertCount(0, $addedAssets['css']);
        $this->assertCount(1, $addedAssets['js']);

        // test clear all
        $this->Assets->addAsset('js', 'jquery', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js');
        $this->Assets->addAsset('css', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css');
        $this->Assets->clear('');
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertCount(0, $addedAssets['js']);
        $this->assertCount(0, $addedAssets['css']);
    }// testClear


    public function testGenerateAssetUrlWithVersion()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__); // example: /app-path/Modules/MyModule
        unset($Url);

        $item['file'] = $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js';
        $item['version'] = '3.x.x';
        $this->assertEquals($item['file'] . '?v=3.x.x', $this->Assets->generateAssetUrlWithVersion($item));

        $item['file'] = $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js';
        $item['version'] = false;
        $this->assertEquals($item['file'], $this->Assets->generateAssetUrlWithVersion($item));

        $item['file'] = $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js';
        $item['version'] = true;
        $assetFileMT = filemtime(PUBLIC_PATH . '/Modules/RdbAdmin/assets/' . $this->testAssetFolderName . '/jquery.js');
        $this->assertEquals($item['file'] . '?v=fmt-' . $assetFileMT, $this->Assets->generateAssetUrlWithVersion($item));

        $item['file'] = $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js';
        $item['version'] = null;
        $assetFileMT = filemtime(PUBLIC_PATH . '/Modules/RdbAdmin/assets/' . $this->testAssetFolderName . '/jquery.js');
        $this->assertEquals($item['file'] . '?v=fmt-' . $assetFileMT, $this->Assets->generateAssetUrlWithVersion($item));

        $item['file'] = $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js?';
        $item['version'] = '3.x.x';
        $this->assertEquals($item['file'] . 'v=3.x.x', $this->Assets->generateAssetUrlWithVersion($item));// file already end with ? sign.

        $item['file'] = $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js?a=b';
        $item['version'] = '3.x.x';
        $this->assertEquals($item['file'] . '&v=3.x.x', $this->Assets->generateAssetUrlWithVersion($item));

        $item['file'] = $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js?a=b&';
        $item['version'] = '3.x.x';
        $this->assertEquals($item['file'] . 'v=3.x.x', $this->Assets->generateAssetUrlWithVersion($item));// file already end with & sign

        $item['file'] = $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js?v=view';
        $item['version'] = '3.x.x';
        $result = $this->Assets->generateAssetUrlWithVersion($item);
        $this->assertStringContainsString($item['file'], $result);// already has v=xxx so it must not overwrite.
        $this->assertStringContainsString('=3.x.x', $result);// already has v=xxx so it must not overwrite.

        // tests with full URL or CDN. ----------------------------------
        $item['file'] = 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js';
        $item['version'] = '3.5.1';
        $this->assertEquals($item['file'] . '?v=3.5.1', $this->Assets->generateAssetUrlWithVersion($item));
    }// testGenerateAssetUrlWithVersion


    public function testGenerateAttributes()
    {
        $attributes = [
            'data-name' => 'myName',
            'data-eschtml' => '&escape',
            'id' => 'myId',
            'class' => 'myClass',
        ];

        $this->assertEquals('data-name="myName" data-eschtml="&amp;escape" id="myId" class="myClass"', $this->Assets->generateAttributes($attributes));
        $this->assertEquals('data-name="myName" data-eschtml="&amp;escape" class="myClass"', $this->Assets->generateAttributes($attributes, ['id']));
        $this->assertEquals('data-name="myName" data-eschtml="&amp;escape"', $this->Assets->generateAttributes($attributes, ['id', 'class']));
    }// testGenerateAttributes


    public function testGenerateInlineScript()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $this->Assets->addAsset('js', 'jquery', $Url->getPublicModuleUrl(__FILE__) . '/assets/' . $this->testAssetFolderName . '/jquery.js');
        $this->Assets->addJsInline('jquery', 'function thisIsjustTest() {}', 'after');
        $this->Assets->addJsInline('jquery', 'function thisIsjustTestJsInlineBefore() {}', 'before');

        $addedAssets = $this->Assets->getAddedAssets();
        $generated = str_replace(["\r\n", "\r", "\n"], '', $this->Assets->generateInlineScript($addedAssets['js']['jquery']));
        $this->assertEquals('<script id="jquery-inlineScriptAfter" type="application/javascript">function thisIsjustTest() {}</script>', $generated);

        $generated = str_replace(["\r\n", "\r", "\n"], '', $this->Assets->generateInlineScript($addedAssets['js']['jquery'], 'before'));
        $this->assertEquals('<script id="jquery-inlineScriptBefore" type="application/javascript">function thisIsjustTestJsInlineBefore() {}</script>', $generated);

        unset($Url);
    }// testGenerateInlineScript


    public function testGenerateInlineStyle()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $this->Assets->addAsset('css', 'bootstrap', $Url->getPublicModuleUrl(__FILE__) . '/assets/' . $this->testAssetFolderName . '/bootstrap.css');
        $this->Assets->addCssInline('bootstrap', 'body {background-color: white;}');

        $addedAssets = $this->Assets->getAddedAssets();
        $generated = str_replace(["\r\n", "\r", "\n"], '', $this->Assets->generateInlineStyle($addedAssets['css']['bootstrap']));
        $this->assertEquals('<style id="bootstrap-inlineStyle" type="text/css">body {background-color: white;}</style>', $generated);

        unset($Url);
    }// testGenerateInlineStyle


    public function testGenerateJsObject()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $this->Assets->addAsset('js', 'jquery', $Url->getPublicModuleUrl(__FILE__) . '/assets/' . $this->testAssetFolderName . '/jquery.js');
        $this->Assets->addJsObject('jquery', 'myJqueryObject', ['name' => 'TestJQueryObj', 'version' => '3.x.x']);

        $addedAssets = $this->Assets->getAddedAssets();
        $generated = str_replace(["\r\n", "\r", "\n"], '', $this->Assets->generateJsObject($addedAssets['js']['jquery']));
        $this->assertEquals('<script id="jquery-jsObject" type="application/javascript">/* <![CDATA[ */var myJqueryObject = {"name":"TestJQueryObj","version":"3.x.x"};/* ]]> */</script>', $generated);

        unset($Url);
    }// testGenerateJsObject


    public function testGetDependencyExists()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        $this->Assets->addAsset('js', 'jquery', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js', [], '3.x.x', [], 'theJsGroup');
        $this->Assets->addAsset('js', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.js', ['jquery'], '4.x.x', [], 'theJsGroup');
        $this->Assets->addAsset('css', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css', [], '4.x.x', [], 'theCssGroup');
        $this->Assets->addAsset('css', 'bootstrap-theme', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap-theme.css', ['bootstrap'], '1.2.x', [], 'theCssGroup');

        $getResult = $this->Assets->getDependencyExists('js', ['jquery', 'bootstrap']);
        $this->assertArrayHasKey('exists', $getResult);
        $this->assertArrayHasKey('not_exists', $getResult);
        $this->assertCount(2, $getResult['exists']);
        $this->assertCount(0, $getResult['not_exists']);
        $this->assertTrue(empty(Arrays::array_diff_assoc_recursive(['exists' => ['jquery', 'bootstrap']], $getResult)));

        $getResult = $this->Assets->getDependencyExists('css', ['jquery', 'bootstrap']);
        $this->assertArrayHasKey('exists', $getResult);
        $this->assertArrayHasKey('not_exists', $getResult);
        $this->assertCount(1, $getResult['exists']);
        $this->assertCount(1, $getResult['not_exists']);
        $this->assertTrue(empty(Arrays::array_diff_assoc_recursive(['exists' => ['bootstrap'], 'not_exists' => ['jquery']], $getResult)));

        $getResult = $this->Assets->getDependencyExists('js', ['jquery', 'bootstrap4']);
        $this->assertArrayHasKey('exists', $getResult);
        $this->assertArrayHasKey('not_exists', $getResult);
        $this->assertCount(1, $getResult['exists']);
        $this->assertCount(1, $getResult['not_exists']);
        $this->assertTrue(empty(Arrays::array_diff_assoc_recursive(['exists' => ['jquery']], $getResult)));
        $this->assertTrue(empty(Arrays::array_diff_assoc_recursive(['not_exists' => ['bootstrap4']], $getResult)));
    }// testGetDependencyExists


    public function testMergeAssetsData()
    {
        $assetData1 = [
            'css' => [
                [
                    'handle' => 'rdta',
                    'file' => '/assets/css/rdta/rdta-bundled.min.css',
                    'version' => '2.0',
                ],
            ],
            'js' => [
                [
                    'handle' => 'rdta',
                    'file' => '/assets/js/rdta/rdta-bundled.min.js',
                    'version' => '2.0',
                ],
            ],
        ];
        $assetData2 = [
            'css' => [
                [
                    'handle' => 'rdba',
                    'file' => '/assets/js/rdba/rdba.min.css',
                ],
            ],
            'js' => [
                [
                    'handle' => 'rdba',
                    'file' => '/assets/js/rdba/rdba.min.js',
                ],
            ],
        ];
        $assetData3 = [
            'css' => [
                [
                    'handle' => 'data3',
                    'file' => '/assets/css/data3.css',
                    'version' => '1.0',
                ],
            ],
            'js' => [
                [
                    'handle' => 'data3',
                    'file' => '/assets/css/data3.js',
                    'version' => '1.0',
                ],
            ],
        ];

        $mergedCss = $this->Assets->mergeAssetsData('css', $assetData1, $assetData2);
        $assertCss = [
            'css' => [
                [
                    'handle' => 'rdta',
                    'file' => '/assets/css/rdta/rdta-bundled.min.css',
                    'version' => '2.0',
                ],
                [
                    'handle' => 'rdba',
                    'file' => '/assets/js/rdba/rdba.min.css',
                ],
            ],
        ];
        $this->assertTrue(empty(Arrays::array_diff_assoc_recursive($assertCss, $mergedCss)));
        $this->assertSame($assertCss, $mergedCss);

        $mergedJs = $this->Assets->mergeAssetsData('js', $assetData1, $assetData2);
        $assertJs = [
            'js' => [
                [
                    'handle' => 'rdta',
                    'file' => '/assets/js/rdta/rdta-bundled.min.js',
                    'version' => '2.0',
                ],
                [
                    'handle' => 'rdba',
                    'file' => '/assets/js/rdba/rdba.min.js',
                ],
            ],
        ];
        $this->assertTrue(empty(Arrays::array_diff_assoc_recursive($assertJs, $mergedJs)));
        $this->assertSame($assertJs, $mergedJs);

        $mergedCss = $this->Assets->mergeAssetsData('css', $assetData1, $assetData2, $assetData3);
        $assertCss = [
            'css' => [
                [
                    'handle' => 'rdta',
                    'file' => '/assets/css/rdta/rdta-bundled.min.css',
                    'version' => '2.0',
                ],
                [
                    'handle' => 'rdba',
                    'file' => '/assets/js/rdba/rdba.min.css',
                ],
                [
                    'handle' => 'data3',
                    'file' => '/assets/css/data3.css',
                    'version' => '1.0',
                ],
            ],
        ];
        $this->assertTrue(empty(Arrays::array_diff_assoc_recursive($assertCss, $mergedCss)));
        $this->assertSame($assertCss, $mergedCss);

        $mergedJs = $this->Assets->mergeAssetsData('js', $assetData1, $assetData2, $assetData3);
        $assertJs = [
            'js' => [
                [
                    'handle' => 'rdta',
                    'file' => '/assets/js/rdta/rdta-bundled.min.js',
                    'version' => '2.0',
                ],
                [
                    'handle' => 'rdba',
                    'file' => '/assets/js/rdba/rdba.min.js',
                ],
                [
                    'handle' => 'data3',
                    'file' => '/assets/css/data3.js',
                    'version' => '1.0',
                ],
            ],
        ];
        $this->assertTrue(empty(Arrays::array_diff_assoc_recursive($assertJs, $mergedJs)));
        $this->assertSame($assertJs, $mergedJs);
    }// testMergeAssetsData


    public function testRemoveAsset()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        $this->Assets->addAsset('js', 'jquery', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js', [], '3.x.x', [], 'theJsGroup');
        $this->Assets->addAsset('js', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.js', ['jquery'], '4.x.x', [], 'theJsGroup');
        $this->Assets->addAsset('css', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css', [], '4.x.x', [], 'theCssGroup');
        $this->Assets->addAsset('css', 'bootstrap-theme', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap-theme.css', ['bootstrap'], '1.2.x', [], 'theCssGroup');

        $this->Assets->removeAsset('js', 'bootstrap');
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertFalse(isset($addedAssets['js']['bootstrap']));
        $this->assertTrue(isset($addedAssets['js']['jquery']));

        $this->Assets->removeAsset('js', 'jquery');
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertFalse(isset($addedAssets['js']['bootstrap']));
        $this->assertFalse(isset($addedAssets['js']['jquery']));
    }// testRemoveAsset


    public function testRenderAssets()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        $this->Assets->addAsset('js', 'notexists', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/notexists.js', [], '1.2', [], 'group1');
        $this->Assets->addAsset('js', 'notexists2', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/notexists2.js', ['jquery'], '1.2', [], 'group1');
        $this->Assets->addAsset('js', 'jquery', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js', [], '3.x.x', [], 'theJsGroup');
        $this->Assets->addAsset('js', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.js', ['jquery'], '4.x.x', [], 'theJsGroup');
        $this->Assets->addJsInline('jquery', 'function thisIsjustTest() {}', 'after');
        $this->Assets->addJsInline('jquery', 'function thisIsjustTestJsInlineBefore() {}', 'before');
        $this->Assets->addJsObject('jquery', 'myJqueryObject', ['name' => 'TestJQueryObj', 'version' => '3.x.x']);
        $renderResult = $this->Assets->renderAssets('js', 'theJsGroup');
        $renderResult = str_replace(["\r\n", "\r", "\n", '  ', '   ', '    '], '', $renderResult);
        $assert = '<script id="jquery-jsObject" type="application/javascript">
        /* <![CDATA[ */
        var myJqueryObject = {"name":"TestJQueryObj","version":"3.x.x"};
        /* ]]> */
        </script>
        <script id="jquery-inlineScriptBefore" type="application/javascript">
        function thisIsjustTestJsInlineBefore() {}
        </script>
        <script id="jquery-js" type="application/javascript" src="/Modules/RdbAdmin/assets/' . $this->testAssetFolderName . '/jquery.js?v=3.x.x"></script>
        <script id="jquery-inlineScriptAfter" type="application/javascript">
        function thisIsjustTest() {}
        </script>
        <script id="bootstrap-js" type="application/javascript" src="/Modules/RdbAdmin/assets/' . $this->testAssetFolderName . '/bootstrap.js?v=4.x.x"></script>';
        $assert = str_replace(["\r\n", "\r", "\n", '  ', '   ', '    '], '', $assert);
        $this->assertEquals($assert, $renderResult);

        $assert = '<script id="notexists-js" type="application/javascript" src="/Modules/RdbAdmin/assets/' . $this->testAssetFolderName . '/notexists.js?v=1.2"></script>
        <script id="notexists2-js" type="application/javascript" src="/Modules/RdbAdmin/assets/' . $this->testAssetFolderName . '/notexists2.js?v=1.2"></script>';
        $assert = str_replace(["\r\n", "\r", "\n", '  ', '   ', '    '], '', $assert);
        $renderResult = $this->Assets->renderAssets('js', 'group1');
        $renderResult = str_replace(["\r\n", "\r", "\n", '  ', '   ', '    '], '', $renderResult);
        $this->assertEquals($assert, $renderResult);

        $this->Assets->addAsset('css', 'bootstrap-theme', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap-theme.css', ['bootstrap'], '1.2.x', [], 'theCssGroup');
        $this->Assets->addAsset('css', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css', [], '4.x.x', [], 'theCssGroup');
        $this->Assets->addCssInline('bootstrap', 'body {background-color: white;}');
        $renderResult = $this->Assets->renderAssets('css', 'theCssGroup');
        $renderResult = str_replace(["\r\n", "\r", "\n", '  ', '   ', '    '], '', $renderResult);
        $assert = '<link id="bootstrap-css" rel="stylesheet" type="text/css" href="/Modules/RdbAdmin/assets/' . $this->testAssetFolderName . '/bootstrap.css?v=4.x.x">
        <style id="bootstrap-inlineStyle" type="text/css">
        body {background-color: white;}
        </style>
        <link id="bootstrap-theme-css" rel="stylesheet" type="text/css" href="/Modules/RdbAdmin/assets/' . $this->testAssetFolderName . '/bootstrap-theme.css?v=1.2.x">';
        $assert = str_replace(["\r\n", "\r", "\n", '  ', '   ', '    '], '', $assert);
        $this->assertEquals($assert, $renderResult);
    }// testRenderAssets


    public function testTopologicalSort()
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);
        $publicModuleUrl = $Url->getPublicModuleUrl(__FILE__);
        unset($Url);

        $this->Assets->addAsset('js', 'jquery', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/jquery.js', [], '3.x.x', [], 'theJsGroup');
        $this->Assets->addAsset('js', 'bootstrap-modal', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap-modal.js', ['bootstrap'], '4.x.x', [], 'theJsGroup');
        $this->Assets->addAsset('js', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.js', ['jquery'], '4.x.x', [], 'theJsGroup');
        $this->Assets->addAsset('css', 'bootstrap-theme', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap-theme.css', ['bootstrap'], '1.2.x', [], 'theCssGroup');
        $this->Assets->addAsset('css', 'bootstrap', $publicModuleUrl . '/assets/' . $this->testAssetFolderName . '/bootstrap.css', [], '4.x.x', [], 'theCssGroup');

        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertEquals(['bootstrap-theme', 'bootstrap'], array_keys($addedAssets['css']));
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertEquals(['jquery', 'bootstrap-modal', 'bootstrap'], array_keys($addedAssets['js']));

        $this->Assets->topologicalSort('js');
        $sortedAssets = $this->Assets->getAssetsSorted();
        $this->assertArrayHasKey('js', $sortedAssets);
        $this->assertArrayHasKey('css', $sortedAssets);
        $this->assertTrue($sortedAssets['js']);
        $this->assertFalse($sortedAssets['css']);
        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertEquals(['jquery', 'bootstrap', 'bootstrap-modal'], array_keys($addedAssets['js']));

        $this->Assets->topologicalSort('css');
        $sortedAssets = $this->Assets->getAssetsSorted();
        $this->assertTrue($sortedAssets['js']);
        $this->assertTrue($sortedAssets['css']);

        $addedAssets = $this->Assets->getAddedAssets();
        $this->assertEquals(['bootstrap', 'bootstrap-theme'], array_keys($addedAssets['css']));
    }// testTopologicalSort


    public function testVerifyType()
    {
        $this->assertEquals('js', $this->Assets->verifyType('invalid'));
        $this->assertEquals('js', $this->Assets->verifyType('js'));
        $this->assertEquals('js', $this->Assets->verifyType('Js'));
        $this->assertEquals('js', $this->Assets->verifyType('jS'));
        $this->assertEquals('js', $this->Assets->verifyType('JS'));

        $this->assertEquals('css', $this->Assets->verifyType('css'));
        $this->assertEquals('css', $this->Assets->verifyType('CSS'));
        $this->assertEquals('css', $this->Assets->verifyType('CsS'));
    }// testVerifyType


}
