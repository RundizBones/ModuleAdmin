<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Tests\Libraries;


class LanguagesTest extends \Tests\Rdb\BaseTestCase
{


    /**
     * @var Modules\RdbAdmin\Tests\Libraries\LanguagesExtended
     */
    protected $Languages;


    public function setup()
    {
        $_SERVER['RUNDIZBONES_LANGUAGE'] = 'th';

        $this->Languages = new LanguagesExtended(new \System\Container());
    }// setup


    public function testBindTextDomain()
    {
        $this->Languages->bindTextDomain('textdomain', __DIR__);

        $this->assertEquals('สวัสดี', __('Hello'));
        $this->assertEquals('สวัสดี', d__('textdomain', 'Hello'));
        $this->assertEquals('textdomain', $this->Languages->currentTextDomain);

        $this->Languages->bindTextDomain('otherdomain', __DIR__);// not exists translation.
        $this->assertEquals('textdomain', $this->Languages->currentTextDomain);// no change because not found translation file.

        $this->Languages->bindTextDomain('textdomain2', __DIR__);// existing translation.
        $this->assertEquals('textdomain2', $this->Languages->currentTextDomain);
    }// testBindTextDomain


    public function testGetTranslator()
    {
        $this->assertInstanceOf(\Gettext\Translator::class, $this->Languages->getTranslator());
    }// testGetTranslator


    public function testRegisterTextDomain()
    {
        $this->Languages->registerTextDomain('textdomain', __DIR__);
        $this->Languages->registerTextDomain('otherdomain', __DIR__);

        $this->assertArrayHasKey('textdomain', $this->Languages->registeredTextDomains);
        $this->assertArrayHasKey('otherdomain', $this->Languages->registeredTextDomains);
    }// testRegisterTextDomain


    public function testTranslatorFunctions()
    {
        $this->Languages->bindTextDomain('textdomain', __DIR__);

        $this->assertEquals('สวัสดี', __('Hello'));
        $this->assertEquals('12 ชิ้น', n__('%d piece', '%d pieces', 12, 12));
        $this->assertEquals('Hello', noop__('Hello'));
        $this->assertEquals('3 ชั้น', np__('(tests) number of floor', '%d story', '%d stories', 3, 3));
        $this->assertEquals('อ้วน', p__('(tests) big', 'Fat'));
        $this->assertEquals('ไขมัน', p__('(tests) oily', 'Fat'));

        // test with d- functions
        $this->assertEquals('สวัสดี', d__('textdomain', 'Hello'));
        $this->assertEquals('12 ชิ้น', dn__('textdomain', '%d piece', '%d pieces', 12, 12));
        $this->assertEquals('3 ชั้น', dnp__('textdomain', '(tests) number of floor', '%d story', '%d stories', 3, 3));
        $this->assertEquals('อ้วน', dp__('textdomain', '(tests) big', 'Fat'));
        $this->assertEquals('ไขมัน', dp__('textdomain', '(tests) oily', 'Fat'));

        $this->Languages->getHelpers();
        $this->assertEquals('&gt; 3 floors', esc__('> 3 floors'));
        $this->assertEquals('&gt; 2 floors', esc__('> %d floors', 2));
        $this->assertEquals('&gt; %d floor', esc_n__('> %d floor', '> %d floors', 1));
        $this->assertEquals('&gt; 1 floor', esc_n__('> %d floor', '> %d floors', 1, 1));
        $this->assertEquals('&gt; 13 floors', esc_n__('> %d floor', '> %d floors', 13, 13));
        $this->assertEquals('&gt; 23 floors', esc_np__('(tests) number of floor', '> %d floor', '> %d floors', 23, 23));
        $this->assertEquals('&lt;%s&gt; is html', esc_p__('(tests) describe html tag', '<%s> is html'));
        $this->assertEquals('&lt;div&gt; is html', esc_p__('(tests) describe html tag', '<%s> is html', 'div'));

        // test with d- functions
        $this->assertEquals('A &lt;&gt; B', esc_d__('textdomain', 'A <> B'));
        $this->assertEquals('A &lt;&gt; B', esc_d__('textdomain', 'A <> %s', 'B'));
        $this->assertEquals('&gt; %d piece', esc_dn__('textdomain', '> %d piece', '> %d pieces', 1));
        $this->assertEquals('&gt; %d pieces', esc_dn__('textdomain', '> %d piece', '> %d pieces', 2));
        $this->assertEquals('&gt; 4 pieces', esc_dn__('textdomain', '> %d piece', '> %d pieces', 4, 4));
        $this->assertEquals('&gt; 4 pieces', esc_dnp__('textdomain', '(tests) number items', '> %d piece', '> %d pieces', 4, 4));
        $this->assertEquals('&lt;strong&gt; is html', esc_dp__('textdomain', '(tests) describe html tag', '<%s> is html', 'strong'));
    }// testTranslatorFunctions


}
