<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Gravatar class.
 * 
 * @since 0.2.1
 */
class Gravatar
{


    /**
     * @var string Gravatar URL base with trailing slash.
     */
    public $gravatarUrlBase = 'https://www.gravatar.com/avatar/';


    /**
     * Get Gravatar image tag with URL or just URL.
     * 
     * @link https://th.gravatar.com/site/implement/images/ Gravatar reference.
     * @link https://th.gravatar.com/site/implement/images/php/ Original source code.
     * @param string $email The email address of target Gravatar.
     * @param int $size Gravatar size.
     * @param string $default Default image. Accept: 404, mp, identicon, monsterid, wavatar, retro, robohash, blank.
     * @param string $rating Rating. Accept: g, pg, r, x.
     * @param bool $htmlImg Set to `true` to return `&lt;img&gt;` tag. Default is `false` means it will be return only URL.
     * @param array $htmlImgAttributes The associative array of `img` attributes.
     * @return string Return URL or `img` tag with URL.
     */
    public function getImage(string $email, int $size = 80, string $default = 'mp', string $rating = 'g', bool $htmlImg = false, array $htmlImgAttributes = []): string
    {
        $url = $this->gravatarUrlBase;
        $url .= md5(strtolower(trim($email)));
        $url .= '?s=' . $size . '&d=' . rawurlencode(strip_tags($default)) . '&r=' . rawurlencode(strip_tags($rating));

        if ($htmlImg === true) {
            unset($htmlImgAttributes['src']);
            $url = '<img src="' . $url . '"';
            foreach ($htmlImgAttributes as $name => $value) {
                $url .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
            }// endforeach;
            unset($name, $value);
            $url .= '>';
        }

        return $url;
    }// getImage



}
