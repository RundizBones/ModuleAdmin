<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


class EncryptionTest extends \Rdb\Tests\BaseTestCase
{


    public function testEncryptDecrypt()
    {
        // test that encrypt and then decrypt must be the same.
        // cannot test and assert just encrypted message because it can be different results.
        $Encryption = new \Rdb\Modules\RdbAdmin\Libraries\Encryption();
        $key = 'secretKey';
        $readableText = 'Hello World!';

        $Encryption->setCipherMethod('AES-128-CBC');
        $encryptedText = $Encryption->encrypt($readableText, $key);
        $this->assertEquals($readableText, $Encryption->decrypt($encryptedText, $key));

        $Encryption->setCipherMethod('AES-192-CBC');
        $encryptedText = $Encryption->encrypt($readableText, $key);
        $this->assertEquals($readableText, $Encryption->decrypt($encryptedText, $key));

        $Encryption->setCipherMethod('AES-256-CBC');
        $encryptedText = $Encryption->encrypt($readableText, $key);
        $this->assertEquals($readableText, $Encryption->decrypt($encryptedText, $key));

        // change key and readable text.
        $key = time();
        $readableText = 'Hello, สวัสดี, ታዲያስ, নমস্কার, こんにちは, 안녕하세요, 你好';

        $Encryption->setCipherMethod('AES-128-CBC');
        $encryptedText = $Encryption->encrypt($readableText, $key);
        $this->assertEquals($readableText, $Encryption->decrypt($encryptedText, $key));

        $Encryption->setCipherMethod('AES-192-CBC');
        $encryptedText = $Encryption->encrypt($readableText, $key);
        $this->assertEquals($readableText, $Encryption->decrypt($encryptedText, $key));

        $Encryption->setCipherMethod('AES-256-CBC');
        $encryptedText = $Encryption->encrypt($readableText, $key);
        $this->assertEquals($readableText, $Encryption->decrypt($encryptedText, $key));
    }// testEncryptDecrypt


}
