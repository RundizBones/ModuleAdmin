<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Models;


class ConfigDbTest extends \Rdb\Tests\BaseTestCase
{


    public function setup(): void
    {
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


    public function tearDown(): void
    {
        $this->Db->disconnectAll();
    }// tearDown


    public function testGet()
    {
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $assertNot = (time() . microtime(true)*1000);
        $this->assertNotEquals($assertNot, $ConfigDb->get('rdbadmin_SiteName', $assertNot));
        $this->assertTrue(is_string($ConfigDb->get('rdbadmin_SiteName')));

        // test config multiple (array) and found.
        $names = ['rdbadmin_SiteName'];
        $defaults = ['MySite'];
        $results = $ConfigDb->get($names, $defaults);
        $this->assertTrue(is_array($results));
        $this->assertArrayHasKey($names[0], $results);
        $this->assertNotEquals($defaults[0], $results[$names[0]]);// name[configxxx] = 'Current site name data in db. Not "MySite".'

        // test config multiple (array) but not found.
        $names = ['configNameThatIsNotExists' . time()];
        $defaults = ['A'];
        $results = $ConfigDb->get($names, $defaults);
        $this->assertTrue(is_array($results));
        $this->assertArrayHasKey($names[0], $results);
        $this->assertSame($defaults[0], $results[$names[0]]);// name[configxxx] = 'A'
    }// testGet


    public function testGetRow()
    {
        $ConfigDb = new ConfigDbExtended($this->Container);
        $assertNot = (time() . microtime(true)*1000);
        $this->assertNotEquals($assertNot, $ConfigDb->getRow('rdbadmin_SiteName', $assertNot));
        $this->assertTrue(is_object($ConfigDb->getRow('rdbadmin_SiteName', $assertNot)));
        $this->assertObjectHasAttribute('config_value', $ConfigDb->getRow('rdbadmin_SiteName', $assertNot));

        // test config not found
        $this->assertSame('', $ConfigDb->getRow('configNameThatIsNotExists' . time(), ''));// not found must return default which is empty string.
    }// testGetRow


    public function testGetMultiple()
    {
        $ConfigDb = new ConfigDbExtended($this->Container);

        $names = ['rdbadmin_SiteName', 'rdbadmin_SiteTimezone'];
        $defaults = ['Site ' . (time() . microtime(true)*1000), 'Abc' . (time() . microtime(true)*1000)];
        $results = $ConfigDb->getMultiple($names, $defaults);

        $this->assertArrayHasKey('rdbadmin_SiteName', $results);
        $this->assertArrayHasKey('rdbadmin_SiteTimezone', $results);
        $this->assertNotEquals($defaults[0], $results['rdbadmin_SiteName']);
        $this->assertNotEquals($defaults[1], $results['rdbadmin_SiteTimezone']);

        // test config not found
        $names = ['configNameThatIsNotExists' . time(), 'anotherConfigNotExists' . time()];
        $defaults = ['A', 'B'];
        $results = $ConfigDb->getMultiple($names, $defaults);
        $this->assertTrue(is_array($results));
        $this->assertArrayHasKey($names[0], $results);
        $this->assertArrayHasKey($names[1], $results);
        $this->assertSame($defaults[0], $results[$names[0]]);// name[configxxx] = 'A'
        $this->assertSame($defaults[1], $results[$names[1]]);// name[anotherxxx] = 'B'
    }// testGetMultiple


}
