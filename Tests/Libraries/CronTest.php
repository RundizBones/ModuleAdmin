<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


class CronTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    public function setup(): void
    {
        $this->runApp('GET', '/');

        $this->Container = $this->RdbApp->getContainer();
    }// setup


    public function testGetCallerClass()
    {
        $Cron = new CronExtended($this->Container);

        $getCallerClassResult = $Cron->checkCallerfromHasRun();
        $this->assertStringContainsString('Tests\\Libraries\\CronTest', $getCallerClassResult);
        $this->assertSame(__CLASS__, $getCallerClassResult);
    }// testGetCallerClass


    public function testGetSecondsBeforeNext()
    {
        $Cron = new CronExtended($this->Container);

        $this->assertTrue(is_int($Cron->getSecondsBeforeNext('minute')));
        $this->assertTrue(is_int($Cron->getSecondsBeforeNext('hour')));
        $this->assertTrue(is_int($Cron->getSecondsBeforeNext('day')));
    }// testGetSecondsBeforeNext


    public function testGetSecondsBeforeNextError()
    {
        $Cron = new CronExtended($this->Container);

        $this->expectException(\DomainException::class);
        $Cron->getSecondsBeforeNext('invalidValue');// will throw an error.
    }// testGetSecondsBeforeNext


}
