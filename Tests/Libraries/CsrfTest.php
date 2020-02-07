<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Tests\Rdb\System\Libraries;


class CsrfTest extends \Rdb\Tests\BaseTestCase
{


    public function testConstructorOptions()
    {
        // test default options.
        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $assert1 = [
            'prefix' => 'csrf',
            'storage' => null,
            'failureCallable' => null,
            'storageLimit' => 1,
            'strength' => 16,
            'persistentTokenMode' => false,
        ];
        $this->assertSame($assert1, $Csrf->options);

        // test customized options. (with wrong order).
        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf([
            'failureCallable' => null,
            'strength' => 20,
            'prefix' => 'csrf2',
            'persistentTokenMode' => true,
            'storageLimit' => 2,
        ]);
        $assert2 = [
            'prefix' => 'csrf2',
            'storage' => null,
            'failureCallable' => null,
            'storageLimit' => 2,
            'strength' => 20,
            'persistentTokenMode' => true,
        ];
        $this->assertSame($assert2, $Csrf->options);

        // test wrong options value.
        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf([
            'persistentTokenMode' => 'true',
            'strength' => '20',
            'prefix' => ['csrf2'],
            'storageLimit' => 2,
        ]);
        $assert3 = [
            'prefix' => 'csrf',
            'storage' => null,
            'failureCallable' => null,
            'storageLimit' => 2,
            'strength' => 16,
            'persistentTokenMode' => false,
        ];
        $this->assertSame($assert3, $Csrf->options);

        // test class instance.
        $this->assertTrue($Csrf->CsrfClass instanceof \Slim\Csrf\Guard);
    }// testConstructorOptions


    public function testCreateToken()
    {
        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();

        $genToken = $Csrf->createToken();

        $this->assertTrue(is_array($genToken));
        $this->assertArrayHasKey('csrfName', $genToken);
        $this->assertArrayHasKey('csrfValue', $genToken);
        $this->assertArrayHasKey('csrfKeyPair', $genToken);
    }// testCreateToken


    public function testGetTokenNameValueKey()
    {
        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();

        $tokenKeys = $Csrf->getTokenNameValueKey();
        $this->assertTrue(is_array($tokenKeys));
        $this->assertArrayHasKey('csrfName', $tokenKeys);
        $this->assertArrayHasKey('csrfValue', $tokenKeys);

        $tokenKeys = $Csrf->getTokenNameValueKey(true);
        $this->assertTrue(is_array($tokenKeys));
        $this->assertCount(2, $tokenKeys);
        $this->assertTrue(array_key_exists(0, $tokenKeys));
        $this->assertTrue(array_key_exists(1, $tokenKeys));
    }// testGetTokenNameValueKey


    public function testValidateToken()
    {
        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();

        $genToken = $Csrf->createToken();

        $this->assertTrue($Csrf->validateToken($genToken['csrfKeyPair'][$genToken['csrfName']], $genToken['csrfKeyPair'][$genToken['csrfValue']]));
    }// testValidateToken


}
