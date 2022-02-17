<?php
/** 
 * Language functions.
 * 
 * To use these functions, it have to included "gettext/translator_functions.php" file via `Translator->register()` method..
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


if (!function_exists('__')) {
    /**
     * Translates a string.
     *
     * @param string $msgid String to be translated
     * @return string translated string (or original, if not found)
     */
    function __(string $msgid): string
    {
        /* @var $Languages \Rdb\Modules\RdbAdmin\Libraries\Languages */
        $Languages = \Rdb\Modules\RdbAdmin\Libraries\Languages::$staticThis;
        return $Languages->getTranslator()->gettext($msgid);
    }
}


if (!function_exists('d__')) {
    /**
     * Translates a string.
     *
     * @param string $domain Domain to use
     * @param string $msgid  String to be translated
     * @return string translated string (or original, if not found)
     */
    function d__(string $domain, string $msgid): string
    {
        /* @var $Languages \Rdb\Modules\RdbAdmin\Libraries\Languages */
        $Languages = \Rdb\Modules\RdbAdmin\Libraries\Languages::$staticThis;
        return $Languages->getTranslator($domain)->gettext(
            $msgid
        );
    }
}


if (!function_exists('dn__')) {
    /**
     * Plural version of gettext.
     *
     * @param string $domain      Domain to use
     * @param string $msgid       Single form
     * @param string $msgidPlural Plural form
     * @param int    $number      Number of objects
     * @return string translated plural form
     */
    function dn__(string $domain, string $msgid, string $msgidPlural, int $number): string
    {
        /* @var $Languages \Rdb\Modules\RdbAdmin\Libraries\Languages */
        $Languages = \Rdb\Modules\RdbAdmin\Libraries\Languages::$staticThis;
        return $Languages->getTranslator($domain)->ngettext(
            $msgid,
            $msgidPlural,
            $number
        );
    }
}


if (!function_exists('dnp__')) {
    /**
     * Plural version of pgettext.
     *
     * @param string $domain      Domain to use
     * @param string $msgctxt     Context
     * @param string $msgid       Single form
     * @param string $msgidPlural Plural form
     * @param int    $number      Number of objects
     * @return string translated plural form
     */
    function dnp__(string $domain, string $msgctxt, string $msgid, string $msgidPlural, int $number): string
    {
        /* @var $Languages \Rdb\Modules\RdbAdmin\Libraries\Languages */
        $Languages = \Rdb\Modules\RdbAdmin\Libraries\Languages::$staticThis;
        return $Languages->getTranslator($domain)->npgettext(
            $msgctxt,
            $msgid,
            $msgidPlural,
            $number
        );
    }
}


if (!function_exists('p__')) {
    /**
     * Translate with context.
     *
     * @param string $msgctxt Context
     * @param string $msgid   String to be translated
     *
     * @return string translated plural form
     */
    function p__(string $msgctxt, string $msgid): string
    {
        /* @var $Languages \Rdb\Modules\RdbAdmin\Libraries\Languages */
        $Languages = \Rdb\Modules\RdbAdmin\Libraries\Languages::$staticThis;
        return $Languages->getTranslator()->pgettext(
            $msgctxt,
            $msgid
        );
    }
}


if (!function_exists('dp__')) {
    /**
     * Translate with context.
     *
     * @param string $domain  Domain to use
     * @param string $msgctxt Context
     * @param string $msgid   String to be translated
     * @return string translated plural form
     */
    function dp__(string $domain, string $msgctxt, string $msgid): string
    {
        /* @var $Languages \Rdb\Modules\RdbAdmin\Libraries\Languages */
        $Languages = \Rdb\Modules\RdbAdmin\Libraries\Languages::$staticThis;
        return $Languages->getTranslator($domain)->pgettext(
            $msgctxt,
            $msgid
        );
    }
}


if (!function_exists('n__')) {
    /**
     * Plural version of gettext.
     *
     * @param string $msgid       Single form
     * @param string $msgidPlural Plural form
     * @param int    $number      Number of objects
     * @return string translated plural form
     */
    function n__(string $msgid, string $msgidPlural, int $number): string
    {
        /* @var $Languages \Rdb\Modules\RdbAdmin\Libraries\Languages */
        $Languages = \Rdb\Modules\RdbAdmin\Libraries\Languages::$staticThis;
        return $Languages->getTranslator()->ngettext(
            $msgid,
            $msgidPlural,
            $number
        );
    }
}


if (!function_exists('np__')) {
    /**
     * Plural version of pgettext.
     *
     * @param string $msgctxt     Context
     * @param string $msgid       Single form
     * @param string $msgidPlural Plural form
     * @param int    $number      Number of objects
     * @return string translated plural form
     */
    function np__(string $msgctxt, string $msgid, string $msgidPlural, int $number): string
    {
        /* @var $Languages \Rdb\Modules\RdbAdmin\Libraries\Languages */
        $Languages = \Rdb\Modules\RdbAdmin\Libraries\Languages::$staticThis;
        return $Languages->getTranslator()->npgettext(
            $msgctxt,
            $msgid,
            $msgidPlural,
            $number
        );
    }
}


/**
 * Get translation text from `__()` function and then escape using `htmlspecialchars()` with `ENT_QUOTES` flag.
 * 
 * @param string $original The message to translate.
 * @return string Return translated text with escaped html.
 */
function esc__(string $original): string
{
    $result = call_user_func_array('__', func_get_args());
    return htmlspecialchars($result, ENT_QUOTES);
}// esc__


/**
 * Get translation text from `d__()` function and then escape using `htmlspecialchars()` with `ENT_QUOTES` flag.
 * 
 * @param string $domain The text domain.
 * @param string $original The message to translate.
 * @return string Return translated text with escaped html.
 */
function esc_d__(string $domain, string $original): string
{
    $result = call_user_func_array('d__', func_get_args());
    return htmlspecialchars($result, ENT_QUOTES);
}// esc_d__


/**
 * Get translation text from `dn__()` function and then escape using `htmlspecialchars()` with `ENT_QUOTES` flag.
 * 
 * @param string $domain The text domain.
 * @param string $original The singular message.
 * @param string $plural The plural message.
 * @param float|int|string $value The number (e.g. item count) to determine the translation for the respective grammatical number.
 * @return string Return translated text with escaped html.
 */
function esc_dn__(string $domain, string $original, string $plural, $value): string
{
    $result = call_user_func_array('dn__', func_get_args());
    return htmlspecialchars($result, ENT_QUOTES);
}// esc_dn__


/**
 * Get translation text from `dnp__()` function and then escape using `htmlspecialchars()` with `ENT_QUOTES` flag.
 * 
 * @param string $domain The text domain.
 * @param string $context The context message to describe what is this message about.
 * @param string $original The singular message.
 * @param string $plural The plural message.
 * @param float|int|string $value The number (e.g. item count) to determine the translation for the respective grammatical number.
 * @return string Return translated text with escaped html.
 */
function esc_dnp__(string $domain, string $context, string $original, string $plural, $value): string
{
    $result = call_user_func_array('dnp__', func_get_args());
    return htmlspecialchars($result, ENT_QUOTES);
}// esc_dnp__


/**
 * Get translation text from `dp__()` function and then escape using `htmlspecialchars()` with `ENT_QUOTES` flag.
 * 
 * @param string $domain The text domain.
 * @param string $context The context message to describe what is this message about.
 * @param string $original The singular message.
 * @return string Return translated text with escaped html.
 */
function esc_dp__(string $domain, string $context, string $original): string
{
    $result = call_user_func_array('dp__', func_get_args());
    return htmlspecialchars($result, ENT_QUOTES);
}// esc_dp__


/**
 * Get translation text from `n__()` function and then escape using `htmlspecialchars()` with `ENT_QUOTES` flag.
 * 
 * @param string $original The singular message.
 * @param string $plural The plural message.
 * @param float|int|string $value The number (e.g. item count) to determine the translation for the respective grammatical number.
 * @return string Return translated text with escaped html.
 */
function esc_n__(string $original, string $plural, $value): string
{
    $result = call_user_func_array('n__', func_get_args());
    return htmlspecialchars($result, ENT_QUOTES);
}// esc_n__


/**
 * Get translation text from `np__()` function and then escape using `htmlspecialchars()` with `ENT_QUOTES` flag.
 * 
 * @param string $context The context message to describe what is this message about.
 * @param string $original The singular message.
 * @param string $plural The plural message.
 * @param float|int|string $value The number (e.g. item count) to determine the translation for the respective grammatical number.
 * @return string Return translated text with escaped html.
 */
function esc_np__(string $context, string $original, string $plural, $value): string
{
    $result = call_user_func_array('np__', func_get_args());
    return htmlspecialchars($result, ENT_QUOTES);
}// esc_np__


/**
 * Get translation text from `p__()` function and then escape using `htmlspecialchars()` with `ENT_QUOTES` flag.
 * 
 * @param string $context The context message to describe what is this message about.
 * @param string $original The singular message.
 * @return string Return translated text with escaped html.
 */
function esc_p__(string $context, string $original): string
{
    $result = call_user_func_array('p__', func_get_args());
    return htmlspecialchars($result, ENT_QUOTES);
}// esc_p__


/**
 * Noop, marks the string for translation but returns it unchanged.
 *
 * @param string $original
 * @return string
 */
function noop__($original)
{
    return $original;
}