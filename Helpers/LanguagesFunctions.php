<?php
/** 
 * Language functions.
 * 
 * To use these functions, it have to included "gettext/translator_functions.php" file via `Translator->register()` method..
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


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