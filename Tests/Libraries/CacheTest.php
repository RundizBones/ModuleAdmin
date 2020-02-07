<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


class CacheTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\Modules\RdbAdmin\Libraries\Cache
     */
    protected $Cache;


    public function setup()
    {
        $this->runApp('GET', '/');
        $this->Container = $this->RdbApp->getContainer();

        if (!$this->Container instanceof \Rdb\System\Container) {
            $this->markTestIncomplete('Unable to get container');
        }

        $this->Cache = new \Rdb\Modules\RdbAdmin\Libraries\Cache(
            $this->Container,
            [
                'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Tests',
            ]
        );
    }// setup


    public function testConstructor()
    {
        $this->assertTrue($this->Cache instanceof \Rdb\Modules\RdbAdmin\Libraries\Cache);
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
