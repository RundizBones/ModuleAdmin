<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Tests\Libraries;


class CacheTest extends \Tests\Rdb\BaseTestCase
{


    /**
     * @var \Modules\RdbAdmin\Libraries\Cache
     */
    protected $Cache;


    public function setup()
    {
        $this->runApp('GET', '/');
        $this->Container = $this->RdbApp->getContainer();

        if (!$this->Container instanceof \System\Container) {
            $this->markTestIncomplete('Unable to get container');
        }

        $this->Cache = new \Modules\RdbAdmin\Libraries\Cache(
            $this->Container,
            [
                'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Tests',
            ]
        );
    }// setup


    public function testConstructor()
    {
        $this->assertTrue($this->Cache instanceof \Modules\RdbAdmin\Libraries\Cache);
    }// testConstructor


    public function testGetCacheObject()
    {
        $Cache = $this->Cache->getCacheObject();
        $this->assertTrue($Cache instanceof \Psr\SimpleCache\CacheInterface);

        $this->assertFalse($Cache->has('something_that_nevercached_' . time()));

        $this->assertFalse($Cache->has('hello.test'));
        $this->assertTrue($Cache->set('hello.test', 'Hello world', 120));
        $this->assertTrue($Cache->has('hello.test'));
        $this->assertSame('Hello world', $Cache->get('hello.test'));
        $this->assertTrue($Cache->delete('hello.test'));
        $this->assertFalse($Cache->has('hello.test'));
    }// testGetCacheObject


}
