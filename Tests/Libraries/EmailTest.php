<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Tests\RdbAdmin\Rdb\System\Libraries;


class EmailTest extends \Rdb\Tests\BaseTestCase
{


    public function setup(): void
    {
        $_SERVER['RUNDIZBONES_LANGUAGE'] = 'th';

        $this->Container = new \Rdb\System\Container();
        $this->Container['Config'] = function ($c) {
            return new \Rdb\System\Config();
        };
        $this->Container['Db'] = function ($c) {
            return new \Rdb\System\Libraries\Db($c);
        };

        $this->Db = $this->Container->get('Db');

        if ($this->Db->currentConnectionKey() === null) {
            $this->markTestIncomplete('Unable to connect to DB.');
        }
    }// setup


    public function testGetMailer()
    {
        $Email = new \Rdb\Modules\RdbAdmin\Libraries\Email($this->Container);
        $this->assertTrue($Email->getMailer() instanceof \PHPMailer\PHPMailer\PHPMailer);
    }// testGetMailer


    public function testGetMessage()
    {
        $Email = new \Rdb\Modules\RdbAdmin\Libraries\Email($this->Container);
        $message = $Email->getMessage('RdbAdmin', 'ForgotLoginPass');
        $this->assertTrue((is_string($message) && !empty($message)));
    }// testGetMessage


}
