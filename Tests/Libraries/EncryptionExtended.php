<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


/**
 * Extended of encryption class.
 */
class EncryptionExtended extends \Rdb\Modules\RdbAdmin\Libraries\Encryption
{


    public function encryptMethodLength(): int
    {
        return parent::encryptMethodLength();
    }// encryptMethodLength


}
