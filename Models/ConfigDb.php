<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Models;


/**
 * Config DB model.
 * 
 * @since 0.1
 */
class ConfigDb extends \Rdb\System\Core\Models\BaseModel
{


    use Traits\CacheFileTrait;


    /**
     * @var string Config prefix which is module name.
     */
    private $configPrefix;


    /**
     * @var string Config cache file name for replacement.
     */
    private $storageFileTemplate = 'config-module-%MODULENAME%.php';


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->storagePath = STORAGE_PATH . '/cache/Modules/RdbAdmin/Models/ConfigDb';
        if ($Container->has('Modules')) {
            /* @var $Modules \Rdb\System\Modules */
            $Modules = $Container->get('Modules');
            $Modules->setCurrentModule(get_called_class());
            unset($Modules);
        }
        $this->beginCacheFileTrait($Container);

        parent::__construct($Container);
    }// __construct


    /**
     * Add a config data.
     * 
     * @param array $data The associative array where key is column name and value is its value.
     * @return mixed Return inserted ID if successfully inserted, return `0` (zero), or `false` if failed to insert.
     */
    public function add(array $data)
    {
        $insertResult = $this->Db->insert($this->Db->tableName('config'), $data);
        if ($insertResult === true) {
            // if success insert.
            if (isset($data['config_name'])) {
                $this->storageFile = str_replace('%MODULENAME%', $this->getConfigPrefix($data['config_name']), $this->storageFileTemplate);
            }
            // clear cache.
            $this->deleteCachedFile();

            return $this->Db->PDO()->lastInsertId();
        }
        return false;
    }// add


    /**
     * Get DB result and build cache content.
     * 
     * @return string Return generated data in php language that is ready to use as cache.
     */
    public function buildCacheContent()
    {
        $sql = 'SELECT * FROM `' . $this->Db->tableName('config') . '` WHERE `config_name` LIKE :configPrefix';
        $Pdo = $this->Db->PDO();
        $Sth = $Pdo->prepare($sql);
        $Sth->bindValue(':configPrefix', $this->configPrefix . '_%');
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Pdo, $sql, $Sth);

        $output = '<?php' . PHP_EOL .
            '/**' . PHP_EOL .
            ' * Auto generate.' . PHP_EOL .
            ' */' . PHP_EOL . 
            PHP_EOL .
            'return [' . PHP_EOL;

        if (is_array($result)) {
            foreach ($result as $row) {
                $configDescription = ($row->config_description ?? '');
                $output .= '    // ' . str_replace(array("\r\n", "\r", "\n"), '', $configDescription) . PHP_EOL .
                    '    (object) [' . PHP_EOL;
                if (is_object($row)) {
                    foreach ($row as $fieldName => $fieldValue) {
                        $output .= '        \'' . $fieldName . '\' => ' . var_export($fieldValue, true) . ',' . PHP_EOL;
                    }// endforeach;
                    unset($fieldName, $fieldValue);
                }
                $output .= '    ],' . PHP_EOL;
            }// endforeach;
            unset($configDescription, $row);
        }

        unset($result);
        $output .= '];' . PHP_EOL;// end return [...];

        return $output;
    }// buildCacheContent


    /**
     * Get config value(s).
     * 
     * Example for get single config name:
     * <pre>
     * $ConfigDb->get('config_name', 'default_value');
     * </pre>
     * 
     * Example for get multiple config name:
     * <pre>
     * $names = ['config_name1', 'config_name2'];
     * $defaults = ['default_value1', 'default_value2'];
     * $ConfigDb->get($names, $defaults);
     * </pre>
     * 
     * @param array|string $name The config name can be string (name) or array (names).
     * @param array|string $default The default config value if this configuration is not exists or have no value. This can be any types but if name is array then this will always be array.
     * @return array|string Return config value.<br>
     *                                  If name is array then it will always return the array with name in the key. If config was not found then it will return default value matched in the array index.<br>
     *                                  If name is string then it will data type as value contain in db. If config was not found then it will return `$default` argument.
     * @throws \InvalidArgumentException Throw the error if `$name` is not string and `$name, `$default` is different type. If one is array the other one must be array.
     */
    public function get($name, $default = '')
    {
        if (!is_scalar($name) && gettype($name) !== gettype($default)) {
            throw new \InvalidArgumentException('The argument $default must be the same type with $name.');
        }

        if (is_array($name)) {
            return $this->getMultiple($name, $default);
        }

        $item = $this->getRow($name, $default);

        if (is_object($item) && isset($item->config_value)) {
            return $item->config_value;
        }
        unset($item);

        return $default;
    }// get


    /**
     * Get config prefix from config name.
     * 
     * Basically the config prefix is module name, get the prefix for use with build cache in smaller size.<br>
     * This method also set `configPrefix` property.
     * 
     * @param string $name Config name.
     * @return string Return prefix of config.
     */
    protected function getConfigPrefix(string $name): string
    {
        $expName = explode('_', $name);
        if (isset($expName[0]) && is_string($expName[0])) {
            $this->configPrefix = $expName[0];
            return $expName[0];
        }

        unset($expName);
        return $name;
    }// getConfigPrefix


    /**
     * Get config values.
     * 
     * This method was called from `get()` method.
     * 
     * @param array $names The config names.
     * @param array $defaults The default values that must be matched the array key of `$names`.
     * @return array Always return array with the name in key.
     * @throws \InvalidArgumentException Throw error if total array values of `$names` and `$defaults` is not matched and `$defaults` is not an empty array.
     */
    protected function getMultiple(array $names, array $defaults = []): array
    {
        if (count($names) !== count($defaults) && !empty($defaults)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Total array values of `$names` and `$defaults` argument must be matched. ($names: %1$d != $defaults: %2$d)', 
                    count($names), 
                    count($defaults)
                )
            );
        }

        $output = [];

        foreach ($names as $key => $name) {
            $item = $this->getRow($name, ($defaults[$key] ?? ''));

            if (is_object($item) && isset($item->config_value)) {
                $output[$name] = $item->config_value;
            } else {
                $output[$name] = $item;
            }

            unset($item);
        }// endforeach;
        unset($key, $name);

        return $output;
    }// getMultiple


    /**
     * Get config data as object (DB row) by name condition.
     * 
     * This method was called from `get()`, `getMultiple()` methods.
     * 
     * @param string $name The name of config to search.
     * @param mixed $default The default config value if this configuration is not exists.
     * @return mixed Return object if found, return `$default` if not found.
     */
    protected function getRow(string $name, $default = '')
    {
        $this->storageFile = str_replace('%MODULENAME%', $this->getConfigPrefix($name), $this->storageFileTemplate);
        $this->loadCacheData([$this, 'buildCacheContent']);

        if (is_array($this->storageData)) {
            foreach ($this->storageData as $item) {
                if (is_object($item) && isset($item->config_name) && isset($item->config_value)) {
                    if ($item->config_name === $name) {
                        return $item;
                    }
                }
            }// endforeach;
            unset($item);
        }

        return $default;
    }// getRow


    /**
     * Update configuration DB.
     * 
     * This will be add if the data is not exists.
     * 
     * @param array $data The associative array where its key is column name and value is its value to update.
     * @param array $where The associative array where its key is column name and value is its value.
     * @return bool Return `true` on success update, `false` for otherwise.
     */
    public function update(array $data, array $where): bool
    {
        if (isset($where['config_name'])) {
            $checkResult = $this->get($where['config_name'], null);
            if ($checkResult === null) {
                if (!isset($data['config_name'])) {
                    $data['config_name'] = $where['config_name'];
                }
                unset($checkResult);
                return $this->add($data);
            }
            unset($checkResult);
        }

        $result = $this->Db->update($this->Db->tableName('config'), $data, $where);

        if ($result === true) {
            // if success update.
            if (isset($where['config_name'])) {
                $this->storageFile = str_replace('%MODULENAME%', $this->getConfigPrefix($where['config_name']), $this->storageFileTemplate);
            }
            // clear cache.
            $this->deleteCachedFile();
        }

        return $result;
    }// update


    /**
     * Update multiple values.
     * 
     * @param array $data Associative array where key is match `config_name` column and value is match `config_value` column.
     * @param array $dataDesc Associative array of config description where array key is the `config_name`column and its value is match `config_description` column.
     * @return bool Return `true` if **all** data has been updated, `false` for otherwise.
     */
    public function updateMultipleValues(array $data, array $dataDesc = []): bool
    {
        $i = 0;
        $configPrefixes = [];
        foreach ($data as $config_name => $config_value) {
            $configPrefixes[] = $this->getConfigPrefix($config_name);
            $sql = 'SELECT `config_name` FROM `' . $this->Db->tableName('config') . '` WHERE `config_name` = :config_name';
            $Sth = $this->Db->PDO()->prepare($sql);
            $Sth->bindValue(':config_name', $config_name);
            $Sth->execute();
            $checkResult = $Sth->fetchObject();
            $Sth->closeCursor();
            unset($sql, $Sth);
            if (empty($checkResult) || is_null($checkResult) || !is_object($checkResult)) {
                $insertData = [
                    'config_name' => $config_name,
                    'config_value' => $config_value,
                ];
                if (array_key_exists($config_name, $dataDesc) && is_scalar($dataDesc[$config_name])) {
                    $insertData['config_description'] = $dataDesc[$config_name];
                }
                $result = $this->Db->insert($this->Db->tableName('config'), $insertData);
                unset($insertData);
            } else {
                $updateData = [
                    'config_value' => $config_value,
                ];
                if (array_key_exists($config_name, $dataDesc) && is_scalar($dataDesc[$config_name])) {
                    $updateData['config_description'] = $dataDesc[$config_name];
                }
                $result = $this->Db->update($this->Db->tableName('config'), $updateData, ['config_name' => $config_name]);
            }

            if ($result === true) {
                $i++;
            }
            unset($checkResult,  $result);
        }// endforeach;
        unset($config_name, $config_value);

        // loop delete cache from config names.
        $configPrefixes = array_unique($configPrefixes);
        foreach ($configPrefixes as $configPrefix) {
            $this->configPrefix = $configPrefix;
            $this->storageFile = str_replace('%MODULENAME%', $configPrefix, $this->storageFileTemplate);
            // clear cache.
            $this->deleteCachedFile();
        }// endforeach;
        unset($configPrefix, $configPrefixes);

        if ($i == count($data)) {
            return true;
        } else {
            return false;
        }
    }// updateMultipleValues


    /**
     * Update configuration value only.
     * 
     * @param mixed $value The config value to update.
     * @param string $name The config name to search for.
     * @return bool Return `true` on success update, `false` for otherwise.
     */
    public function updateValue($value, string $name): bool
    {
        if (!is_scalar($value) && !is_null($value)) {
            $Serializer = new \Rundiz\Serializer\Serializer();
            $value = $Serializer->maybeSerialize($value);
            unset($Serializer);
        }

        $data = [];
        $data['config_value'] = $value;
        $where = [];
        $where['config_name'] = $name;

        return $this->update($data, $where);
    }// updateValue


}
