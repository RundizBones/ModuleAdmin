<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


class CookieTest extends \Rdb\Tests\BaseTestCase
{


    public function setup(): void
    {
        $this->runApp('GET', '/');
        $this->Container = $this->RdbApp->getContainer();

        if (!$this->Container instanceof \Rdb\System\Container) {
            $this->markTestIncomplete('Unable to get container');
        }
    }// setup


    public function testSet()
    {
        $Cookie = new \Rdb\Modules\RdbAdmin\Libraries\Cookie($this->Container);
        $cookieValue = [
            'name' => 'Vee',
            'lastname' => 'W.',
        ];
        $setCookieResult = $Cookie->set('testcookie', $cookieValue, (time() + (30 * 24 * 60 * 60)), '/');

        $this->assertTrue($setCookieResult);
    }// testSet


}
