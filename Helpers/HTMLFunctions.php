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
 * @param string $format Date/time format for use in `strftime()`.
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
    unset($DateTime);

    // @todo [rdb] Remove process below and use pattern (format) for class `\IntlDateFormatter()` instead in v2.0.
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
    $pattern = $format;

    // replace 1 single quote that is not following visible character or single quote and not follow by single quote or word or number.
    // example: '
    // replace with 2 single quotes. example: ''
    $pattern = preg_replace('/(?<![\'\S])(\')(?![\'\w])/u', "'$1", $pattern);
    // replace 1 single quote that is not following visible character or single quote and follow by word.
    // example: 'xx
    // replace with 2 single quotes. example: ''xx
    $pattern = preg_replace('/(?<![\'\S])(\')(\w+)/u', "'$1$2", $pattern);
    // replace 1 single quote that is following word (a-z 0-9) and not follow by single quote.
    // example: xx'
    // replace with 2 single quotes. example: xx''
    $pattern = preg_replace('/([\w]+)(\')(?!\')/u', "$1'$2", $pattern);
    // replace a-z (include upper case) that is not following %. example xxx.
    // replace with wrap single quote. example: 'xxx'.
    $pattern = preg_replace('/(?<![%a-zA-Z])([a-zA-Z]+)/u', "'$1$2'", $pattern);

    // escape %%x with '%%x'.
    $pattern = preg_replace('/(%%[a-zA-Z]+)/u', "'$1'", $pattern);

    foreach ($replaces as $strftime => $intl) {
        $pattern = preg_replace('/(?<!%)(' . $strftime . ')/u', $intl, $pattern);
    }// endforeach;
    unset($intl, $strftime);

    // use `\IntlDateFormatter`instead of `strftime()` that is deprecated since PHP 8.1
    // Do not use `\IntlDateFormatter::TRADITIONAL` to prevent some mistake where Buddhist era that is +543 years.
    // This may affect on some process that use this function to get date/time for processing. Previous code also not convert the year.
    try {
        $IntlDateFormatter = new \IntlDateFormatter($locale, \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, $timezone);
        $IntlDateFormatter->setPattern($pattern);
    } catch (\Exception|\Error $err) {
        // in this case the server might not set locales that supported, so `setlocale()` will return something very wrong and cause error.
        // let's try again.
        try {
            // try again with configuration directly by get the first locale value.
            $localesString = json_decode(($_SERVER['RUNDIZBONES_LANGUAGE_LOCALE'] ?? '[]'));
            $IntlDateFormatter = new \IntlDateFormatter($localesString[0], \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, $timezone);
            $IntlDateFormatter->setPattern($pattern);
            unset($localesString);
        } catch (\Exception|\Error $err) {
            // in this case, it means there is problem with configuration. developers need attention about this.
            error_log($err);
            $IntlDateFormatter = new \IntlDateFormatter('en', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, $timezone);
            $IntlDateFormatter->setPattern($pattern);
        }// endtry;
    }// endtry;
    unset($pattern);
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


/**
 * Render favicon HTML.
 * 
 * @link https://stackoverflow.com/questions/48956465/favicon-standard-2022-svg-ico-png-and-dimensions Reference.
 * @since 1.2.4
 * @param string $faviconPath
 * @return string
 */
function renderFaviconHtml(\Rdb\System\Container $Container, string $faviconPath): string
{
    if (empty($faviconPath)) {
        return '';
    }

    $Cache = (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
        $Container, 
        [
            'cachePath' => STORAGE_PATH . '/cache/Modules/RdbAdmin/Helpers/HTMLFunctions/' . __FUNCTION__,
        ]
    ))->getCacheObject();
    $cacheKey = 'faviconHTML_' . $faviconPath;
    $cacheExpire = (15 * 24 * 60 * 60);// 15 days

    if ($Cache->has($cacheKey)) {
        unset($cacheExpire);
        return $Cache->get($cacheKey)
            . '        <!--cached favicon HTML-->' . PHP_EOL;
    } else {
        $output = '';
        $Url = new \Rdb\System\Libraries\Url($Container);
        $fullPublicUrl = $Url->getDomainProtocol();
        $publicUrl = $Url->getPublicUrl();
        if ($publicUrl !== '') {
            $fullPublicUrl .= '/' . $publicUrl;
        }
        unset($publicUrl, $Url);

        $fileExtension = pathinfo($faviconPath, PATHINFO_EXTENSION);
        $faviconPathNoExt = preg_replace('/.' . $fileExtension . '$/iu', '', $faviconPath);
        $publicPath = str_replace(['\\', DIRECTORY_SEPARATOR], '/', PUBLIC_PATH);
        $filesSearch = glob(
             $publicPath . '/' . $faviconPathNoExt . '*.' . $fileExtension
        );
        if (is_array($filesSearch)) {
            asort($filesSearch, SORT_NATURAL);
            foreach ($filesSearch as $eachFile) {
                if (is_file($eachFile)) {
                    $fileNameOnly = pathinfo($eachFile, PATHINFO_FILENAME);// no extension and path, just file name.
                    $fileNameExp = explode('_', $fileNameOnly);
                    $fileNameNumberWidthHeight = $fileNameExp[(count($fileNameExp) - 1)];
                    if (
                        is_scalar($fileNameNumberWidthHeight) &&
                        str_contains($fileNameNumberWidthHeight, 'x')
                    ) {
                        // found nnxnn in file name.
                        $numberExp = explode('x', $fileNameNumberWidthHeight);
                        if (
                            isset($numberExp[0]) && 
                            isset($numberExp[1]) && 
                            is_numeric($numberExp[0]) && 
                            is_numeric($numberExp[1])
                        ) {
                            // if found number in file name.
                            $finfo = new finfo();
                            $mimeType = $finfo->file($eachFile, FILEINFO_MIME_TYPE);
                            unset($finfo);
                            $faviconRelPath = str_replace($publicPath . '/', '', $eachFile);

                            if ($numberExp[0] <= 48 || $numberExp[0] == 192) {
                                $output .= str_repeat(' ', 8);
                                $output .= '<link rel="icon" type="' . $mimeType . '" sizes="' . $fileNameNumberWidthHeight . '" href="' . $fullPublicUrl . '/' . $faviconRelPath . '">' . PHP_EOL;
                            } elseif ($numberExp[0] == 180) {
                                $output .= str_repeat(' ', 8);
                                $output .= '<link rel="apple-touch-icon" sizes="' . $fileNameNumberWidthHeight . '" href="' . $fullPublicUrl . '/' . $faviconRelPath . '">' . PHP_EOL;
                            } elseif ($numberExp[0] == 270) {
                                $output .= str_repeat(' ', 8);
                                $output .= '<meta name="msapplication-TileImage" content="' . $fullPublicUrl . '/' . $faviconRelPath . '" />' . PHP_EOL;
                            }

                            unset($faviconRelPath, $mimeType);
                        }
                        unset($numberExp);
                    }
                    unset($fileNameExp, $fileNameOnly, $fileNameNumberWidthHeight);
                }
            }// endforeach;
            unset($eachFile);
        }
        unset($fileExtension, $faviconPathNoExt, $filesSearch, $fullPublicUrl, $publicPath);

        $Cache->set($cacheKey, $output, $cacheExpire);
        unset($Cache, $cacheExpire, $cacheKey);
        return $output;
    }
}// renderFaviconHtml