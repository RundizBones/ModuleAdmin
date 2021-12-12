<?php
/** 
 * HTML functions.
 * 
 * To use these functions, you must include/require it.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


/**
 * Get date/time value from UTC date/time to specific time zone.
 * 
 * @param string $gmtDatetime The GMT or UTC date/time.
 * @param string $timezone PHP time zone value.
 * @param string $format Date/time format for use in `strftime()` function except `%z` or `%Z` that will be always the same time zone value.
 * @return string Return formatted date/time to specific time zone.
 */
function rdbaGetDatetime(string $gmtDatetime, string $timezone = '', string $format = '%e %B %Y %H:%M:%S %z'): string
{
    if (empty(trim($timezone))) {
        return $gmtDatetime;
    }

    // get the locale that is already set in System/Middleware/I18n.php
    $locale = setlocale(LC_ALL, 0);

    $DateTime = new \DateTime($gmtDatetime, new \DateTimeZone('UTC'));
    $DateTime->setTimezone(new \DateTimeZone($timezone));
    $timestamp = $DateTime->getTimestamp();

    // @todo [rdb] Remove process below and use pattern (format) for class `\IntlDateFormatter()` instead in v2.0.
    $formattedTimezone = $DateTime->format('P');
    $format = str_replace(['%z', '%Z'], "'" . $formattedTimezone . "'", $format);
    unset($DateTime, $formattedTimezone);

    $replaces = [
        '%a' => 'E',
        '%A' => 'EEEE',
        '%d' => 'dd',
        '%e' => 'd',
        '%j' => 'D',
        '%u' => 'e',// not 100% correct
        '%w' => 'c',// not 100% correct
        '%U' => 'w',
        '%V' => 'ww',// not 100% correct
        '%W' => 'w',// not 100% correct
        '%b' => 'MMM',
        '%B' => 'MMMM',
        '%h' => 'MMM',// alias of %b
        '%m' => 'MM',
        '%C' => 'yy',// no replace for this
        '%g' => 'yy',// no replace for this
        '%G' => 'Y',// not 100% correct
        '%y' => 'yy',
        '%Y' => 'yyyy',
        '%H' => 'HH',
        '%k' => 'H',
        '%I' => 'hh',
        '%l' => 'h',
        '%M' => 'mm',
        '%p' => 'a',
        '%P' => 'a',// no replace for this
        '%r' => 'hh:mm:ss a',
        '%R' => 'HH:mm',
        '%S' => 'ss',
        '%T' => 'HH:mm:ss',
        '%X' => 'HH:mm:ss',// no replace for this
        '%z' => 'ZZ',
        '%Z' => 'v',// no replace for this
        '%c' => 'd/M/YYYY HH:mm:ss',// Buddhist era not converted.
        '%D' => 'MM/dd/yy',
        '%F' => 'yyyy-MM-dd',
        '%s' => '',// no replace for this
        '%x' => 'd/MM/yyyy',// Buddhist era not converted.
        '%n' => "\n",
        '%t' => "\t",
        '%%' => '%',
    ];
    $intlDpattern = preg_replace('/(%%[a-zA-Z])/u', "'$1'", $format);// escape %%x with '%%x'.
    foreach ($replaces as $strftime => $intl) {
        $intlDpattern = preg_replace('/(?<!%)(' . $strftime . ')/u', $intl, $intlDpattern);
    }// endforeach;
    unset($intl, $strftime);

    // use `\IntlDateFormatter`instead of `strftime()` that is deprecated since PHP 8.1
    // Do not use `\IntlDateFormatter::TRADITIONAL` to prevent some mistake where Buddhist era that is +543 years.
    // This may affect on some process that use this function to get date/time for processing. Previous code also not convert the year.
    $IntlDateFormatter = new \IntlDateFormatter($locale, \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, $timezone);
    $IntlDateFormatter->setPattern($intlDpattern);
    unset($intlDpattern);
    return $IntlDateFormatter->format($timestamp);
}// rdbaGetDatetime


/**
 * Render RDTA alert box.
 * 
 * @param string|array $content The alert content.
 * @param string $alertClass RDTA alert class. Accept 'success', 'error', 'info' anything else will be 'alert-warning'.
 * @param bool $dismissable Set to `true` to make it dismissable, `false` to unable to dismiss.
 * @return string Return rendered RDTA alert element.
 */
function renderAlertHtml($content, string $alertClass = '', bool $dismissable = true): string
{
    if (is_array($content)) {
        $newContent = '<ul class="rd-alert-list">';
        foreach ($content as $eachMessage) {
            if (is_scalar($eachMessage)) {
                $newContent .= '<li>' . $eachMessage . '</li>';
            }
        }// endforeach;
        unset($eachMessage);
        $newContent .= '</ul>';

        $content = $newContent;
        unset($newContent);
    }

    $alertClass = strtolower($alertClass);
    if ($alertClass === 'success' || $alertClass === 'alert-success') {
        $alertClass = 'alert-success';
    } else if ($alertClass === 'error' || $alertClass === 'alert-danger') {
        $alertClass = 'alert-danger';
    } else if ($alertClass === 'info' || $alertClass === 'alert-info') {
        $alertClass = 'alert-info';
    } else {
        $alertClass = 'alert-warning';
    }

    if (function_exists('esc__')) {
        $closeMessage = esc__('Close');
    } else {
        $closeMessage = 'Close';
    }

    if ($dismissable === true) {
        return '<div class="rd-alertbox ' . $alertClass . ' is-dismissable">' .
            '<button class="close" type="button" aria-label="' . $closeMessage . '" onclick="return RundizTemplateAdmin.closeAlertbox(this);"><span aria-hidden="true">&times;</span></button>' .
            (is_scalar($content) ? $content : '') .
            '</div>';
    } else {
        return '<div class="rd-alertbox ' . $alertClass . '">' .
            (is_scalar($content) ? $content : '') .
            '</div>';
    }
}// renderAlertHtml


/**
 * Render breadcrumb list without `<ul>` and `</ul>` due to it is already in **mainLayout_v.php** file.
 * 
 * @param array $breadcrumb The breadcrumb array. The keys are:
 *                          `item` Text of each link.<br>
 *                          `link` Link of each breadcrumb.
 * @return string
 */
function renderBreadcrumbHtml(array $breadcrumb): string
{
    $output = '';

    if (!empty($breadcrumb)) {
        $i = 1;
        $total = count($breadcrumb);
        foreach ($breadcrumb as $item) {
            $output .= '<li';
            if ($i == $total) {
                $output .= ' class="current"';
            }
            $output .= '>';
            if (array_key_exists('link', $item)) {
                $output .= '<a href="' . $item['link'] . '">';
            }
            $output .= ($item['item'] ?? '');
            if (array_key_exists('link', $item)) {
                $output .= '</a>';
            }
            $output .= '</li>';
            $i++;
        }// endforeach;
        unset($i, $item, $total);
    }

    return $output;
}// renderBreadcrumbHtml