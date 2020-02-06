<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Tests\RdbAdmin\System\Libraries;


class EmailTest extends \Tests\Rdb\BaseTestCase
{


    public function setup()
    {
        $_SERVER['RUNDIZBONES_LANGUAGE'] = 'th';

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


    public function testGetMailer()
    {
        $Email = new \Modules\RdbAdmin\Libraries\Email($this->Container);
        $this->assertTrue($Email->getMailer() instanceof \PHPMailer\PHPMailer\PHPMailer);
    }// testGetMailer


    public function testGetMessage()
    {
        $Email = new \Modules\RdbAdmin\Libraries\Email($this->Container);
        $message = $Email->getMessage('RdbAdmin', 'ForgotLoginPass');
        $this->assertTrue((is_string($message) && !empty($message)));
    }// testGetMessage


}
