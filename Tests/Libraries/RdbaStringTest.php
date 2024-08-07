<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


class RdbaStringTest extends \Rdb\Tests\BaseTestCase
{


    public function testFilterSanitizeString()
    {
        $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();
        $string = 'Hello \' \" & <Script>café</script> <!@#$';
        $assert = 'Hello &#039; \&quot; &amp; café ';

        $this->assertSame($assert, $RdbaString->filterSanitizeString($string));
    }// testFilterSanitizeString


    public function testStaticFilterSanitizeString()
    {
        $string = 'Hello \' \" & <Script>café</script> <!@#$';
        $assert = 'Hello &#039; \&quot; &amp; café ';

        $this->assertSame($assert, \Rdb\Modules\RdbAdmin\Libraries\RdbaString::staticFilterSanitizeString($string));
    }// \Rdb\Modules\RdbAdmin\Libraries\RdbaString::


    public function testRandom()
    {
        $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();

        $this->assertSame(12, strlen($RdbaString->random(12)));
        $this->assertSame(33, strlen($RdbaString->random(33)));

        $randomThai = $RdbaString->random(46, 'กขฃคฅฆงจฉชซฌญฎฏฐฑฒณดตถทธนบปผฝพฟภมยรฤลฦวศษสหฬอฮ');
        $matchedResult = preg_match('/([ก-ฮ]+)/u', $randomThai);// contain any ก-ฮ.
        $this->assertSame(0, $matchedResult);// 0 must not contain that

        $randomSpecialChars = $RdbaString->random(9, '!@#$%^&*()_+-=[{]}\\|\'";:,<.>/?');
        $matchedResult = preg_match('/[a-z]+/iu', $randomSpecialChars);// contain any a to z (case insensitive).
        $this->assertSame(0, $matchedResult);// 0 must not contain that
    }// testRandom


    public function testStaticRandom()
    {
        $this->assertSame(12, strlen(\Rdb\Modules\RdbAdmin\Libraries\RdbaString::staticrandom(12)));// can use lower case after word `static`.
        $this->assertSame(33, strlen(\Rdb\Modules\RdbAdmin\Libraries\RdbaString::staticRandom(33)));
    }// testStaticRandom


    public function testRandomUnicode()
    {
        $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();

        $this->assertSame(14, mb_strlen($RdbaString->randomUnicode(14)));
        $this->assertSame(26, mb_strlen($RdbaString->randomUnicode(26)));

        $randomThai = $RdbaString->randomUnicode(37, 'กขฃคฅฆงจฉชซฌญฎฏฐฑฒณดตถทธนบปผฝพฟภมยรฤลฦวศษสหฬอฮ');
        $matchedResult = preg_match('/([ก-ฮ]+)/u', $randomThai);
        $this->assertSame(1, $matchedResult);// 1 must contain
        $matchedResult = preg_match('/([a-z]+)/u', $randomThai);
        $this->assertSame(0, $matchedResult);// 0 must not contain that

        $randomSpecialChars = $RdbaString->randomUnicode(9, '!@#$%^&*()_+-=[{]}\\|\'";:,<.>/?');
        $matchedResult = preg_match('/[a-zก-ฮ]+/iu', $randomSpecialChars);// contain any a to z (case insensitive), ก-ฮ.
        $this->assertSame(0, $matchedResult);// 0 must not contain that
    }// testRandomUnicode


    public function testStaticRandomUnicode()
    {
        $this->assertSame(14, mb_strlen(\Rdb\Modules\RdbAdmin\Libraries\RdbaString::staticRandomUnicode(14)));
    }// testStaticRandomUnicode


    public function testSanitizeDisplayname()
    {
        $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();

        // no HTML
        $this->assertSame('displayname', $RdbaString->sanitizeDisplayname('<div>displayname</div>'));
        $this->assertSame('displayname', $RdbaString->sanitizeDisplayname('<script>displayname</script>'));

        // no more than one spaces.
        // @link https://www.php.net/manual/en/function.trim.php whitespace characters
        // @link https://www.ibm.com/support/knowledgecenter/en/SSMKHH_10.0.0/com.ibm.etools.mft.doc/ad26650_.htm whitespace characters
        // @link https://gist.github.com/leebyron/7a63e80b31d9d4cc9061 whitespace characters
        $this->assertSame('display name', $RdbaString->sanitizeDisplayname(' display name '));
        $this->assertSame('display name', $RdbaString->sanitizeDisplayname('display name '));
        $this->assertSame('display name', $RdbaString->sanitizeDisplayname(' display name'));
        $this->assertSame('display name', $RdbaString->sanitizeDisplayname(' display        name     '));
        $this->assertSame('display name', $RdbaString->sanitizeDisplayname(' display    ' . "\t\0\x0B \n\r \r\n \x09" . '   name     '));
        $this->assertSame('display name', $RdbaString->sanitizeDisplayname(' display name' . "\t\0\x0B \n\r \r\n \x09"));

        // no double quote (").
        $this->assertSame('displayname', $RdbaString->sanitizeDisplayname('display""name'));
        $this->assertSame('displayname', $RdbaString->sanitizeDisplayname('display"name'));

        // no special characters. "(),:;<>@[\]
        $this->assertSame('display', $RdbaString->sanitizeDisplayname('display    <name'));// removed <name by strig_tags.
        $this->assertSame('display', $RdbaString->sanitizeDisplayname('display    <<<name'));// removed <<<name by strig_tags.
        $this->assertSame('display name', $RdbaString->sanitizeDisplayname('display    <  name'));
        $this->assertSame('display name', $RdbaString->sanitizeDisplayname('display    "(),:;<>@[\]  name'));
        $this->assertSame('display name', $RdbaString->sanitizeDisplayname('display    "(),:;<>@[\]   ]]<<<]]][]][]>>>  name'));

        // no more than one dots at a time.
        $this->assertSame('display.name', $RdbaString->sanitizeDisplayname('display......name'));
        $this->assertSame('display. . .name.lastname', $RdbaString->sanitizeDisplayname('display.  ....    .name..lastname'));
        $this->assertSame('display . name.lastname', $RdbaString->sanitizeDisplayname('display . name..lastname'));
        $this->assertSame('display . name.lastname', $RdbaString->sanitizeDisplayname('display ...    name..lastname'));
        $this->assertSame('display . . name.lastname', $RdbaString->sanitizeDisplayname('display     ....     ...    name..lastname'));

        unset($RdbaString);
    }// testSanitizeDisplayname


    public function testStaticSanitizeDisplayname()
    {
        $this->assertSame('display name', \Rdb\Modules\RdbAdmin\Libraries\RdbaString::staticSanitizeDisplayname(' display name '));
    }// testStaticSanitizeDisplayname


    public function testSanitizeUsername()
    {
        $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();

        // no HTML
        $this->assertSame('username', $RdbaString->sanitizeUsername('<div>username</div>'));
        $this->assertSame('username', $RdbaString->sanitizeUsername('<script>username</script>'));

        // no spaces.
        // @link https://www.php.net/manual/en/function.trim.php whitespace characters
        // @link https://www.ibm.com/support/knowledgecenter/en/SSMKHH_10.0.0/com.ibm.etools.mft.doc/ad26650_.htm whitespace characters
        // @link https://gist.github.com/leebyron/7a63e80b31d9d4cc9061 whitespace characters
        $this->assertSame('username', $RdbaString->sanitizeUsername(' user name '));
        $this->assertSame('username', $RdbaString->sanitizeUsername('user name '));
        $this->assertSame('username', $RdbaString->sanitizeUsername(' user name'));
        $this->assertSame('username', $RdbaString->sanitizeUsername(' user    name '));
        $this->assertSame('username', $RdbaString->sanitizeUsername(' user      name '));
        $this->assertSame('username', $RdbaString->sanitizeUsername(' user ' . "\t\0\x0B \n\r \r\n \x09" . 'name '));

        // no double quote (").
        $this->assertSame('username', $RdbaString->sanitizeUsername('user""name'));
        $this->assertSame('username', $RdbaString->sanitizeUsername('user"name'));

        // no special characters. "(),:;<>@[\]
        $this->assertSame('username', $RdbaString->sanitizeUsername('user(name'));
        $this->assertSame('username', $RdbaString->sanitizeUsername('user))name'));
        $this->assertSame('username', $RdbaString->sanitizeUsername('user,,,name'));
        $this->assertSame('username', $RdbaString->sanitizeUsername('user:name'));
        $this->assertSame('username', $RdbaString->sanitizeUsername('user;;name'));
        $this->assertSame('user', $RdbaString->sanitizeUsername('user<name'));// removed <name by strig_tags.
        $this->assertSame('user', $RdbaString->sanitizeUsername('user<<<name'));// removed <<<name by strig_tags.
        $this->assertSame('username', $RdbaString->sanitizeUsername('user<><>name'));
        $this->assertSame('username', $RdbaString->sanitizeUsername('user<<>>name'));
        $this->assertSame('username', $RdbaString->sanitizeUsername('user>>name'));
        $this->assertSame('username', $RdbaString->sanitizeUsername('user@name'));
        $this->assertSame('username', $RdbaString->sanitizeUsername('user[@\[]]name'));
        $this->assertSame('username', $RdbaString->sanitizeUsername('user        "(),:;<>@[\]    [[[[]]]    name'));

        // no more than one dots at a time.
        $this->assertSame('user.name', $RdbaString->sanitizeUsername('user......name'));
        $this->assertSame('user.name.lastname', $RdbaString->sanitizeUsername('user.........name..lastname'));
        $this->assertSame('user.name.lastname', $RdbaString->sanitizeUsername('user.. ....    ..name.   .lastname'));
    }// testSanitizeUsername


    public function testStaticSanitizeUsername()
    {
        $this->assertSame('username', \Rdb\Modules\RdbAdmin\Libraries\RdbaString::staticSanitizeUsername(' user name '));
    }// testStaticSanitizeUsername


}
