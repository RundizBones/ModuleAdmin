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
     * Call to `bindValue()` from `$Sth` argument.
     * 
     * This is for work with `updateMultipleValues()` method.
     * 
     * @sin 1.2.9
     * @param \PDOStatement $Sth
     * @param array $bindValues The array of bind values where key is placeholder and value is its value.
     */
    private function bindValuesForUpdateMultipleVals(\PDOStatement $Sth, array $bindValues)
    {
        foreach ($bindValues as $bindPlaceholder => $bindValue) {
            $Sth->bindValue($bindPlaceholder, $bindValue);
        }// endforeach;
        unset($bindPlaceholder, $bindValue);
    }// bindValuesForUpdateMultipleVals


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
     * Build/alter the variables for description column for use in `updateMultipleValues()` method.
     * 
     * @sin 1.2.9
     * @param array $dataDesc The input data description.
     * @param array $configNamesToCheck The list of config names to check input name (config name) that must be matched. For example config for check with exists (will update), or not exists (will insert).
     * @param array $cfdPlaceholders The config description placeholders to be altered.
     * @param array $cfdBindValues The config description for use with bind values to be altered.
     */
    private function buildPlaceholdersAndBindValuesDescForUpdateMultipleVals(
        array $dataDesc,
        array $configNamesToCheck,
        array &$cfdPlaceholders,
        array &$cfdBindValues
    ) {
        $i = 0;
        foreach ($dataDesc as $config_name => $config_description) {
            if (in_array($config_name, $configNamesToCheck)) {
                $cfdPlaceholders[$i] = ':config_description' . $i;
                $cfdBindValues[':config_description' . $i] = $config_description;
                ++$i;
            }// endif;
        }// endforeach;
        unset($config_description, $config_name, $i);
    }// buildPlaceholdersAndBindValuesDescForUpdateMultipleVals


    /**
     * Build/alter the variables for use in `updateMultipleValues()` method.
     * 
     * @since 1.2.9
     * @param array $data The input data.
     * @param array $configNamesToCheck The list of config names to check input name (config name) that must be matched. For example config for check with exists (will update), or not exists (will insert).
     * @param array $configPrefixes The config prefixes to use later with clear cache. This value will be alter.
     * @param array $cfnPlaceholders The config name placeholders to be altered.
     * @param array $cfnBindValues The config name for use with bind values to be altered.
     * @param array $cfvPlaceholders The config value placeholders to be altered.
     * @param array $cfvBindValues The config value for use with bind values to be altered.
     */
    private function buildPlaceholdersAndBindValuesForUpdateMultipleVals(
        array $data,
        array $configNamesToCheck,
        array &$configPrefixes, 
        array &$cfnPlaceholders,
        array &$cfnBindValues,
        array &$cfvPlaceholders,
        array &$cfvBindValues
    ) {
        $i = 0;
        foreach ($data as $config_name => $config_value) {
            if (in_array($config_name, $configNamesToCheck)) {
                $configPrefixes[] = $this->getConfigPrefix($config_name);
                $cfnPlaceholders[$i] = ':config_name' . $i;
                $cfnBindValues[':config_name' . $i] = $config_name;
                $cfvPlaceholders[$i] = ':config_value' . $i;
                $cfvBindValues[':config_value' . $i] = $config_value;
                ++$i;
            }// endif;
        }// endforaech;
        unset($config_name, $config_value, $i);
    }// buildPlaceholdersAndBindValuesForUpdateMultipleVals


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
     * @return bool Return `true` if **all** data have been updated, `false` for otherwise.
     * @throws \OutOfRangeException Throw the exception if `$dataDesc` is not empty but have not same amount of array values.
     */
    public function updateMultipleValues(array $data, array $dataDesc = []): bool
    {
        // use `INSERT ... ON DUPLICATE KEY UPDATE` ( https://stackoverflow.com/a/34866431/128761 ) can cause the data rows re-order and may have unwanted records.

        // check arguments. -----------------
        if (!empty($dataDesc)) {
            if (count($data) !== count($dataDesc)) {
                throw new \OutOfRangeException('The argument $data and $dataDesc must have the same amount of array values.');
            }
        }// endif;

        if (empty($data)) {
            return false;
        }
        // end check arguments. -----------

        // retrieve check results, all at once. ----------------------------------------------------
        $sql = 'SELECT `config_name` FROM `rdb_config` WHERE `config_name` IN (' . rtrim(str_repeat('?, ', count($data)), " \n\r\t\v\x00,") . ')';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        $Sth->execute(array_keys($data));
        $checkResults = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);

        $configNameExists = [];
        if (!empty($checkResults)) {
            foreach ($checkResults as $row) {
                $configNameExists[] = $row->config_name;
            }// endforeach;
            unset($row);
        }
        unset($checkResults);
        $configNameNotExists = array_diff(array_keys($data), $configNameExists);
        // end retrieve check results, all at once. -----------------------------------------------

        $configPrefixes = [];// for use on the bottom of this method to clear config cache.
        // build bind placeholders & values for `INSERT`. -------------
        $insertCfnPlaceholders = [];
        $insertCfnBindValues = [];
        $insertCfvPlaceholders = [];
        $insertCfvBindValues = [];
        $this->buildPlaceholdersAndBindValuesForUpdateMultipleVals(
            $data,
            $configNameNotExists,
            $configPrefixes,
            $insertCfnPlaceholders,
            $insertCfnBindValues,
            $insertCfvPlaceholders,
            $insertCfvBindValues
        );

        if (!empty($dataDesc)) {
            $insertCfdPlaceholders = [];
            $insertCfdBindValues = [];
            $this->buildPlaceholdersAndBindValuesDescForUpdateMultipleVals(
                $dataDesc,
                $configNameNotExists,
                $insertCfdPlaceholders,
                $insertCfdBindValues
            );
        }
        unset($configNameNotExists);
        // end build bind placeholders & values for `INSERT`. --------

        // build bind placeholders & values for `UPDATE`. ------------
        $updateCfnPlaceholders = [];
        $updateCfnBindValues = [];
        $updateCfvPlaceholders = [];
        $updateCfvBindValues = [];
        $this->buildPlaceholdersAndBindValuesForUpdateMultipleVals(
            $data,
            $configNameExists,
            $configPrefixes,
            $updateCfnPlaceholders,
            $updateCfnBindValues,
            $updateCfvPlaceholders,
            $updateCfvBindValues
        );

        if (!empty($dataDesc)) {
            $updateCfdPlaceholders = [];
            $updateCfdBindValues = [];
            $this->buildPlaceholdersAndBindValuesDescForUpdateMultipleVals(
                $dataDesc,
                $configNameExists,
                $updateCfdPlaceholders,
                $updateCfdBindValues
            );
        }
        unset($configNameExists);
        // end build bind placeholders & values for `UPDATE`. -------

        // execute `INSERT` command. --------------------------------
        if (!empty($insertCfnPlaceholders)) {
            $sql = 'INSERT INTO `' . $this->Db->tableName('config') . '` (`config_name`, `config_value`' . (!empty($dataDesc) ? ', `config_description`' : '') . ')' . PHP_EOL;
            $sql .= 'VALUES' . PHP_EOL;
            $totalPlaceholders = count($insertCfnPlaceholders);
            for ($i = 0; $i < $totalPlaceholders; ++$i) {
                $sql .= '    (' . $insertCfnPlaceholders[$i] . ', ' . $insertCfvPlaceholders[$i];
                if (isset($insertCfdPlaceholders) && is_array($insertCfdPlaceholders) && array_key_exists($i, $insertCfdPlaceholders)) {
                    $sql .= ', ' . $insertCfdPlaceholders[$i];
                }
                $sql .= ')';
                if (($i + 1) < $totalPlaceholders) {
                    $sql .= ', ';
                }
                $sql .= PHP_EOL;
            }// endfor;
            unset($i, $totalPlaceholders);

            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            $this->bindValuesForUpdateMultipleVals($Sth, $insertCfnBindValues);
            $this->bindValuesForUpdateMultipleVals($Sth, $insertCfvBindValues);
            if (isset($insertCfdBindValues) && is_array($insertCfdBindValues)) {
                $this->bindValuesForUpdateMultipleVals($Sth, $insertCfdBindValues);
            }
            unset($insertCfdBindValues, $insertCfdPlaceholders);
            unset($insertCfnBindValues, $insertCfnPlaceholders, $insertCfvBindValues, $insertCfvPlaceholders);
            $Sth->execute();
            $insertResult = $Sth->closeCursor();
            unset($Sth);
        }// endif; insert `config_name` placeholders is not empty.
        // end execute `INSERT` command. ---------------------------

        // execute `UPDATE` command. -------------------------------
        if (!empty($updateCfnPlaceholders)) {
            $sql = 'UPDATE `' . $this->Db->tableName('config') . '`' . PHP_EOL;
            $sql .= '    SET `config_value` = CASE' . PHP_EOL;
            $totalPlaceholders = count($updateCfnPlaceholders);
            for ($i = 0; $i < $totalPlaceholders; ++$i) {
                $sql .= '        WHEN `config_name` = ' . $updateCfnPlaceholders[$i] . ' THEN ' . $updateCfvPlaceholders[$i] . PHP_EOL;
            }// endfor;
            unset($i, $totalPlaceholders);
            $sql .= '    END';
            if (!empty($updateCfdPlaceholders)) {
                $sql .= ',';
            }
            $sql .= PHP_EOL;
            if (!empty($updateCfdPlaceholders)) {
                $sql .= '    `config_description` = CASE' . PHP_EOL;
                $totalPlaceholders = count($updateCfdPlaceholders);
                for ($i = 0; $i < $totalPlaceholders; ++$i) {
                    $sql .= '        WHEN `config_name` = ' . $updateCfnPlaceholders[$i] . ' THEN ' . $updateCfdPlaceholders[$i] . PHP_EOL;
                }// endfor;
                unset($i, $totalPlaceholders);
                $sql .= '    END' . PHP_EOL;
            }// endif; config_description placeholders is not empty.
            $sql .= 'WHERE `config_name` IN (' . implode(', ', $updateCfnPlaceholders) . ')' . PHP_EOL;

            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            $this->bindValuesForUpdateMultipleVals($Sth, $updateCfnBindValues);
            $this->bindValuesForUpdateMultipleVals($Sth, $updateCfvBindValues);
            if (isset($updateCfdBindValues) && is_array($updateCfdBindValues)) {
                $this->bindValuesForUpdateMultipleVals($Sth, $updateCfdBindValues);
            }
            unset($updateCfdBindValues, $updateCfdPlaceholders);
            unset($updateCfnBindValues, $updateCfnPlaceholders, $updateCfvBindValues, $updateCfvPlaceholders);
            $Sth->execute();
            $updateResult = $Sth->closeCursor();
            unset($Sth);
        }// endif; update `config_name` placeholders is not empty.
        // end execute `UPDATE` command. --------------------------

        // loop delete cache from config names. --------------
        $configPrefixes = array_unique($configPrefixes);
        foreach ($configPrefixes as $configPrefix) {
            $this->configPrefix = $configPrefix;
            $this->storageFile = str_replace('%MODULENAME%', $configPrefix, $this->storageFileTemplate);
            // clear cache.
            $this->deleteCachedFile();
        }// endforeach;
        unset($configPrefix, $configPrefixes);
        // end loop delete cache from config names. ---------

        return (
            (
                isset($insertResult) && 
                isset($updateResult) &&
                true === $insertResult &&
                true === $updateResult
            ) ||
            (
                isset($insertResult) && 
                true === $insertResult &&
                !isset($updateResult)
            ) ||
            (
                !isset($insertResult) && 
                isset($updateResult) &&
                true === $updateResult
            )
        );
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
