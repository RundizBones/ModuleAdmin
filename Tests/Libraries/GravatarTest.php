<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Libraries;


class GravatarTest extends \Rdb\Tests\BaseTestCase
{


    public function testGetImage()
    {
        $Gravatar = new \Rdb\Modules\RdbAdmin\Libraries\Gravatar();

        $this->assertSame('https://www.gravatar.com/avatar/923d10bc97028030e8e67e7db62658d1?s=80&d=mp&r=g', $Gravatar->getImage('someone@somewhere.com'));
        $this->assertSame('https://www.gravatar.com/avatar/923d10bc97028030e8e67e7db62658d1?s=200&d=mp&r=g', $Gravatar->getImage('someone@somewhere.com', 200));
        $this->assertSame('https://www.gravatar.com/avatar/923d10bc97028030e8e67e7db62658d1?s=120&d=retro&r=g', $Gravatar->getImage('someone@somewhere.com', 120, 'retro'));
        $this->assertSame('https://www.gravatar.com/avatar/923d10bc97028030e8e67e7db62658d1?s=190&d=wavatar&r=x', $Gravatar->getImage('someone@somewhere.com', 190, 'wavatar', 'x'));

        $this->assertSame('<img src="https://www.gravatar.com/avatar/923d10bc97028030e8e67e7db62658d1?s=80&d=mp&r=g" class="gravatar">', $Gravatar->getImage('someone@somewhere.com', 80, 'mp', 'g', true, ['class' => 'gravatar', 'src' => 'none']));
        $this->assertSame('<img src="https://www.gravatar.com/avatar/923d10bc97028030e8e67e7db62658d1?s=200&d=mp&r=g" class="gravatar">', $Gravatar->getImage('someone@somewhere.com', 200, 'mp', 'g', true, ['class' => 'gravatar']));

        unset($Gravatar);
    }// testGetImage


}
