<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Libraries\Extended;


/**
 * Extended Slim Guard class.
 * 
 * Base on Slim Csrf 0.8.x.<br>
 * This extended class is for easier to access protected method(s).
 * 
 * @since 0.1
 * @link https://github.com/slimphp/Slim-Csrf Slim CSRF document
 */
class SlimGuard extends \Slim\Csrf\Guard
{


    /**
     * {@inheritDoc}
     */
    public function enforceStorageLimit()
    {
        return parent::enforceStorageLimit();
    }// enforceStorageLimit


    /**
     * {@inheritDoc}
     */
    public function generateToken()
    {
        // this extended method fix generate token that was set to persistent mode but it always re-generate.
        if ($this->persistentTokenMode) {
            if ($this->loadLastKeyPair()) {
                return $this->keyPair;
            } else {
                return parent::generateToken();
            }
        } else {
            return parent::generateToken();
        }
    }// generateToken


}
