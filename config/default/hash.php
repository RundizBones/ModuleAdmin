<?php
/** 
 * Hash configuration.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 * 
 * Use these tools to re-generate the keys in put it in config/[your environment]/hash.php
 * @link http://www.unit-conversion.info/texttools/random-string-generator/ Generate secret key online.
 * @link https://passwordsgenerator.net/ Generate secret key online.
 * @link https://keygen.io/ Generate secret key online.
 */


return [
    // logged in key for use with logged in cookie or session data. length should be 30 - 80 characters.
    'rdbaLoggedinKey' => '|m7%G-:%0v$ WmWa8<2PR:5#rE6/@{@vh`4&)$txH6+2L(HQU+5F%#rjKYcUkM|',
    // generic cookies key for use with anything else. length should be 30 - 80 characters.
    'rdbaGenericKey' => 'zm?MV4FU1A=OuKx2Z.@`N4C7brqlbaIX2@(IG}BJ[Z[@=JNW@$',
    // device cookies secret key. length should be 128 characters.
    'rdbaDeviceCookieSecret' => '3627c88899640decfac001f4f826b628fbbbda09e6825de708512ec304ec21be41d7572752088a6b8e16ec0a19438049eec732eb658bb175da893b8eaa8c0927',
    // user fields secret keys. length should be 30 - 80 characters.
    'rdbaUserFieldsKeys' => '_.4Pb7)Nf]=sjP4c56`@^P:w>%&sX*.;j}KUsd3&',
];