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

    $DateTime = new \DateTime($gmtDatetime, new \DateTimeZone('UTC'));
    $DateTime->setTimezone(new \DateTimeZone($timezone));
    $timestamp = $DateTime->getTimestamp();

    $timezone = $DateTime->format('P');
    $format = str_replace(['%z', '%Z'], $timezone, $format);
    unset($timezone);

    return strftime($format, $timestamp);
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