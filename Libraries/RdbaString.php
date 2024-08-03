<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * String class. (String class name is reserved, see https://www.php.net/manual/en/reserved.other-reserved-words.php for more details.)
 * 
 * @since 0.1
 * @method static staticFilterSanitizeString(string $paramName)
 * @method static staticRandom(int $length, string $characters)
 * @method static staticRandomUnicode(int $length, string $characters)
 * @method static staticSanitizeDisplayname(string $displayname)
 * @method static staticSanitizeUsername(string $username)
 */
class RdbaString
{


    /**
     * For static methods on non-static methods.
     * 
     * @since 1.2.10
     * @param string $name The method name.
     * @param array $arguments The method's arguments.
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $thisClass = new static;
        $methodName = preg_replace('/static(.+)/', '$1', $name);
        if (is_string($methodName)) {
            $methodName = lcfirst($methodName);
        }

        return $thisClass->{$methodName}(...$arguments);
    }// __callStatic


    /**
     * Strip HTML tags and then do the `htmlspecialchars()` with single quotes.
     * 
     * This is for replacement that `FILTER_SANITIZE_STRING` constant has been deprecated since PHP 8.1<br>
     * This method does not support any flags that `FILTER_SANITIZE_STRING` constant may have.
     * 
     * @since 1.2.10
     * @param string $string The string to be filter and sanitize.
     * @return string Return filtered and sanitized string.
     */
    public function filterSanitizeString(string $string): string
    {
        return htmlspecialchars(strip_tags($string), ENT_QUOTES);
    }// filterSanitizeString


    /**
     * Generate random string.
     * 
     * For ASCII text only.
     * 
     * @link https://stackoverflow.com/a/4356295/128761 Reference
     * @link https://www.php.net/manual/en/language.types.string.php#language.types.string.substr PHP string bracket reference.
     * @param int $length String length.
     * @param string $characters The allowed characters.
     * @return string Return generated random string.
     */
    public function random(int $length = 10, string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        if ($characters === '' || strtolower(mb_detect_encoding($characters)) !== 'ascii') {
            // if characters is not ASCII.
            // use default ASCII text because PHP string index does not support multi-byte.
            // @link https://www.php.net/manual/en/language.types.string.php#language.types.string.substr PHP string bracket reference.
            // Warning Internally, PHP strings are byte arrays. 
            // As a result, accessing or modifying a string using array brackets is not multi-byte safe, 
            // and should only be done with strings that are in a single-byte encoding such as ISO-8859-1
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        $charactersLength = mb_strlen($characters, '8bit') - 1;
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength)];
        }// endfor;

        unset($charactersLength);
        return $randomString;
    }// random


    /**
     * Generate random unicode string.
     * 
     * To count the length, please use `mb_strlen()` instead of `strlen()`.
     * 
     * @link https://stackoverflow.com/a/4356295/128761 Reference
     * @param int $length String length.
     * @param string $characters The allowed characters.
     * @return string Return generated random string.
     */
    public function randomUnicode(int $length = 10, string $characters = ''): string
    {
        if (trim($characters) == null) {
            // if set null to characters
            // means use default.
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $characters .= '๐๑๒๓๔๕๖๗๘๙กขฃคฅฆงจฉชซฌญฎฏฐฑฒณดตถทธนบปผฝพฟภมยรฤลฦวศษสหฬอฮ฿';
        }

        $charactersLength = mb_strlen($characters) - 1;
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= mb_substr($characters, random_int(0, $charactersLength), 1);
        }

        unset($charactersLength);
        return $randomString;
    }// randomUnicode


    /**
     * Sanitize display name.
     * 
     * The difference from `sanitizeUsername()` as it will not remove space between text but allowed just one.
     * 
     * @link https://en.wikipedia.org/wiki/Email_address Reference for some disallowed characters.
     * @param string $displayname The display name.
     * @return string Return sanitized display name.
     */
    public function sanitizeDisplayname($displayname): string
    {
        // remove all HTML tags.
        $displayname = strip_tags($displayname);
        // remove space at start and end.
        $displayname = trim($displayname);

        // remove special characters. "(),:;<>@[\]
        $specialChars = preg_quote('"(),:;<>@[\]', '#');
        $displayname = preg_replace('#[' . $specialChars . ']+#', '', $displayname);
        unset($specialChars);

        // do not allow more than one (dots, spaces).
        $displayname = preg_replace('/\.+/', '.', $displayname);
        $displayname = preg_replace('/\s+/', ' ', $displayname);

        return $displayname;
    }// sanitizeDisplayname


    /**
     * Sanitize username.
     * 
     * @link https://en.wikipedia.org/wiki/Email_address Reference for some disallowed characters.
     * @link https://stackoverflow.com/a/10342485/128761 Remove duplicate characters.
     * @param string $username The input username.
     * @return string Return sanitized username.
     */
    public function sanitizeUsername($username): string
    {
        // remove all HTML tags.
        $username = strip_tags($username);
        // remove space at start and end.
        $username = trim($username);
        // remove all spaces between text.
        $username = preg_replace('#\s+#', '', $username);

        // remove special characters. "(),:;<>@[\]
        $specialChars = preg_quote('"(),:;<>@[\]', '#');
        $username = preg_replace('#[' . $specialChars . ']+#', '', $username);
        unset($specialChars);

        // do not allow more than one dots.
        $username = preg_replace('#([\.])\\1+#', '$1', $username);

        return $username;
    }// sanitizeUsername


}
