<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * Assets class.
 * 
 * To use Assets class, you have to call these methods by order.<br>
 * 1. `addAsset()`, `addMultipleAssets()`. You can use one of these methods but no need to call them all.<br>
 * 2. If you want to add more inline style or JavaScript please call `addCssInline()`, `addJsInline()`.<br>
 * 3. If you want to add JS object (to access them easily in JSON format) please call `addJsObject()`.<br>
 * 4. In the views page call to `renderAssets()`.<br>
 * That's all the basic usage.
 * 
 * @since 0.1
 */
class Assets
{


    /**
     * @var array Contain the associative array with `js`, `css` keys and its asset files that was added.
     */
    protected $addedAssets = ['css' => [], 'js' => []];


    /**
     * @var array Contain the associative array with `css`, `js` keys and its value is boolean for checking before do topological sort.
     */
    protected $assetsSorted = ['css' => false, 'js' => false];


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * Add an asset to the queue by type.
     * 
     * @param string $type The asset type (css, js).
     * @param string $handle The handle name of the asset. It must be unique to prevent overriding and duplication.
     * @param string $file The URL to file, it can be full URL or just relative path.<br>
     *                  If the file has special characters, it should be `rawurlencode()` before calling this.
     * @param array $dependency An array of added asset handles that this asset is depend on.<br>
     *                  The css and js dependency cannot be cross type. <br>
     *                  This class is not support cross group dependency yet.
     * @param string|bool $version The version number to added to the query string of this asset URL. If `false` means no version is added.
     * @param array $attributes An array of HTML attributes for this asset. Do not add id attribute because it will be auto generated.
     * @param string|null $group The group name. This can be helped when you want to render the assets by group.
     * @throws \InvalidArgumentException Throw the errors if invalid argument is specified.
     */
    public function addAsset(string $type, string $handle, string $file, array $dependency = [], $version = true, array $attributes = [], $group = null)
    {
        $type = $this->verifyType($type);

        if (!is_bool($version) && !is_scalar($version) && $version !== null) {
            throw new \InvalidArgumentException('The $version attribute must be string.');
        }

        if ($group != null && !is_string($group)) {
            throw new \InvalidArgumentException('The $group attribute must be string.');
        }

        if (is_array($this->addedAssets) && array_key_exists($type, $this->addedAssets)) {
            // if type exists in `addedAssets` property.
            if (is_array($this->addedAssets[$type]) && array_key_exists($handle, $this->addedAssets[$type])) {
                // if the handle specified is already exists.
                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
                    $caller = '';
                    if (is_array($backtrace) && isset($backtrace[0]['file']) && isset($backtrace[0]['line'])) {
                        $caller = $backtrace[0]['file'] . ' line ' . $backtrace[0]['line'];
                    }
                    unset($backtrace);
                    $Logger->write('modules/rdbadmin/libraries/assets', 2, 'The selected handle for ' . $type . ' asset is already exists. ({handle} => {file})', ['handle' => $handle, 'file' => $file, 'caller' => $caller]);
                    unset($caller, $Logger);
                }
            }
        } else {
            // if types is not exists, add new.
            $this->addedAssets[$type] = [];
        }

        if (stripos($file, '://') === false && stripos($file, '//') !== 0) {
            // if the asset file is local. check that if it exists or not.
            $Url = new \Rdb\System\Libraries\Url($this->Container);
            $fileRealPath = preg_replace('#^' . preg_quote($Url->getAppBasedPath(), '#') . '#', PUBLIC_PATH . '/', $file, 1);
            $fileRealPathBeforeNormalize = $fileRealPath;
            $fileRealPath = realpath($fileRealPath);
            if (!is_file($fileRealPath)) {
                // if asset file is not exists.
                if ($this->Container->has('Logger')) {
                    /* @var $Logger \Rdb\System\Libraries\Logger */
                    $Logger = $this->Container->get('Logger');
                    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
                    $caller = '';
                    if (is_array($backtrace) && isset($backtrace[0]['file']) && isset($backtrace[0]['line'])) {
                        $caller = $backtrace[0]['file'] . ' line ' . $backtrace[0]['line'];
                    }
                    unset($backtrace);
                    $Logger->write('modules/rdbadmin/libraries/assets', 3,  'The asset file is not exists! ({handle} => {file})', ['handle' => $handle, 'file' => $file, 'fileRealPath' => $fileRealPathBeforeNormalize, 'caller' => $caller]);
                    unset($caller, $Logger);
                }
            }
            unset($fileRealPath, $fileRealPathBeforeNormalize, $Url);
        }

        $this->addedAssets[$type] = array_merge(
            $this->addedAssets[$type], 
            [
                $handle => [
                    'handle' => $handle,
                    'file' => $file,
                    'dependency' => $dependency,
                    'version' => $version,
                    'attributes' => $attributes,
                    'group' => $group,
                    'inline' => null, // for inline style, inline script.
                    'printed' => false,// change to true when render via `renderAssets()` methods.
                ],
            ]
        );
    }// addAsset


    /**
     * Add in-line stylesheet to extend current css file.
     * 
     * You have to escape anything that should not be HTML by yourself before add to data.
     * 
     * @param string $handle The handle name of exists css file.
     * @param string $data The stylesheet content. Do not include `<style>...</style>` tag.
     */
    public function addCssInline(string $handle, string $data)
    {
        if (!isset($this->addedAssets['css'])) {
            $this->addedAssets['css'] = [];
        }

        if (!array_key_exists($handle, $this->addedAssets['css'])) {
            // if handle name is not found in added assets.
            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
                $Logger->write('modules/rdbadmin/libraries/assets', 3, 'The css handle name {handle} is not exists.', ['handle' => $handle]);
                unset($Logger);
            }
            return ;
        }

        $this->addedAssets['css'][$handle]['inline'] = $data;
    }// addCssInline


    /**
     * Add in-line JavaScript to extend current js file.
     * 
     * @param string $handle The handle name of exists js file.
     * @param string $data The JavaScript content. Do not include `&ltscript&gt;...&lt/script&gt;` tag.
     * @param string $position Position to add this in-line js. The value is "before", "after". Please call this method after `addAsset()` method even if position is before, otherwise it will not show up.
     */
    public function addJsInline(string $handle, string $data, string $position = 'after')
    {
        if (!isset($this->addedAssets['js'])) {
            $this->addedAssets['js'] = [];
        }

        if (!array_key_exists($handle, $this->addedAssets['js'])) {
            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
                $Logger->write('modules/rdbadmin/libraries/assets', 3, 'The js handle name {handle} is not exists.', ['handle' => $handle]);
                unset($Logger);
            }
            return ;
        }

        if ($position !== 'after' && $position !== 'before') {
            $position = 'after';
        }

        if (!is_array($this->addedAssets['js'][$handle]['inline'])) {
            $this->addedAssets['js'][$handle]['inline'] = [];
        }
        $this->addedAssets['js'][$handle]['inline'][$position] = $data;
    }// addJsInline


    /**
     * Add JavaScript object for use the data between js and php such as translation text.
     * 
     * You have to escape anything that should not be HTML by yourself before add to data.
     * 
     * @param string $handle The asset handle name of exists js file.
     * @param string $name The JavaScript object name. This should be unique to prevent conflict with others.
     * @param array $data Recommended use associative array where the key will becomes js property.
     */
    public function addJsObject(string $handle, string $name, array $data)
    {
        if (!isset($this->addedAssets['js'])) {
            $this->addedAssets['js'] = [];
        }

        if (!array_key_exists($handle, $this->addedAssets['js'])) {
            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
                $Logger->write('modules/rdbadmin/libraries/assets', 3, 'The js handle name {handle} is not exists.', ['handle' => $handle]);
                unset($Logger);
            }
            return ;
        }

        $this->addedAssets['js'][$handle]['jsobject'] = [$name => $data];
    }// addJsObject


    /**
     * Automatically add missed dependencies.
     * 
     * This method was called from `addMultipleAssets()` method.
     * 
     * @param string $type The asset type (css, js).
     * @param array $handles The array list of asset's handle name to check that its dependency is already added or not.
     * @param array $assetsData The assets data in array value. 
     */
    protected function addMissedDependencies(string $type, array $handles, array $assetsData)
    {
        foreach ($handles as $handle) {
            foreach ($assetsData[$type] as $assetsKey => $assetsItem) {
                if (
                    isset($assetsItem['handle']) &&
                    $assetsItem['handle'] === $handle &&
                    isset($assetsItem['dependency']) && 
                    is_array($assetsItem['dependency'])
                ) {
                    foreach ($assetsItem['dependency'] as $eachDependency) {
                        if (!isset($this->addedAssets[$type][$eachDependency])) {
                            $this->addMultipleAssets($type, [$eachDependency], $assetsData);
                        }
                    }// endforeach;
                    unset($eachDependency);
                }// endif;
            }// endforeach;
            unset($assetsItem, $assetsKey);
        }// endforeach;
        unset($handle);
    }// addMissedDependencies


    /**
     * Add multiple assets at once to the queue by type.
     * 
     * This method will add those dependencies for each asset handle automatically.
     * 
     * @param string $type The asset type (css, js).
     * @param array $handles The array list of asset's handle name that you want to add. The handle should exists in `$assetsData`.
     * @param array $assetsData The assets data in array value. These assets will be chosen by `$handles` and add to the class. <br>
     *               The data structure will be...
     * <pre>
     *               array(
     *                   'css' => array( // contain the asset type in array key.
     *                       array(
     *                           // the array keys name in this will be matched Assets->addAsset() arguments except the type.
     *                           'handle' => (string)'xxx', // this is required
     *                           'file' => (string)'xxx/xxx/xxx.xx', // this is required
     *                           'dependency' => array('the', 'array', 'of', 'handle', 'this', 'asset', 'depend', 'on'),
     *                           'version' => 'x.x.x', // string or boolean
     *                           'attributes' => array('title' => 'my element title', 'data-name' => 'my data-name attribute'),
     *                           'group' => null, // string or null
     *                       ),
     *                       array(
     *                           ....
     *                       ),
     *                   ),
     *                   'js' => array( // contain the asset type in array key.
     *                       array(
     *                           // the array keys name in this will be matched Assets->addAsset() arguments except the type.
     *                           'handle' => (string)'xxx', // this is required
     *                           'file' => (string)'xxx/xxx/xxx.xx', // this is required
     *                           'dependency' => array('the', 'array', 'of', 'handle', 'this', 'asset', 'depend', 'on'),
     *                           'version' => 'x.x.x', // string or boolean
     *                           'attributes' => array('title' => 'my element title', 'data-name' => 'my data-name attribute'),
     *                           'group' => null, // string or null
     *                       ),
     *                       array(
     *                           ....
     *                       ),
     *                   ),
     *               )
     * </pre>
     *                  This class is not support cross group dependency yet.
     */
    public function addMultipleAssets(string $type, array $handles, array $assetsData)
    {
        if (!array_key_exists($type, $assetsData)) {
            $handles = [];// clear memory.
            $assetsData = [];// clear memory.
            return;
        }

        $foundAssetKeys = [];
        $foundHandles = [];

        // loop handles to check that specified handles were found in the assets data.
        foreach ($handles as $handle) {
            if (!is_scalar($handle)) {
                break;
            }
    
            foreach ($assetsData[$type] as $assetsKey => $assetsItem) {
                if (is_array($assetsItem) && true === in_array($handle, $assetsItem, true)) {
                    $foundAssetKeys[] = $assetsKey;
                    $foundHandles[] = $handle;
                }
            }// endforeach;
            unset($assetsItem, $assetsKey);
        }// endforeach;
        unset($handle);

        if (count($handles) !== count($foundAssetKeys)) {
            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
                $Logger->write('modules/rdbadmin/libraries/assets', 3, 'Found only ' . count($foundAssetKeys) . ' assets from total ' . count($handles) . ' input handles for ' . $type . '.', ['found' => $foundHandles, 'handles' => $handles]);
                unset($Logger);
            }
        }

        $this->addMissedDependencies($type, $foundHandles, $assetsData);

        unset($foundHandles);
        $handles = [];// clear memory.

        if (isset($foundAssetKeys) && is_array($foundAssetKeys) && !empty($foundAssetKeys)) {
            // if found the assets key.
            // loop found assets key in the assets data for add the assets.
            foreach ($foundAssetKeys as $key) {
                if (array_key_exists($key, $assetsData[$type]) && is_array($assetsData[$type][$key])) {
                    $assetItem = $assetsData[$type][$key];
                    // check that the key names that matched the addAsset() argument are exists. if not set the default value.
                    if (!array_key_exists('handle', $assetItem)) {
                        $assetItem['handle'] = null;
                    }
                    if (!array_key_exists('file', $assetItem)) {
                        $assetItem['file'] = null;
                    }
                    if (!array_key_exists('dependency', $assetItem)) {
                        $assetItem['dependency'] = [];
                    }
                    if (!array_key_exists('version', $assetItem)) {
                        $assetItem['version'] = true;
                    }
                    if (!array_key_exists('attributes', $assetItem)) {
                        $assetItem['attributes'] = [];
                    }
                    if (!array_key_exists('group', $assetItem)) {
                        $assetItem['group'] = null;
                    }

                    if (!$this->assetIs($type, $assetItem['handle'])) {
                        // if item was not added
                        // now, add the asset.
                        $this->addAsset($type, $assetItem['handle'], $assetItem['file'], $assetItem['dependency'], $assetItem['version'], $assetItem['attributes'], $assetItem['group']);
                    }
                    unset($assetItem);
                }
            }// endforeach;
            unset($key);
        }
        unset($foundAssetKeys);
        $assetsData = [];// clear memory.
    }// addMultipleAssets


    /**
     * Check that if asset is ...(action)...
     * 
     * Example: check if asset is already "added" or "printed".
     * 
     * @param string $type The asset type (css, js).
     * @param string $handle The asset's handle name by type.
     * @param string $action The action to check. The value is "added", "printed"
     * @return bool Return true if the selected action already did. Return false for not.
     */
    public function assetIs(string $type, string $handle, string $action = 'added'): bool
    {
        $type = $this->verifyType($type);

        if ($action != 'added' && $action != 'printed') {
            $action = 'added';
        }

        if (isset($this->addedAssets[$type]) && is_array($this->addedAssets[$type])) {
            if (array_key_exists($handle, $this->addedAssets[$type])) {
                if ($action == 'added') {
                    return true;
                }

                if (
                    isset($this->addedAssets[$type][$handle]['printed']) && 
                    $this->addedAssets[$type][$handle]['printed'] === true && 
                    $action == 'printed'
                ) {
                    return true;
                }
            }
        }

        return false;
    }// assetIs


    /**
     * Clear any added assets and begins again.
     * 
     * It will be clear by asset type (css, js).
     * 
     * @param string $type The asset type to be cleared. The value is css, js. Set this to empty means clear all.
     */
    public function clear(string $type)
    {
        if (empty($type)) {
            $this->addedAssets = ['css' => [], 'js' => []];
            $this->assetsSorted = ['css' => false, 'js' => false];
        } elseif ($type === 'css') {
            $this->addedAssets['css'] = [];
            $this->assetsSorted['css'] = false;
        } elseif ($type === 'js') {
            $this->addedAssets['js'] = [];
            $this->assetsSorted['js'] = false;
        } else {
            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
                $caller = '';
                if (is_array($backtrace) && isset($backtrace[0]['file']) && isset($backtrace[0]['line'])) {
                    $caller = $backtrace[0]['file'] . ' line ' . $backtrace[0]['line'];
                }
                unset($backtrace);
                $Logger->write('modules/rdbadmin/libraries/assets', 2, 'The asset type is incorrect ({type}).', ['type' => $type, 'caller' => $caller]);
                unset($Logger);
            }
        }
    }// clear


    /**
     * Generate asset URL with ?v=version_number append.
     * 
     * This method was called from `renderAssets()` method.
     * 
     * @param array $item The item should have "file" and "version" in array key.
     * @return Return generated asset URL. The returned value did not escape for HTML, you must use `htmlspecialchars()` to make something such as `&` to be `&amp;` which is correct HTML value.
     */
    protected function generateAssetUrlWithVersion(array $item): string
    {
        $output = '';

        if (array_key_exists('file', $item) && is_scalar($item['file'])) {
            $Url = new \Rdb\System\Libraries\Url();

            $fileParts = parse_url($item['file']);

            if (isset($fileParts['query'])) {
                parse_str($fileParts['query'], $queries);
            } else {
                $queries = [];
            }

            unset($fileParts['fragment'], $fileParts['query']);// remove ?query and #fragment for build URL without them.
            $fileUrl = $Url->buildUrl($fileParts);

            if (array_key_exists('version', $item) && $item['version'] !== false) {
                if (!isset($queries['v'])) {
                    $queryVersionName = 'v';
                } else {
                    $queryVersionName = 'v_' . hash('sha512', __FILE__);
                }

                if (!is_string($item['version'])) {
                    // if file version is set to auto generate.
                    $queries[$queryVersionName] = date('Ym');

                    // check again for file version that is local and get its modify date.
                    if (stripos($item['file'], '://') === false && stripos($item['file'], '//') !== 0) {
                        // if local file
                        
                        $fileRealPath = preg_replace('#'.$Url->getAppBasedPath().'#', PUBLIC_PATH . '/', $item['file'], 1);
                        $fileRealPath = realpath($fileRealPath);

                        if (is_file($fileRealPath)) {
                            $queries[$queryVersionName] = 'fmt-' . filemtime($fileRealPath);
                        }

                        unset($fileRealPath);
                    }
                } else {
                    // if version is string
                    $queries[$queryVersionName] = $item['version'];
                }

                unset($queryVersionName);
            }// endif; asset version append to url.

            unset($Url);

            $output = $fileUrl;
            if (!empty($queries)) {
                $output .= '?' . http_build_query($queries, '', ini_get('arg_separator.output'), PHP_QUERY_RFC3986);
            }
            unset($fileUrl, $queries);
        }// endif; array_key_exists('file', $item)

        return $output;
    }// generateAssetUrlWithVersion


    /**
     * Generate HTML attributes by filter out disallow attributes.
     * 
     * This method was called from `renderAssets()` method.
     * 
     * @param array $attributes The associative array of attributes. Example: `['title' => 'My Title', 'data-id' => 3]` will becomes `title="My Title" data-id="3"`
     * @param array $disallowAttributes The 2D array of disallow attribute names. Example: `['id', 'class']` so, the `$attributes` that contain id and class will be removed.
     * @return string Return generated HTML attributes with trim spaces. The returned value is already escape attribute value with `htmlspecialchars()`.
     */
    protected function generateAttributes(array $attributes, array $disallowAttributes = []): string
    {
        $output = '';

        foreach ($attributes as $name => $value) {
            if (is_scalar($name)) {
                if (!in_array($name, $disallowAttributes)) {
                    $output .= ' ' . strip_tags($name) . '="';
                    $value = (string) $value;
                    $output .= htmlspecialchars($value, ENT_QUOTES);
                    $output .= '"';// end quote of attribute xxx="..."
                }
            }
        }// endforeach;
        unset($name, $value);

        return trim($output);
    }// generateAttributes


    /**
     * Generate in-line JavaScript.
     * 
     * This method was called from `renderAssets()` method.
     * 
     * @param array $item The item should have "inline" in array key.
     * @param string $position The position is "before", "after".
     * @return string Return generated in-line JavaScript.
     */
    protected function generateInlineScript(array $item, string $position = 'after'): string
    {
        if ($position !== 'after' && $position !== 'before') {
            $position = 'after';
        }

        $output = '';

        if (isset($item['inline'][$position]) && trim($item['inline'][$position]) != null) {
            $output .= '<script';
            if (isset($item['handle'])) {
                $output .= ' id="' . $item['handle'] . '-inlineScript' . ucfirst($position) . '"';
            }
            $output .= ' type="application/javascript">'."\n";
            $output .= $item['inline'][$position]."\n";
            $output .= '</script>'."\n";
        }

        return $output;
    }// generateInlineScript


    /**
     * Generate in-line stylesheet.
     * 
     * This method was called from `renderAssets()` method.
     * 
     * @param array $item The item should have "inline" in array key.
     * @return string Return generated in-line stylesheet.
     */
    protected function generateInlineStyle(array $item): string
    {
        $output = '';

        if (isset($item['inline']) && trim($item['inline']) != null) {
            $output .= '<style';
            if (isset($item['handle'])) {
                $output .= ' id="' . $item['handle'] . '-inlineStyle"';
            }
            $output .= ' type="text/css">'."\n";
            $output .= $item['inline']."\n";
            $output .= '</style>'."\n";
        }

        return $output;
    }// generateInlineStyle


    /**
     * Generate JavaScript object.
     * 
     * This method was called from `renderAssets()` method.
     * 
     * @param array $item The item should have `jsobject` in array key.
     * @return string Return generated JS object.
     */
    protected function generateJsObject(array $item): string
    {
        $output = '';

        if (isset($item['jsobject']) && is_array($item['jsobject']) && !empty($item['jsobject'])) {
            $jsObjectName = key($item['jsobject']);
            $jsObjects = ($item['jsobject'][$jsObjectName] ?? '');

            $output .= '<script';
            if (isset($item['handle'])) {
                $output .= ' id="' . $item['handle'] . '-jsObject"';
            }
            $output .= ' type="application/javascript">'."\n";
            $output .= '/* <![CDATA[ */'."\n";
            $output .= 'var ' . $jsObjectName . ' = ';
            $output .= json_encode($jsObjects);
            $output .= ';'."\n";
            $output .= '/* ]]> */'."\n";
            $output .= '</script>'."\n";

            unset($jsObjectName, $jsObjects);
        }

        return $output;
    }// generateJsObject


    /**
     * Check and separate dependencies into exists and not exists.
     * 
     * This method was called from `topologicalSort()` method.
     * 
     * @param string $type The asset type (css, js).
     * @param array $dependency The dependencies list array.
     * @return array Return associative array with `exists` and `not_exists` in keys. The array values of `exists`, `not_exists` are from `$dependency` list.
     */
    protected function getDependencyExists(string $type, array $dependency): array
    {
        $exists = [];
        $notExists = [];

        if ($this->Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $this->Container->get('Logger');
        }

        if (isset($this->addedAssets[$type]) && is_array($this->addedAssets[$type])) {
            foreach ($dependency as $eachDependency) {
                $eachDependency = (string) $eachDependency;

                if (array_key_exists($eachDependency, $this->addedAssets[$type])) {
                    $exists[] = $eachDependency;
                } else {
                    $notExists[] = $eachDependency;

                    if (isset($Logger)) {
                        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);
                        $caller = [];

                        if (is_array($backtrace)) {
                            foreach ($backtrace as $key => $item) {
                                if (isset($item['file']) && isset($item['line'])) {
                                    $caller[] = $item['file'] . ' line ' . $item['line'];
                                }
                            }// endforeach;
                            unset($item, $key);
                        }

                        $Logger->write('modules/rdbadmin/libraries/assets', 3, 'The asset dependency (' . $type . ') name "' . $eachDependency . '" is not exists.'."\n".'trace:'."\n".implode("\n", $caller));
                        unset($backtrace, $caller);
                    }
                }
            }// endforeach;
            unset($eachDependency);
        }

        unset($Logger);

        return [
            'exists' => $exists,
            'not_exists' => $notExists,
        ];
    }// getDependencyExists


    /**
     * Merge selected type of assets data.
     * 
     * The last asset data will be append to the end of previous asset data.
     * 
     * @param string $type 'css' or 'js'.
     * @param array $assetsData The assets data in array value. The array format must contain type as key. Example array('css' => array());
     * @return array Return merged assets data.
     */
    public function mergeAssetsData(string $type, array ...$assetsData): array
    {
        $type = $this->verifyType($type);

        $mergedAssetsData = [];

        foreach ($assetsData as $eachAssetsData) {
            // prepare data
            if (!array_key_exists($type, $mergedAssetsData)) {
                $mergedAssetsData[$type] = [];
            }
            if (!array_key_exists($type, $eachAssetsData)) {
                $eachAssetsData[$type] = [];
            }

            if (!empty($eachAssetsData[$type])) {
                array_push($mergedAssetsData[$type], ...$eachAssetsData[$type]);
            }
        }// endforeach;
        unset($eachAssetsData);

        return $mergedAssetsData;
    }// mergeAssetsData


    /**
     * Remove the added asset from specified handle name.
     * 
     * @param string $type The asset type (css, js).
     * @param string $handle The asset handle name.
     */
    public function removeAsset(string $type, string $handle)
    {
        $type = $this->verifyType($type);

        if (isset($this->addedAssets[$type]) && is_array($this->addedAssets[$type])) {
            if (array_key_exists($handle, $this->addedAssets[$type])) {
                unset($this->addedAssets[$type][$handle]);
            }
        }
    }// removeAsset


    /**
     * Render individual asset (CSS).
     * 
     * @param string $handle The handle name.
     * @param array $item The array item.
     * @return string Return rendered HTML.
     */
    private function renderAssetCss(string $handle, array $item): string
    {
        $output = '';
        $output .= '<link id="' . $handle . '-css" rel="stylesheet" type="text/css" href="';
        $output .= htmlspecialchars($this->generateAssetUrlWithVersion($item), ENT_QUOTES);
        $output .= '"';// end quote of href="..."
        if (isset($item['attributes']) && !empty($item['attributes'])) {
            $generatedAtts = $this->generateAttributes($item['attributes'], ['id', 'href', 'rel', 'type']);
            if (!empty($generatedAtts)) {
                $output .= ' ' . $generatedAtts;
            }
            unset($generatedAtts);
        }
        $output .= '>'."\n";// end <link> element.
        $output .= $this->generateInlineStyle($item);

        return $output;
    }// renderAssetCss


    /**
     * Render individual asset (JS).
     * 
     * @param string $handle The handle name.
     * @param array $item The array item.
     * @return string Return rendered HTML.
     */
    private function renderAssetJs(string $handle, array $item): string
    {
        $output = '';
        $output .= $this->generateJsObject($item);
        $output .= $this->generateInlineScript($item, 'before');
        $output .= '<script id="' . $handle . '-js"';

        if (isset($item['attributes']) && is_array($item['attributes']) && !array_key_exists('type', $item['attributes'])) {
            $output .= ' type="application/javascript"';
        }

        $output .= ' src="';
        $output .= htmlspecialchars($this->generateAssetUrlWithVersion($item), ENT_QUOTES);
        $output .= '"';// end quote of src="..."
        if (isset($item['attributes']) && !empty($item['attributes'])) {
            $generatedAtts = $this->generateAttributes($item['attributes'], ['id', 'src']);
            if (!empty($generatedAtts)) {
                $output .= ' ' . $generatedAtts;
            }
            unset($generatedAtts);
        }
        $output .= '></script>'."\n";// end <script> element.
        $output .= $this->generateInlineScript($item, 'after');

        return $output;
    }// renderAssetJs


    /**
     * Render the assets into HTML elements.
     * 
     * This will be call to `topologicalSort()` and then render.<br>
     * This class is not support cross group dependency yet.
     * 
     * @param string $type The asset type (css, js).
     * @param string|null $group The asset group that was specified via `addCss()` method.
     * @return string Return the generated HTML elements into string.
     */
    public function renderAssets(string $type, $group = null): string
    {
        if (!is_scalar($group) && $group !== null) {
            throw new \InvalidArgumentException('The $group must be string.');
        }

        $type = $this->verifyType($type);
        $output = '';

        $this->topologicalSort($type);

        if (is_array($this->addedAssets) && array_key_exists($type, $this->addedAssets) && is_array($this->addedAssets[$type])) {
            switch ($type) {
                case 'css':
                    foreach ($this->addedAssets[$type] as $handle => $item) {
                        if (
                            isset($this->addedAssets[$type][$handle]['printed']) && 
                            $this->addedAssets[$type][$handle]['printed'] === true
                        ) {
                            // if asset was printed, skip it.
                            continue;
                        }

                        if (($group === null && $item['group'] === null) || ($item['group'] === $group)) {
                            // if group was matched.
                            $output .= $this->renderAssetCss($handle, $item);
                            // change some array value to mark that this was printed (rendered).
                            $this->addedAssets[$type][$handle]['printed'] = true;
                        }
                    }// endforeach;
                    break;
                case 'js':
                default:
                    foreach ($this->addedAssets[$type] as $handle => $item) {
                        if (
                            isset($this->addedAssets[$type][$handle]['printed']) && 
                            $this->addedAssets[$type][$handle]['printed'] === true
                        ) {
                            // if asset was printed, skip it.
                            continue;
                        }

                        if (($group === null && $item['group'] === null) || ($item['group'] === $group)) {
                            // if group was matched.
                            $output .= $this->renderAssetJs($handle, $item);
                            // change some array value to mark that this was printed (rendered).
                            $this->addedAssets[$type][$handle]['printed'] = true;
                        }
                    }// endforeach;
                    break;
            }// endswitch;
            unset($handle, $item);
        }

        return $output;
    }// renderAssets


    /**
     * Topological sort the assets by type (js, css).
     * 
     * Topological sort is sorting by dependency comes before and then follow by order.<br>
     * Thanks to marcj/topsort ( https://github.com/marcj/topsort.php ) to make this easy.
     * 
     * This method was called from `renderAssets()` method.
     * 
     * @param string $type The asset type value is css, js.
     */
    protected function topologicalSort(string $type)
    {
        if (isset($this->assetsSorted[$type]) && $this->assetsSorted[$type] === true) {
            return ;
        }

        $type = $this->verifyType($type);

        if (is_array($this->addedAssets) && array_key_exists($type, $this->addedAssets) && is_array($this->addedAssets[$type])) {
            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
            }

            try {
                $Sorter = new \MJS\TopSort\Implementations\FixedArraySort();
                foreach ($this->addedAssets[$type] as $handle => $item) {
                    if (isset($item['dependency'])) {
                        $dependencies = $this->getDependencyExists($type, $item['dependency']);
                        if (isset($dependencies['exists'])) {
                            $Sorter->add($handle, $dependencies['exists']);
                        } else {
                            $Sorter->add($handle);
                        }
                        unset($dependencies);
                    } else {
                        $Sorter->add($handle);
                    }
                }// endforeach;
                unset($handle, $item);
                $sortedHandles = $Sorter->sort();
                unset($Sorter);
            } catch (\Exception $e) {
                if (isset($Logger)) {
                    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
                    $traceLog = [];

                    if (is_array($backtrace)) {
                        foreach ($backtrace as $key => $item) {
                            if (isset($item['file']) && isset($item['line'])) {
                                $traceLog[] = $item['file'] . ' line ' . $item['line'];
                            }
                        }// endforeach;
                        unset($item, $key);
                    }
                    unset($backtrace);

                    $Logger->write('modules/rdbadmin/libraries/assets', 3, 'Asset dependency (' . $type . ') error! ' . $e->getMessage() . "\n" . 'trace:' . "\n" . implode("\n", $traceLog));
                    unset($traceLog);
                }
            }

            $sortedAssets = [];
            if (isset($sortedHandles) && is_array($sortedHandles) && !empty($sortedHandles)) {
                foreach ($sortedHandles as $handle) {
                    $sortedAssets[$handle] = ($this->addedAssets[$type][$handle] ?? []);
                }// endforeach;
                unset($handle);
                $this->addedAssets[$type] = $sortedAssets;
                if (isset($Logger)) {
                    $Logger->write('modules/rdbadmin/libraries/assets', 1, 'Asset dependency (' . $type . ') was sorted.', $sortedAssets);
                }
            }
            unset($sortedAssets, $sortedHandles);

            $this->assetsSorted[$type] = true;

            unset($Logger);
        }
    }// topologicalSort


    /**
     * Verify asset type.
     * 
     * This is common use in many methods to verify asset type (css, js).
     * 
     * @param string $type The asset type (css, js).
     * @return string Return the correct asset type. If incorrect then return `js`.
     */
    protected function verifyType(string $type): string
    {
        $type = strtolower($type);

        if ($type !== 'css' && $type !== 'js') {
            $type = 'js';
        }

        return $type;
    }// verifyType


}
