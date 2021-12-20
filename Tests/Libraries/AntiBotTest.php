<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


class AntiBotTest extends \Rdb\Tests\BaseTestCase
{


    public function testSetAndGetHoneypotName()
    {
        $AntiBot = new \Rdb\Modules\RdbAdmin\Libraries\AntiBot();
        $result = $AntiBot->setAndGetHoneypotName();
        $this->assertNotFalse(preg_match('/([a-zA-Z0-9\-_]+)/', $result));
        $this->assertTrue(mb_strlen($result) >= 3);// x_n where x is alpha-numeric and n is number.
    }// testSetAndGetHoneypotName


}
