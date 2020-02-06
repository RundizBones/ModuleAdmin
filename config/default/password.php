<?php
/** 
 * Password configuration.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 * @link https://www.php.net/manual/en/function.password-hash.php PHP password hash documentation.
 */


/**
 * The format of password configuration must be associative array that contain `algo` and/or `options` in keys.<br>
 * The key `algo` is required, the key `options` is optional.<br>
 * If the key `options` appears, it must contain related options to the password algorithm.<br>
 * See https://www.php.net/manual/en/function.password-hash.php for more details about options and algorithm.
 * 
 * Do not change anything in this file where it is in 'default' folder because it will be overwritten when updated.<br>
 * Use your environment folder in 'config' folder instead.<br>
 * Example: config/production/password.php.
 */
return [
    'algo' => PASSWORD_DEFAULT,
    'options' => [
        'cost' => 11,
    ],
];