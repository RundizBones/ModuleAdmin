<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin;


/**
 * Captcha page controller.
 * 
 * @since 0.1
 */
class CaptchaController extends \Rdb\System\Core\Controllers\BaseController
{


    /**
     * Get audio sound content.
     */
    public function audioAction()
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_id() === '') {
            session_start();
        }
        $Securimage = new \Securimage();
        $Securimage->namespace = 'RdbCaptcha';
        $Securimage->audio_gap_max = 700;
        $Securimage->audio_use_noise = true;
        $Securimage->degrade_audio = true;

        // display, response part ---------------------------------------------------------------------------------------------
        $Securimage->outputAudioFile(null);
        unset($Securimage);
    }// audioAction


    /**
     * Do check captcha code.
     * 
     * This method can be called from other controllers.
     * 
     * @param string $input The input code.
     * @return bool Return `true` if the input code was correct, `false` if not.
     */
    public function doCheckCaptcha(string $input): bool
    {
        if (session_id() === '') {
            session_start();
        }
        $Securimage = new \Securimage();
        $Securimage->namespace = 'RdbCaptcha';

        return $Securimage->check($input);
    }// doCheckCaptcha


    /**
     * Get captcha image content.
     */
    public function imageAction()
    {
        // processing part ----------------------------------------------------------------------------------------------------
        if (session_id() === '') {
            session_start();
        }
        $Securimage = new \Securimage();
        $Securimage->namespace = 'RdbCaptcha';
        // set characters. remove ambiguous refer from https://github.com/Rundiz/serial-number-generator/blob/master/Rundiz/SerialNumberGenerator/SerialNumberGenerator.php#L70
        // 0 ambiguous with O
        // 1 ambiguous with I J L T
        // 2 ambiguous with Z
        // 5 ambiguous with S
        // 8 ambiguous with B
        // U ambiguous with V
        // Before add or change anything, make sure that audio is supported.
        $Securimage->charset = '0123456789ACDEFGHKMNPQRUWXY';
        $Securimage->code_length = mt_rand(4, 6);
        $Securimage->image_width = 400;
        $Securimage->image_height = ($Securimage->image_width * 0.35);
        $Securimage->line_color = new \Securimage_Color('#999999');
        $Securimage->noise_color = new \Securimage_Color('#999999');
        $Securimage->noise_level = 10;
        $Securimage->num_lines = 9;
        $Securimage->perturbation = 0.95;
        $Securimage->text_transparency_percentage = 30;
        $Securimage->use_text_angles = true;

        // display, response part ---------------------------------------------------------------------------------------------
        $Securimage->show();
        unset($Securimage);
    }// imageAction


}
