<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Anti robot class to prevent spam.
 */
class AntiBot
{


    /**
     * @var null|array The default honeypot names. Leave null means not set yet. This is for in case `honeypotNames` property was override.
     */
    protected $defaultHoneypotNames;


    /**
     * @var array The honeypot names.
     */
    protected $honeypotNames = ['birthdate', 'fullname', 'phonenumber', 'mobilenumber', 'secondary-email', 'national-id'];


    /**
     * Get the honeypot name.
     * 
     * @return string
     */
    public function getHoneypotName(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['honeypotName'])) {
            return $_SESSION['honeypotName'];
        }

        return '';
    }// getName


    /**
     * Set and get the field name for use in honeypot. To get only, use `getHoneypotName()` method.
     * 
     * @param array $allowedNames The custom allowed names. If leave empty, it will be use default.
     * @return string Return generated honeypot name to use. The honeypot name will be set to session `$_SESSION['honeypotName']`. Use this session to check that honeypot name must be empty.
     */
    public function setAndGetHoneypotName(array $allowedNames = []): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($allowedNames)) {
            if (is_null($this->defaultHoneypotNames)) {
                $this->defaultHoneypotNames = $this->honeypotNames;
            }

            $this->honeypotNames = $allowedNames;
        } else {
            if (!is_null($this->defaultHoneypotNames)) {
                $this->honeypotNames = $this->defaultHoneypotNames;
                $this->defaultHoneypotNames = null;
            }
        }

        $output = $this->honeypotNames[mt_rand(0, (count($this->honeypotNames) - 1))] . '_' . mt_rand(0, 999);
        $_SESSION['honeypotName'] = $output;
        return $output;
    }// setAndGetNames


    /**
     * Get honeypot name.
     * 
     * @see getHoneypotName() Calling to this method as non static.
     * @return string
     */
    public static function staticGetHoneypotName(): string
    {
        $thisClass = new static();
        return $thisClass->getHoneypotName();
    }// staticGetHoneypotName


}
