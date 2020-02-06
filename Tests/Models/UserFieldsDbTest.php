<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Tests\Models;


class UserFieldsDbTest extends \Tests\Rdb\BaseTestCase
{


    public function setup()
    {
        $this->Container = new \System\Container();
        $this->Container['Config'] = function ($c) {
            return new \System\Config();
        };
        $this->Container['Db'] = function ($c) {
            return new \System\Libraries\Db($c);
        };

        $this->Db = $this->Container['Db'];

        if ($this->Db->currentConnectionKey() === null) {
            $this->markTestIncomplete('Unable to connect to DB.');
        }
    }// setup


    public function tearDown()
    {
        $this->Db->disconnectAll();
    }// tearDown


    public function testPropertyRdbaUserFields()
    {
        $UserFieldsDb = new \Modules\RdbAdmin\Models\UserFieldsDb($this->Container);

        $this->assertTrue(isset($UserFieldsDb->rdbaUserFields['rdbadmin_uf_registerconfirm_key']));
        $this->assertGreaterThan(2, mb_strlen($UserFieldsDb->rdbaUserFields['rdbadmin_uf_registerconfirm_key']));
    }// testPropertyRdbaUserFields


}
