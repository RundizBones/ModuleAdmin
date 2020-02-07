<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Input class.
 * 
 * @since 0.1
 */
class Input
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class. Only required for some method.
     */
    public function __construct(\Rdb\System\Container $Container = null)
    {
        if ($Container instanceof \Rdb\System\Container) {
            $this->Container = $Container;
        }
    }// __construct


    /**
     * Get cookie value.
     * 
     * To use this method correctly, the class constructor must contain the `Container` and `Config` class in it.
     * 
     * For the `$filter` and `$options` argument, please read more details at http://php.net/manual/en/function.filter-var.php page.
     * 
     * @param string $name The input name (cookie name) without suffix. The suffix will be automatically added.
     * @param mixed $default Default value to return if the input name is not exists.
     * @param int $filter The ID of the filter to apply. See types of filters at http://php.net/manual/en/filter.filters.php page.
     * @param mixed $options Associative array of options or bitwise disjunction of flags.
     * @return mixed Return filtered input value.
     */
    public function cookie(string $name, $default = '', $filter = FILTER_UNSAFE_RAW, $options = null)
    {
        /* @var $Config \Rdb\System\Config */
        if ($this->Container instanceof \Rdb\System\Container && $this->Container->has('Config')) {
            $Config = $this->Container->get('Config');
            $Config->setModule('');
        } else {
            $Config = new \Rdb\System\Config();
        }

        return $this->inputParams('COOKIE', $name . $Config->get('suffix', 'cookie'), $default, $filter, $options);
    }// cookie


    /**
     * Get input via DELETE method.
     * 
     * For the `$filter` and `$options` argument, please read more details at http://php.net/manual/en/function.filter-var.php page.
     * 
     * @param string $name The input name.
     * @param mixed $default Default value to return if the input name is not exists.
     * @param int $filter The ID of the filter to apply. See types of filters at http://php.net/manual/en/filter.filters.php page.
     * @param mixed $options Associative array of options or bitwise disjunction of flags.
     * @return mixed Return filtered input value.
     */
    public function delete(string $name, $default = '', $filter = FILTER_UNSAFE_RAW, $options = null)
    {
        global $_DELETE;
        parse_str(file_get_contents('php://input'), $_DELETE);

        return $this->inputParams('DELETE', $name, $default, $filter, $options);
    }// delete


    /**
     * Filter data for inputs.
     * 
     * @link http://php.net/manual/en/function.filter-var.php Read more details.
     * @param mixed $variable Value to filter.
     * @param int $filter The ID of the filter to apply. See types of filters at http://php.net/manual/en/filter.filters.php page.
     * @param mixed $options Associative array of options or bitwise disjunction of flags.
     * @return mixed Returns the filtered data, or false if the filter fails.
     */
    protected function filter($variable, $filter = FILTER_UNSAFE_RAW, $options = null)
    {
        if (is_array($variable)) {
            if (is_array($options)) {
                $optionsForVar = array_merge(['filter' => $filter], $options);
                $options = [];

                foreach ($variable as $key => $item) {
                    if (is_numeric($key)) {
                        // if numeric key, it is not allowed by filter_var_array.
                        // don't do anything.
                        return $variable;
                    }
                    $options[$key] = $optionsForVar;
                }

                unset($item, $key);
            } else {
                $options = $filter;
            }

            return filter_var_array($variable, $options);
        } elseif (is_scalar($variable)) {
            if ($options !== null) {
                return filter_var($variable, $filter, $options);
            } else {
                return filter_var($variable, $filter);
            }
        }

        return $variable;
    }// filter


    /**
     * Filter data with regular expression.
     * 
     * @link https://stackoverflow.com/questions/26458654/regular-expressions-for-a-range-of-unicode-points-php Original source code for wide range of unicode support.
     * @link https://www.php.net/manual/en/function.preg-replace.php See more description about variable, pattern, replacement, and return value.
     * @param mixed $variable The string or an array with strings to search and replace.
     * @param mixed $pattern The pattern to search for. It can be either a string or an array with strings.
     * @param mixed $replacement The string or an array with strings to replace.
     * @return mixed Returns an array if the subject parameter is an array, or a string otherwise.
     */
    public function filterRegexp(
        $variable, 
        $pattern = '/([^\w\x{0080}-\x{10FFFF} ~\!@#\$%\^&\*\(\)\+`\-\=\[\]\\\{\}\|;\:,\.\/\?]+)/u', 
        $replacement = ''
    )
    {
        if (is_null($variable) || is_object($variable) || is_callable($variable) || is_resource($variable)) {
            return $variable;
        }

        $result = @preg_replace($pattern, $replacement, $variable);

        if (is_null($result)) {
            return $variable;
        }

        return $result;
    }// filterRegexp


    /**
     * Get input via GET method.
     * 
     * For the `$filter` and `$options` argument, please read more details at http://php.net/manual/en/function.filter-var.php page.
     * 
     * @param string $name The input name.
     * @param mixed $default Default value to return if the input name is not exists.
     * @param int $filter The ID of the filter to apply. See types of filters at http://php.net/manual/en/filter.filters.php page.
     * @param mixed $options Associative array of options or bitwise disjunction of flags.
     * @return mixed Return filtered input value.
     */
    public function get(string $name, $default = '', $filter = FILTER_UNSAFE_RAW, $options = null)
    {
        return $this->inputParams('GET', $name, $default, $filter, $options);
    }// get


    /**
     * Get input parameters such as $_GET, $_POST, etc.
     * 
     * For the `$filter` and `$options` argument, please read more details at http://php.net/manual/en/function.filter-var.php page.
     * 
     * @param string $inputType The input type. For example: 'GET' will access input data from `$_GET`, 'POST' will access from `$_POST`.
     * @param string $name The input name.
     * @param mixed $default Default value to return if the input name is not exists.
     * @param mixed $filter The ID of the filter to apply. See types of filters at http://php.net/manual/en/filter.filters.php page.
     * @param mixed $options Associative array of options or bitwise disjunction of flags.
     * @return mixed Return filtered input value.
     */
    protected function inputParams(string $inputType, string $name, $default = '', $filter = FILTER_UNSAFE_RAW, $options = null)
    {
        if (empty($inputType)) {
            $inputType = 'GET';
        } else {
            $inputType = strtoupper($inputType);
        }

        $inputParam = $GLOBALS['_' . $inputType];// in case type is GET this will be equal to $_GET, POST will be $_POST.

        if (isset($inputParam[$name])) {
            $value = $inputParam[$name];
        } else {
            unset($inputParam);
            return $default;
        }
        unset($inputParam);

        $value = $this->filter($value, $filter, $options);
        if ($value === false) {
            $value = $default;
        }

        return $value;
    }// inputParams


    /**
     * Check if HTTP accept type is non-HTML.
     * 
     * For check HTTP accept that can be called via REST API (that is not contain XMLHttpRequest).
     * 
     * @return bool Return `true` if it is non-HTML accept type, `false` for HTML or XHTML accept type.
     */
    public function isNonHtmlAccept(): bool
    {
        $httpAccept = ($_SERVER['HTTP_ACCEPT'] ?? '*/*');

        if (
            stripos($httpAccept, 'text/html') !== false ||
            stripos($httpAccept, 'application/xhtml+xml') !== false
        ) {
            // if html, xhtml
            return false;
        } else {
            // anything else
            return true;
        }
    }// isNonHtmlAccept


    /**
     * Check if request/input is XHR (XMLHttpRequest) or Ajax.
     * 
     * @return bool Return `true` if yes, `false` if not.
     */
    public function isXhr(): bool
    {
        return (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }// isXhr


    /**
     * Get input via PATCH method.
     * 
     * For the `$filter` and `$options` argument, please read more details at http://php.net/manual/en/function.filter-var.php page.
     * 
     * @param string $name The input name.
     * @param mixed $default Default value to return if the input name is not exists.
     * @param int $filter The ID of the filter to apply. See types of filters at http://php.net/manual/en/filter.filters.php page.
     * @param mixed $options Associative array of options or bitwise disjunction of flags.
     * @return mixed Return filtered input value.
     */
    public function patch(string $name, $default = '', $filter = FILTER_UNSAFE_RAW, $options = null)
    {
        global $_PATCH;
        parse_str(file_get_contents('php://input'), $_PATCH);

        return $this->inputParams('PATCH', $name, $default, $filter, $options);
    }// patch


    /**
     * Get input via POST method.
     * 
     * For the `$filter` and `$options` argument, please read more details at http://php.net/manual/en/function.filter-var.php page.
     * 
     * @param string $name The input name.
     * @param mixed $default Default value to return if the input name is not exists.
     * @param int $filter The ID of the filter to apply. See types of filters at http://php.net/manual/en/filter.filters.php page.
     * @param mixed $options Associative array of options or bitwise disjunction of flags.
     * @return mixed Return filtered input value.
     */
    public function post(string $name, $default = '', $filter = FILTER_UNSAFE_RAW, $options = null)
    {
        return $this->inputParams('POST', $name, $default, $filter, $options);
    }// post


    /**
     * Get input via PUT method.
     * 
     * For the `$filter` and `$options` argument, please read more details at http://php.net/manual/en/function.filter-var.php page.
     * 
     * @param string $name The input name.
     * @param mixed $default Default value to return if the input name is not exists.
     * @param int $filter The ID of the filter to apply. See types of filters at http://php.net/manual/en/filter.filters.php page.
     * @param mixed $options Associative array of options or bitwise disjunction of flags.
     * @return mixed Return filtered input value.
     */
    public function put(string $name, $default = '', $filter = FILTER_UNSAFE_RAW, $options = null)
    {
        global $_PUT;
        parse_str(file_get_contents('php://input'), $_PUT);

        return $this->inputParams('PUT', $name, $default, $filter, $options);
    }// put


    /**
     * Get input via GET, POST, COOKIE.
     * 
     * For the `$filter` and `$options` argument, please read more details at http://php.net/manual/en/function.filter-var.php page.
     * 
     * @param string $name The input name.
     * @param mixed $default Default value to return if the input name is not exists.
     * @param int $filter The ID of the filter to apply. See types of filters at http://php.net/manual/en/filter.filters.php page.
     * @param mixed $options Associative array of options or bitwise disjunction of flags.
     * @return mixed Return filtered input value.
     */
    public function request(string $name, $default = '', $filter = FILTER_UNSAFE_RAW, $options = null)
    {
        return $this->inputParams('REQUEST', $name, $default, $filter, $options);
    }// request


    /**
     * Get `$_SERVER` variable data.
     * 
     * For the `$filter` and `$options` argument, please read more details at http://php.net/manual/en/function.filter-var.php page.
     * 
     * @param string $name The input name.
     * @param mixed $default Default value to return if the input name is not exists.
     * @param int $filter The ID of the filter to apply. See types of filters at http://php.net/manual/en/filter.filters.php page.
     * @param mixed $options Associative array of options or bitwise disjunction of flags.
     * @return mixed Return filtered input value.
     */
    public function server(string $name, $default = '', $filter = FILTER_UNSAFE_RAW, $options = null)
    {
        return $this->inputParams('SERVER', $name, $default, $filter, $options);
    }// server


    /**
     * Get session value.
     * 
     * For the `$filter` and `$options` argument, please read more details at http://php.net/manual/en/function.filter-var.php page.
     * 
     * @param string $name The input name.
     * @param mixed $default Default value to return if the input name is not exists.
     * @param int $filter The ID of the filter to apply. See types of filters at http://php.net/manual/en/filter.filters.php page.
     * @param mixed $options Associative array of options or bitwise disjunction of flags.
     * @return mixed Return filtered input value.
     */
    public function session(string $name, $default = '', $filter = FILTER_UNSAFE_RAW, $options = null)
    {
        return $this->inputParams('SESSION', $name, $default, $filter, $options);
    }// session


}
