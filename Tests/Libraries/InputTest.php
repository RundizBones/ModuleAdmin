<?php


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


use Rdb\Modules\RdbAdmin\Tests\PHPUnitFunctions\Arrays;
use Rdb\Modules\RdbAdmin\Libraries\RdbaString;


class InputTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\Modules\RdbAdmin\Libraries\Input
     */
    protected $Input;


    public function setup(): void
    {
        // reset everything first.
        // due to $_POST contain array with `[]` which is auto increasing its key value everytime this `setup()` was called.
        $_POST = [];
        $_GET = [];
        $_COOKIE = [];
        $_SESSION = [];

        $_POST['posthtml'] = 'name<div class="myclass">in-div</div>';
        $_POST['posthtmlquote'] = 'name<div class="myclass">in-div<span class=\'quote\'>I\'m single quote.</span></div>';
        $_POST['posturl'] = 'http://mydomain.tld';
        $_POST['postemail'] = 'me@gmail.com';
        $_POST['postinvalidemail'] = '<me-with-lessthan-sign>@gmail.com';
        $_POST['requestname'] = 'requestval-via-post';
        $_POST['inputarray']['a'] = 'email1@domain.com';
        $_POST['inputarray']['b'] = '<email2>@domain.com';
        $_POST['inputnumstring'] = '10';
        $_POST['inputarraynumstring']['a'] = '12,345';
        $_POST['inputarraynumstring']['b'] = '2,345';
        $_POST['inputarraynumstring']['c'] = 'asdf';
        $_POST['inputarraynumstring2'][] = '9,876';
        $_POST['inputarraynumstring2'][] = '5432';

        $Config = new \Rdb\System\Config();
        $_COOKIE['mycookie'.$Config->get('suffix', 'cookie')] = 'my cookie value';

        $_SESSION['mysession'] = 'my session value';
        $_SESSION['email'] = $_POST['postemail'];
        $_SESSION['invalidemail'] = $_POST['postinvalidemail'];

        $this->runApp('POST', '/?gethtml=<div class="myclass">div-element</div>&getstring=just text&getint=123&requestname=requestval-via-get', $_COOKIE, $_POST);

        $this->Input = new \Rdb\Modules\RdbAdmin\Libraries\Input();
    }// setup


    /**
     * @todo [rdb] Remove this test in v2.0
     */
    public function testFilterRegexp()
    {
        $this->assertSame('div class=myclassdiv-element/div', $this->Input->filterRegexp($this->Input->get('gethtml')));
        $this->assertSame('div-element', $this->Input->filterRegexp(RdbaString::staticFilterSanitizeString($this->Input->get('gethtml', ''))));

        $inputString = '0123456789 abcdefghijklmnopqrstuvwxyz ABCDEFGHIJKLMNOPQRSTUVWXYZ ~!@#$%^&*()_+`-=[]\\{}|;\':",./<>? กขคงจฉช À Ω һ Ջ ت ڹ ন ບ ᡚ ᴩ Ⅻ ✅ 㯹 ㇸ 𝘈 𞢖 𞤤 𞥖 𞸇 𞺨 🅗 🆗 🛕 🪕 🩰';
        $assertString = '0123456789 abcdefghijklmnopqrstuvwxyz ABCDEFGHIJKLMNOPQRSTUVWXYZ ~!@#$%^&*()_+`-=[]\\{}|;:,./? กขคงจฉช À Ω һ Ջ ت ڹ ন ບ ᡚ ᴩ Ⅻ ✅ 㯹 ㇸ 𝘈 𞢖 𞤤 𞥖 𞸇 𞺨 🅗 🆗 🛕 🪕 🩰';
        $this->assertSame($assertString, $this->Input->filterRegexp($inputString));
    }// testFilterRegexp


    public function testInputCookie()
    {
        $this->assertEquals('my cookie value', $this->Input->cookie('mycookie'));
    }// testInputCookie


    public function testInputGet()
    {
        $this->assertEquals('<div class="myclass">div-element</div>', $this->Input->get('gethtml'));
        $this->assertEquals('&lt;div class=&quot;myclass&quot;&gt;div-element&lt;/div&gt;', $this->Input->get('gethtml', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->assertEquals('div-element', RdbaString::staticFilterSanitizeString($this->Input->get('gethtml', null)));
        $this->assertEquals('just text', RdbaString::staticFilterSanitizeString($this->Input->get('getstring', null)));
        $this->assertEquals(123, $this->Input->get('getint', null, FILTER_SANITIZE_NUMBER_INT));
    }// testInputGet


    public function testInputIsNonHtmlAccept()
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $this->assertFalse($this->Input->isNonHtmlAccept());

        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $this->assertFalse($this->Input->isNonHtmlAccept());

        $_SERVER['HTTP_ACCEPT'] = 'application/xhtml+xml';
        $this->assertFalse($this->Input->isNonHtmlAccept());

        $_SERVER['HTTP_ACCEPT'] = 'application/xml';
        $this->assertTrue($this->Input->isNonHtmlAccept());

        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $this->assertTrue($this->Input->isNonHtmlAccept());
    }// testInputIsNonHtmlAccept


    public function testInputIsXhr()
    {
        $this->assertFalse($this->Input->isXhr());

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue($this->Input->isXhr());
    }// testInputIsXhr


    public function testInputPost()
    {
        $this->assertEquals('name<div class="myclass">in-div</div>', $this->Input->post('posthtml'));// use default FILTER_UNSAFE_RAW which is do nothing.
        $this->assertEquals(
            'name&lt;div class="myclass"&gt;in-div&lt;span class=\'quote\'&gt;I\'m single quote.&lt;/span&gt;&lt;/div&gt;', 
            $this->Input->post('posthtmlquote', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES)
        );// test with filter and its option.
        $this->assertEquals('name&lt;div class=&quot;myclass&quot;&gt;in-div&lt;/div&gt;', $this->Input->post('posthtml', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->assertEquals('http://mydomain.tld', $this->Input->post('posturl', null, FILTER_SANITIZE_URL));
        $this->assertEquals('me@gmail.com', $this->Input->post('postemail', null, FILTER_SANITIZE_EMAIL));
        $this->assertEquals('me-with-lessthan-sign@gmail.com', $this->Input->post('postinvalidemail', null, FILTER_SANITIZE_EMAIL));
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(['a' => 'email1@domain.com', 'b' => 'email2@domain.com'], $this->Input->post('inputarray', [], FILTER_SANITIZE_EMAIL)))
        );
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                [0 => 10], 
                $this->Input->post('inputnumstring', '', FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY, 'options' => ['min_range' => 1, 'max_range' => 10]])
            ))
        );// test with 'flags', 'options' in options argument.
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                ['a' => '12,345', 'b' => '2,345', 'c' => ''], 
                $this->Input->post('inputarraynumstring', '', FILTER_SANITIZE_NUMBER_FLOAT, ['flags' => FILTER_FLAG_ALLOW_THOUSAND])
            ))
        );// test with 'flags', 'options' in options argument.
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive(
                $_POST['inputarraynumstring2'],
                $this->Input->post('inputarraynumstring2', '', FILTER_SANITIZE_NUMBER_FLOAT, ['flags' => FILTER_FLAG_ALLOW_THOUSAND])
            ))
        );// test with invalid value that is array and one of its key is number. it will be return as it is in $_POST without filtered.
    }// testInputPost


    public function testInputRequest()
    {
        $this->assertEquals('requestval-via-get', $this->Input->request('requestname'));
    }// testInputRequest


    public function testInputServer()
    {
        $this->assertEquals('/?gethtml=<div class="myclass">div-element</div>&getstring=just text&getint=123&requestname=requestval-via-get', $this->Input->server('REQUEST_URI'));
        $this->assertEquals('POST', $this->Input->server('REQUEST_METHOD'));
    }// testInputServer


    public function testInputSession()
    {
        $this->assertEquals('my session value', $this->Input->session('mysession'));
        $this->assertEquals('me@gmail.com', $this->Input->session('email', null, FILTER_SANITIZE_EMAIL));
        $this->assertEquals('me-with-lessthan-sign@gmail.com', $this->Input->session('invalidemail', null, FILTER_SANITIZE_EMAIL));
    }// testInputSession


}