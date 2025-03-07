<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Models\Traits;


/**
 * Build cache data for model.
 * 
 * To use cache data, call to `loadCacheData([$this, 'yourBuildCacheContent'])`.<br>
 * Example:
 * <pre>
 * // To use cache data with your build cache content.
 * class MyModel extends \Rdb\System\Core\Models\BaseModel
 * {
 *      public function __construct(\Rdb\System\Container $Container)
 *      {
 *          $this->storageFile = 'mymodel-db.php';
 *          $this->beginCacheFileTrait($Container);
 *      }
 * 
 *      public function myBuildCacheContent()
 *      {
 *          $sql = 'SELECT * FROM `' . $this->Db->tableName('mytable') . '`';
 *          $Pdo = $this->Db->PDO();
 *          $Sth = $Pdo->prepare($sql);
 *          $Sth->execute();
 *          $result = $Sth->fetchAll();
 *          $Sth->closeCursor();
 * 
 *          $output = '<?php' . PHP_EOL;
 *          $output .= 'return [' . PHP_EOL;
 *          foreach ($result as $row) {
 *              $output .= '\'' . $row->config_name . '\' => \'' . $row->config_value . '\',';'
 *          }
 *          $output .= '];' . PHP_EOL;
 *          return $output;
 *      }
 * 
 *      public function getData($name, $default = null)
 *      {
 *          $this->loadCacheData([$this, 'myBuildCacheContent']);
 *          print_r($this->storageData);
 *      }
 * }
 * 
 * // To use auto generated build cache content.
 * class MyModel extends \Rdb\Modules\RdbAdmin\Models\BaseModel
 * {
 *      public function __construct(\Rdb\System\Container $Container)
 *      {
 *          $this->storageFile = 'mymodel-db.php';
 *          $this->beginCacheFileTrait($Container);
 *      }
 * 
 *      public function myBuildCacheContent()
 *      {
 *          $sql = 'SELECT * FROM `' . $this->Db->tableName('mytable') . '`';
 *          $Pdo = $this->Db->PDO();
 *          $Sth = $Pdo->prepare($sql);
 *          $Sth->execute();
 *          $result = $Sth->fetchAll();
 *          $Sth->closeCursor();
 * 
 *          return $this->buildCacheContentFromResult($result);
 *      }
 * 
 *      public function getData($name, $default = null)
 *      {
 *          $this->loadCacheData([$this, 'myBuildCacheContent']);
 *          print_r($this->storageData);
 *      }
 * }
 * </pre>
 * 
 * @since 0.1
 */
trait CacheFileTrait
{


    /**
     * @var \Rdb\System\Libraries\FileSystem
     */
    protected $FileSystem;


    /**
     * @var mixed The data that was generated from DB. This property is depend on how you set `$content` argument in `buildCacheFile()` method.
     */
    protected $storageData;


    /**
     * @var string File name with extension for cache. Example: `'tablename-db.php'`.
     */
    protected $storageFile;


    /**
     * @var string Full path to storage folder and specific name without trailing slash. 
     *                      Example `STORAGE_PATH . '/cache/tablename'` for store data from selected table in DB.
     *                      Only set this if you want to use cache.
     */
    protected $storagePath;


    /**
     * Trait initialize method.
     * 
     * This method must be called before it can be working.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    protected function beginCacheFileTrait(\Rdb\System\Container $Container)
    {
        if (empty($this->storageFile) || !is_string($this->storageFile)) {
            $this->storageFile = hash('sha512', get_called_class()) . '.php';
        }

        if (empty($this->storagePath) || !is_string($this->storagePath)) {
            if ($Container->has('Modules')) {
                $Module = $Container->get('Modules')->getCurrentModule();
            } else {
                if (function_exists('get_called_class')) {
                    $calledClass = str_replace('/', '\\', get_called_class());
                    $expCalledClass = explode('\\', $calledClass);
                    if (isset($expCalledClass[1])) {
                        $Module = $expCalledClass[1];
                    }
                    unset($calledClass, $expCalledClass);
                }

                if (!isset($Module)) {
                    $backTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
                    if (isset($backTrace[1]['file'])) {
                        $Module = hash('sha512', $backTrace[1]['file']);
                    } else {
                        $Module = hash('sha512', __CLASS__);
                    }
                    unset($backTrace);
                }
            }
            $this->storagePath = STORAGE_PATH . '/cache/Modules/' . $Module . '/Models';
            unset($Module);
        }

        $this->FileSystem = new \Rdb\System\Libraries\FileSystem($this->storagePath);
    }// beginCacheFileTrait


    /**
     * Buld cache content from result that got from `PDOStatement::fetchAll()`.
     * 
     * @param mixed $result The result from `PDOStatement::fetchAll()`.
     * @param string $formatMethod The format option. Value can be 'array', 'json_array', 'replace_setstate', 'serialize', 'raw'. Default is 'raw'.
     * @return string Return generated content ready for cache.
     */
    protected function buildCacheContentFromResult($result, string $formatMethod = 'raw'): string
    {
        if ($formatMethod === 'array') {
            $resultArr = json_decode(json_encode($result), true);
            $resultString = var_export($resultArr, true) . ';';
            unset($resultArr);
        } elseif ($formatMethod === 'json_array') {
            $resultString = 'json_decode(\'' . str_replace('\'', '\\\'', json_encode($result)) . '\', true);';
        } elseif ($formatMethod === 'replace_setstate') {
            $resultString = var_export($result, true);
            $resultString = str_replace('stdClass::__set_state', '(object) ', $resultString);
            $resultString .= ';';
        } elseif ($formatMethod === 'serialize') {
            $resultString = 'unserialize(\'' . str_replace('\'', '\\\'', serialize($result)) . '\');';
        } else {
            $resultString = var_export($result, true) . ';';
        }

        return '<?php' . PHP_EOL .
            '/**' . PHP_EOL .
            ' * Auto generate by ' . __FILE__ . ':' . __LINE__ . '.' . PHP_EOL .
            ' */' . PHP_EOL .
            'return ' .
            $resultString;
    }// buildCacheContentFromResult


    /**
     * Build the cache file.
     * 
     * This method was called from `getCacheFilePath()`.
     * 
     * @param string $content The file content to build cache. This content should be array or object from db in php data that is ready to access.
     * @return Boolean Return true on success build cache file.
     */
    private function buildCacheFile(string $content): bool
    {
        if (!is_dir($this->storagePath)) {
            // if storage/[name] folder is not exists, create one.
            $this->FileSystem->createFolder('');
        }

        $this->deleteCachedFile();

        // add debug backtrace for checking how it created.
        $content .= PHP_EOL . PHP_EOL . PHP_EOL;
        $content .= '/**' . PHP_EOL;
        $content .= ' * Debug backtrace:' . PHP_EOL;
        $content .= ' * Generated from file: ' . __FILE__ . ' : ' . (__LINE__ - 3) . PHP_EOL;
        $debugbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 0);
        if (is_array($debugbt) && !empty($debugbt)) {
            foreach ($debugbt as $trace) {
                $content .= '   # ';
                if (isset($trace['class'])) {
                    $content .= trim($trace['class']);
                }
                if (isset($trace['type'])) {
                    $content .= trim($trace['type']);
                }
                if (isset($trace['function'])) {
                    $content .= trim($trace['function']) . '()';
                }
                $content .= '; ';
                if (isset($trace['file'])) {
                    $content .= ' at ' . trim($trace['file']);
                    if (isset($trace['line'])) {
                        $content .= ' line ' . trim($trace['line']);
                    }
                }
                $content .= PHP_EOL;
            }// endforeach;
            unset($trace);
        }
        unset($debugbt);
        $content .= ' */' . PHP_EOL;
        $content .= PHP_EOL;

        // write the php content to cache file.
        $buildResult = file_put_contents($this->storagePath . DIRECTORY_SEPARATOR . $this->storageFile, $content, LOCK_EX);

        if ($buildResult !== false && $this->Container->has('Logger')) {
            /* @var $Logger \Rdb\System\Libraries\Logger */
            $Logger = $this->Container->get('Logger');
            $Logger->write('modules/rdbadmin/models/traits/cachefiletrait', 0, $this->storagePath . DIRECTORY_SEPARATOR . $this->storageFile . ' has been built.');
            unset($Logger);
        }

        if ($buildResult !== false) {
            return true;
        }
        return false;
    }// buildCacheFile


    /**
     * Delete the file that were built and cached.
     * 
     * This method was called from `buildCacheFile()`.
     * 
     * @throws Throw exception if the file is exists and really cannot delete.
     */
    protected function deleteCachedFile()
    {
        if (is_file($this->storagePath . DIRECTORY_SEPARATOR . $this->storageFile)) {
            $this->FileSystem->deleteFile($this->storageFile);
            $this->storageData = null;
            if (is_file($this->storagePath . DIRECTORY_SEPARATOR . $this->storageFile)) {
                // if the file is still exists.
                $result = @unlink($this->storagePath . DIRECTORY_SEPARATOR . $this->storageFile);
                if ($result === false) {
                    throw new \RuntimeException(sprintf('Unable to delete file (%s), please check your permission.', $this->storagePath . DIRECTORY_SEPARATOR . $this->storageFile));
                }
            }

            if ($this->Container->has('Logger')) {
                /* @var $Logger \Rdb\System\Libraries\Logger */
                $Logger = $this->Container->get('Logger');
                $Logger->write('modules/rdbadmin/models/traits/cachefiletrait', 0, $this->storagePath . DIRECTORY_SEPARATOR . $this->storageFile . ' has been deleted.');
                unset($Logger);
            }
        }
    }// deleteCachedFile


    /**
     * Get full path to cache file.
     * 
     * This method was called from `loadCacheData()`.
     * 
     * @param callable $BuildContent The callback array to build content if needed.
     * @return string Return full path to cache file. Return empty string if cache file was not found and unable to build.
     */
    private function getCacheFilePath(callable $BuildContent): string
    {
        if ($this->isNeedRebuildCache() === true) {
            // if it is needed to build, rebuild cache file.
            $content = call_user_func($BuildContent);
            $this->buildCacheFile($content);
        }

        if (is_file($this->storagePath . DIRECTORY_SEPARATOR . $this->storageFile)) {
            // if file exists.
            return $this->storagePath . DIRECTORY_SEPARATOR . $this->storageFile;
        } else {
            // if file is not exists.
            return '';
        }
    }// getCacheFilePath


    /**
     * Check if it is needed to rebuild the cache file.
     * 
     * This method was called from `getCacheFilePath()`, `MetaFieldsTrait->listObjectsFields()`.
     * 
     * @return bool Return `true` if it is need to build, rebuild the cache file, return `false` if it is not.
     */
    private function isNeedRebuildCache(): bool
    {
        if (is_file($this->storagePath . DIRECTORY_SEPARATOR . $this->storageFile)) {
            // if file exists.
            // check how old is it.
            if ($this->Container->has('Config')) {
                /* @var $Config \Rdb\System\Config */
                $Config = $this->Container->get('Config');
            } else {
                $Config = new \Rdb\System\Config();
            }
            $Config->setModule('RdbAdmin');

            $fileTs = $this->FileSystem->getTimestamp($this->storageFile);
            $dayOld = ((time()-$fileTs)/60/60/24);
            unset($fileTs);

            $cachedExpires = $Config->get('modelCacheExpire', 'cache', 30);
            $Config->setModule('');// restore to default module.

            if ($dayOld > $cachedExpires) {
                // if older than xx days (30 days by default), rebuild cache.
                unset($cachedExpires, $Config, $dayOld);
                return true;
            }

            unset($cachedExpires, $Config, $dayOld);
            return false;
        } else {
            // if file is not exists.
            return true;
        }
    }// isNeedRebuildCache


    /**
     * Load the cache file data into class property. (`storageData`).
     * 
     * Also build it if it is not exists or too old (expired).
     * 
     * You can access cache data via `storageData` property.
     * 
     * @param callable $BuildContent The callback array to build content if needed. Example: `[$this, 'buildCacheContent']`.
     *                              The callback must return content string of generated php data for write to cache.
     */
    protected function loadCacheData(callable $BuildContent)
    {
        if (empty($this->storageData)) {
            $storageFilePath = $this->getCacheFilePath($BuildContent);
            if (is_file($storageFilePath)) {
                $this->storageData = include $storageFilePath;
            }
            unset($storageFilePath);
        }
    }// loadCacheData


    /**
     * Reset cache properties.
     * 
     * This is useful when you call to any methods that have called to `loadCacheData()` method on this trait.<br>
     * This method will be reset `storageData`, `storageFile`, `storagePath` properties.
     * 
     * @since 1.1.1
     */
    public function resetCacheProperties()
    {
        $this->storageData = null;
        $this->storageFile = null;
        $this->storagePath = null;
    }// resetCacheProperties


    /**
     * Reset some properties that was used while get the data using `loadCacheData()`.
     * 
     * This is useful when you call to extended class that is get a cache data row using `loadCacheData()` and you want to call it again or it is in the loop.<br>
     * This method will be reset `storageData`, `storageFile` properties.
     * 
     * @since 1.1.7
     */
    public function resetGetData()
    {
        $this->storageData = null;
        $this->storageFile = null;
    }// resetGetData


}
