<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Models\Traits;


/**
 * Working with create/read/update/delete meta fields tables.
 * 
 * To use meta fields trait, set these properties and method properly in class constructor.
 * <pre>
 * $this->storagePath = STORAGE_PATH . '/cache/Modules/MyModule/Models/my_table_fields';
 * $this->tableName = mytable';
 * $this->objectIdName = 'mytable_object_id';
 * $this->beginMetaFieldsTrait($Container);
 * </pre>
 * 
 * And then it will be able to call `getFields()`, `addFieldsData()`, `updateFieldsData()`, `deleteFieldsData()` methods.
 */
trait MetaFieldsTrait
{


    use CacheFileTrait;


    /**
     * @var array|null The temporary result where the query was called to selected object ID. The result of DB queried will be array wether it is empty or not.<br>
     *              This property's value was set in `listObjectsFields()` method.<br>
     *              This will be reset to `null` once called `buildCacheContent()` method and found that this is not `null`.
     * @since 1.2.9
     */
    protected $builtCacheContent;


    /**
     * @var bool Indicate that `getFields()` and `getFieldsNoCache()` methods contain values or not. The result will be `true` if no value or no data, but will be `false` if there is at least a value or data.
     * @since 1.0.1
     */
    protected $getFieldsNoData = false;


    /**
     * @var int The object_id.
     */
    protected $objectId;


    /**
     * @var string The field name of object_id.
     */
    protected $objectIdName;


    /**
     * @var string Table fields name.
     */
    protected $tableName;


    /**
     * Add a meta field data to meta `_fields` table.
     * 
     * This method is not recommended to call it directly, please call to `updateFieldsData()` method instead and if the data is not exists, it will be call this method automatically.
     * 
     * @param int $objectId Object ID.
     * @param string $field_name Field name.
     * @param mixed $field_value Field value. If it is no scalar then it will be serialize automatically.
     * @param string $field_description Field description.
     * @return mixed Return insert ID on success, or `false` if failure.
     */
    protected function addFieldsData(int $objectId, string $field_name, $field_value, $field_description = false)
    {
        if (!is_scalar($field_description) && !is_null($field_description)) {
            $field_description = null;
        }

        $data = [];
        $data[$this->objectIdName] = $objectId;
        $data['field_name'] = $field_name;
        $data['field_value'] = $this->getFieldValueReformat($field_value, 'insert');
        $data['field_description'] = $field_description;

        $PDO = $this->Db->PDO();
        $insertResult = $this->Db->insert($this->tableName, $data);

        if ($insertResult === true) {
            $output = $PDO->lastInsertId();
        } else {
            $output = false;
        }

        $this->storageFile = $this->getStorageFileName($objectId);
        $this->deleteCachedFile();

        unset($data, $insertResult, $PDO);

        return $output;
    }// addFieldsData


    /**
     * Trait initialize method.
     * 
     * This method must be called before it can be working.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    protected function beginMetaFieldsTrait(\Rdb\System\Container $Container)
    {
        $this->beginCacheFileTrait($Container);
    }// beginMetaFieldsTrait


    /**
     * Call to `bindValue()` from `$Sth` argument.
     * 
     * This is for work with `updateFieldsMultipleData()` method.
     * 
     * @sin 1.2.9
     * @param \PDOStatement $Sth
     * @param array $bindValues The array of bind values where key is placeholder and value is its value.
     */
    private function bindValuesForUpdateFieldsMultipleData(\PDOStatement $Sth, array $bindValues)
    {
        foreach ($bindValues as $bindPlaceholder => $bindValue) {
            $Sth->bindValue($bindPlaceholder, $bindValue);
        }// endforeach;
        unset($bindPlaceholder, $bindValue);
    }// bindValuesForUpdateFieldsMultipleData


    /**
     * Get DB result and build cache content.
     * 
     * This method must be public to be able to called from other method/class.
     * 
     * @return string Return generated data in php language that is ready to use as cache.
     */
    public function buildCacheContent(): string
    {
        if (!is_null($this->builtCacheContent)) {
            $result = $this->builtCacheContent;
            $this->builtCacheContent = null;
        } else {
            $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `' . $this->objectIdName . '` = :object_id';
            /* @var $Pdo \PDO */
            $Pdo = $this->Db->PDO();
            $Sth = $Pdo->prepare($sql);
            $Sth->bindValue(':object_id', $this->objectId);
            $Sth->execute();
            $result = $Sth->fetchAll();
            $Sth->closeCursor();
            unset($Pdo, $sql, $Sth);
        }

        return $this->buildCacheContentFromResult($result);
    }// buildCacheContent


    /**
     * Build/alter the variables for description column for use in `updateFieldsMultipleData()` method.
     * 
     * @sin 1.2.9
     * @param array $dataDesc The input data description.
     * @param array $fieldNamesToCheck The list of field names to check input name (field name) that must be matched. For example field for check with exists (will update), or not exists (will insert).
     * @param array $descPlaceholders The field description placeholders to be altered.
     * @param array $descBindValues The field description for use with bind values to be altered.
     */
    private function buildPlaceholdersAndBindValuesDescForUpdateFieldsMultipleData(
        array $dataDesc,
        array $fieldNamesToCheck,
        array &$descPlaceholders,
        array &$descBindValues
    ) {
        $i = 0;
        foreach ($dataDesc as $field_name => $field_description) {
            if (in_array($field_name, $fieldNamesToCheck)) {
                $descPlaceholders[$i] = ':field_description' . $i;
                $descBindValues[':field_description' . $i] = $field_description;
                ++$i;
            }// endif;
        }// endforeach;
        unset($field_description, $field_name, $i);
    }// buildPlaceholdersAndBindValuesDescForUpdateFieldsMultipleData


    /**
     * Build/alter the variables for use in `updateFieldsMultipleData()` method.
     * 
     * @since 1.2.9
     * @param array $data The input data.
     * @param array $fieldNamesToCheck The list of field names to check input name (field name) that must be matched. For example field for check with exists (will update), or not exists (will insert).
     * @param array $namePlaceholders The field name placeholders to be altered.
     * @param array $nameBindValues The field name for use with bind values to be altered.
     * @param array $valuePlaceholders The field value placeholders to be altered.
     * @param array $valueBindValues The field value for use with bind values to be altered.
     */
    private function buildPlaceholdersAndBindValuesForUpdateFieldsMultipleData(
        array $data,
        array $fieldNamesToCheck,
        array &$namePlaceholders,
        array &$nameBindValues,
        array &$valuePlaceholders,
        array &$valueBindValues
    ) {
        $i = 0;
        foreach ($data as $field_name => $field_value) {
            if (in_array($field_name, $fieldNamesToCheck)) {
                $namePlaceholders[$i] = ':field_name' . $i;
                $nameBindValues[':field_name' . $i] = $field_name;
                $valuePlaceholders[$i] = ':field_value' . $i;
                $valueBindValues[':field_value' . $i] = $field_value;
                ++$i;
            }// endif;
        }// endforaech;
        unset($field_name, $field_value, $i);
    }// buildPlaceholdersAndBindValuesForUpdateFieldsMultipleData


    /**
     * Delete all fields for specific object ID.
     * 
     * Also delete cached data.
     * 
     * @param int $objectId Object ID.
     * @return bool Return `true` on success, `false` for otherwise.
     */
    protected function deleteAllFieldsData(int $objectId): bool
    {
        $data = [];
        $data[$this->objectIdName] = $objectId;

        $result = $this->Db->delete($this->tableName, $data);
        unset($data);

        $this->storageFile = $this->getStorageFileName($objectId);
        $this->deleteCachedFile();

        if (is_bool($result)) {
            return $result;
        }
        return false;
    }// deleteAllFieldsData


    /**
     * Delete a meta field data from meta `_fields` table.
     * 
     * Also delete cached data.
     * 
     * @param int $objectId Object ID.
     * @param string $field_name Field name.
     * @return bool Return `true` on success, `false` for otherwise.
     */
    protected function deleteFieldsData(int $objectId, string $field_name): bool
    {
        $data = [];
        $data[$this->objectIdName] = $objectId;
        $data['field_name'] = $field_name;

        $result = $this->Db->delete($this->tableName, $data);
        unset($data);

        $this->storageFile = $this->getStorageFileName($objectId);
        $this->deleteCachedFile();

        if (is_bool($result)) {
            return $result;
        }
        return false;
    }// deleteFieldsData


    /**
     * Get meta field(s) data by conditions.
     * 
     * @param int $objectId The object ID.
     * @param string $field_name The field name to search in. If this is empty then it will return all fields.
     * @return mixed Return a single row of field or all rows depend on field name to search. If it was not found then return null.<br>
     *                          The return value may be unserialize if it is not scalar and not `null`.<br>
     *                          You can call to property `getFieldsNoData` (boolean) to check that are there any data or value from this method.
     */
    protected function getFields(int $objectId, string $field_name = '')
    {
        $this->storageFile = $this->getStorageFileName($objectId);
        $this->objectId = $objectId;

        $this->loadCacheData([$this, 'buildCacheContent']);

        $this->getFieldsNoData = false;

        if (is_array($this->storageData)) {
            if (!empty($field_name)) {
                foreach ($this->storageData as $item) {
                    if (is_object($item) && isset($item->field_name) && $item->field_name === $field_name) {
                        $item->field_value = $this->getFieldValueReformat($item->field_value, 'read');
                        return $item;
                    }
                }// endforeach;
                unset($item);
            } else {
                foreach ($this->storageData as $item) {
                    $item->field_value = $this->getFieldValueReformat($item->field_value, 'read');
                }// endforeach;
                unset($item);
                return $this->storageData;
            }
        }

        $this->getFieldsNoData = true;

        return null;
    }// getFields


    /**
     * Get meta field(s) data by conditions but no cache.
     * 
     * This method work the same as `getFields()` method but connect to DB without cache to make very sure that data is really exists.
     * 
     * @see \Rdb\Modules\RdbAdmin\Models\Traits::getFields()
     * @since 1.0.1
     * @param int $objectId The object ID.
     * @param string $field_name The field name to search in. If this is empty then it will return all fields.
     * @return mixed Return a single row of field or all rows depend on field name to search. If it was not found then return null.<br>
     *                          The return value may be unserialize if it is not scalar and not `null`.<br>
     *                          You can call to property `getFieldsNoData` (boolean) to check that are there any data or value from this method.
     */
    protected function getFieldsNoCache(int $objectId, string $field_name = '')
    {
        $this->getFieldsNoData = false;

        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `' . $this->objectIdName . '` = :object_id';
        $Pdo = $this->Db->PDO();
        $Sth = $Pdo->prepare($sql);
        $Sth->bindValue(':object_id', $objectId);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Pdo, $sql, $Sth);

        if (!empty($field_name)) {
            foreach ($result as $row) {
                if (is_object($row) && isset($row->field_name) && $row->field_name === $field_name) {
                    $row->field_value = $this->getFieldValueReformat($row->field_value, 'read');
                    return $row;
                }
            }// endforeach;
            unset($result, $row);
        } else {
            foreach ($result as $row) {
                $row->field_value = $this->getFieldValueReformat($row->field_value, 'read');
            }// endforeach;
            unset($row);
            return $result;
        }

        $this->getFieldsNoData = true;

        return null;
    }// getFieldsNoCache


    /**
     * Get field value that will be re-formatted for insert, update, read.
     * 
     * @since 1.2.9
     * @param mixed $field_value The field value data that will be re-formatted.
     * @param srting $getFor Get field value that will be re-formatted for. Accepted value: 'insert', 'update', 'read'.<br>
     *              The 'insert' and 'update' value will use the same algorithm.
     * @return mixed If 'insert', or 'update' value then the field value type scalar or `null` will be return as is. Otherwise it will be serialize.<br>
     *              If 'read' value then it will be un-serialize the field value.
     */
    protected function getFieldValueReformat($field_value, string $getFor = 'update')
    {
        if ('insert' === $getFor || 'update' === $getFor) {
            if (is_scalar($field_value) || is_null($field_value)) {
                return $field_value;
            } else {
                $Serializer = new \Rundiz\Serializer\Serializer();
                return $Serializer->maybeSerialize($field_value);
            }
        } elseif ('read' === $getFor) {
            $Serializer = new \Rundiz\Serializer\Serializer();
            return $Serializer->maybeUnserialize($field_value);
        }
    }// getFieldValueReformat


    /**
     * Get storage file name with .php extension.
     * 
     * @since 1.2.9
     * @param int $objectId The object ID.
     * @return string Return storage file name. Example: object-id-311-user_fields.php
     */
    protected function getStorageFileName(int $objectId): string
    {
        return 'object-id-' . $objectId . '-' . $this->tableName . '.php';
    }// getStorageFileName


    /**
     * List multiple objects and their fields.
     * 
     * @since 1.2.9
     * @param array $objectIds The object IDs to search in.
     * @param string $field_name The field name to search in. If this is empty then it will return all.
     * @return array Return associative array where key is each object ID (int) in the `$objectIds` and its result will be the same as we get from `getFields()` method with `$field_name` parameter.
     */
    protected function listObjectsFields(array $objectIds, string $field_name = ''): array
    {
        // filter out the object ids that is already has a cache in storage file.
        $objectIdsInPlaceholder = [];
        $bindValues = [];
        $i = 0;
        $origObjectIds = $objectIds;
        foreach ($objectIds as $index => $objectId) {
            $this->storageFile = $this->getStorageFileName(intval($objectId));
            if ($this->isNeedRebuildCache() === false) {
                unset($objectIds[$index]);
                continue;
            }
            $objectIdsInPlaceholder[] = ':objectIdsIn' . $i;
            $bindValues[':objectIdsIn' . $i] = intval($objectId);
            ++$i;
        }// endforeach;
        unset($i, $index, $objectId);

        if (!empty($objectIds)) {
            // if $objectIds is not empty. means there are some object IDs that is needed to build cache files.
            // make DB query to retrieve all fields of selected object IDs. -----------------------
            $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `' . $this->objectIdName . '` IN (' . implode(', ', $objectIdsInPlaceholder) . ')';
            /* @var $Sth \PDOStatement */
            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            foreach ($bindValues as $placeholder => $value) {
                $Sth->bindValue($placeholder, $value);
            }// endforeach;
            unset($placeholder, $value);
            $Sth->execute();
            $objectIdResults = $Sth->fetchAll();
            $Sth->closeCursor();
            unset($Sth);
            // end make DB query to retrieve all fields of selected object IDs. -------------------

            // loop build cache files. ------------------------------------------------------------------
            if (is_iterable($objectIdResults)) {
                foreach ($objectIds as $index => $objectId) {
                    $objectId = intval($objectId);
                    $this->storageFile = $this->getStorageFileName($objectId);
                    // create new result only for this object ID.
                    $objectIdResult = [];
                    foreach ($objectIdResults as $lofrIndex => $row) {
                        if (intval($row->{$this->objectIdName}) === $objectId) {
                            $objectIdResult[] = $row;
                            unset($objectIdResults[$lofrIndex]);
                        }
                    }// endforeach;
                    unset($lofrIndex, $row);

                    $this->objectId = $objectId;
                    $this->builtCacheContent = $objectIdResult;
                    $content = $this->buildCacheContent();
                    $this->buildCacheFile($content);
                    unset($content, $objectIdResult);
                }// endforeach;
                unset($index, $objectId);
            }// endif; is iterable `$objectIdResults` property.
            // end loop build cache files. -------------------------------------------------------------

            unset($objectIdResults);
        }// endif; $objectIds is not empty.
        unset($bindValues, $objectIdsInPlaceholder);

        // populate output result. ------------------------------------------------------------------
        $output = [];
        if (is_iterable($origObjectIds)) {
            foreach ($origObjectIds as $objectId) {
                $objectId = intval($objectId);
                $this->resetGetData();
                $output[$objectId] = $this->getFields($objectId, $field_name);
            }// endforeach;
            unset($objectId);
            $this->resetGetData();
        }
        unset($origObjectIds);
        // end populate output result. -------------------------------------------------------------

        return $output;
    }// listObjectsFields


    /**
     * Update a meta field data to meta `_fields` table.
     * 
     * This will be add if the data is not exists.
     * 
     * @param int $objectId Object ID.
     * @param string $field_name Field name.
     * @param mixed $field_value Field value. If field value is not scalar then it will be serialize automatically.
     * @param string|false $field_description Field description. Set to `false` to not change.
     * @param mixed $previousValue Previous field value to check that it must be matched, otherwise it will not be update and return `false`. Set this to `false` to skip checking.
     * @return mixed Return meta field ID if it use add method, return `true` if update success, `false` for otherwise.
     */
    protected function updateFieldsData(int $objectId, string $field_name, $field_value, $field_description = false, $previousValue = false)
    {
        if (!is_scalar($field_description) && !is_null($field_description)) {
            // if not integer, float, string or boolean and not null.
            // set to false for not change.
            $field_description = false;
        }
        if ($field_description === true) {
            $field_description = false;
        }

        if (empty(trim($field_name))) {
            // if field name was not set or was set to empty.
            // return false, not update. 
            return false;
        }

        $result = $this->getFieldsNoCache($objectId, $field_name);
        if ($this->getFieldsNoData === true) {
            unset($result);
            return $this->addFieldsData($objectId, $field_name, $field_value, $field_description);
        }


        if ($previousValue !== false) {
            $Serializer = new \Rundiz\Serializer\Serializer();
            $currentValue = $Serializer->maybeUnserialize($result->field_value);
            if ($currentValue != $previousValue) {
                // if current value is not match to checking value (previous value).
                // return false, not update.
                unset($result, $Serializer);
                return false;
            }
        }
        unset($result);

        $identifier = [];
        $identifier[$this->objectIdName] = $objectId;
        $identifier['field_name'] = $field_name;
        $data = [];
        $data['field_value'] = $this->getFieldValueReformat($field_value);
        if ($field_description !== false) {
            $data['field_description'] = $field_description;
        }

        $updateResult = $this->Db->update($this->tableName, $data, $identifier);
        unset($data, $identifier);

        $this->storageFile = $this->getStorageFileName($objectId);
        $this->deleteCachedFile();

        return $updateResult;
    }// updateFieldsData


    /**
     * Update `_fields` table on multiple values.
     * 
     * It will be update if data exists, or it will be insert if data is not exists.
     * 
     * @param array $data Associative array where key is match `field_name` column and value is match `field_value` column.
     * @param array $dataDesc Associative array of field description where array key is the `field_name`column and its value is match `field_description` column.
     * @return bool Return `true` if **all** data have been updated, `false` for otherwise.
     * @throws \OutOfRangeException Throw the exception if `$dataDesc` is not empty but have not same amount of array values.
     */
    protected function updateFieldsMultipleData(int $objectId, array $data, array $dataDesc = []): bool
    {
        // check arguments. -----------------
        if (!empty($dataDesc)) {
            if (count($data) !== count($dataDesc)) {
                throw new \OutOfRangeException('The argument $data and $dataDesc must have the same amount of array values.');
            }
        }// endif;

        if (empty($data)) {
            return true;
        }
        // end check arguments. -----------

        // retrieve check results, all at once. ----------------------------------------------------
        $sql = 'SELECT `field_name` FROM `' . $this->tableName . '`
            WHERE `' . $this->objectIdName . '` = ?
                AND `field_name` IN (' . rtrim(str_repeat('?, ', count($data)), " \n\r\t\v\x00,") . ')';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        $Sth->execute(array_merge([$objectId], array_keys($data)));
        $checkResults = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Sth);

        $fieldNameExists = [];
        if (!empty($checkResults)) {
            foreach ($checkResults as $row) {
                $fieldNameExists[] = $row->field_name;
            }// endforeach;
            unset($row);
        }
        unset($checkResults);
        $fieldNameNotExists = array_diff(array_keys($data), $fieldNameExists);
        // end retrieve check results, all at once. -----------------------------------------------

        // build bind placeholders & values for `INSERT`. -------------
        $insertFnPlaceholders = [];
        $insertFnBindValues = [];
        $insertFvPlaceholders = [];
        $insertFvBindValues = [];
        $this->buildPlaceholdersAndBindValuesForUpdateFieldsMultipleData(
            $data,
            $fieldNameNotExists,
            $insertFnPlaceholders,
            $insertFnBindValues,
            $insertFvPlaceholders,
            $insertFvBindValues
        );

        if (!empty($dataDesc)) {
            $insertFdPlaceholders = [];
            $insertFdBindValues = [];
            $this->buildPlaceholdersAndBindValuesDescForUpdateFieldsMultipleData(
                $dataDesc,
                $fieldNameNotExists,
                $insertFdPlaceholders,
                $insertFdBindValues
            );
        }
        unset($fieldNameNotExists);
        // end build bind placeholders & values for `INSERT`. --------

        // build bind placeholders & values for `UPDATE`. ------------
        $updateFnPlaceholders = [];
        $updateFnBindValues = [];
        $updateFvPlaceholders = [];
        $updateFvBindValues = [];
        $this->buildPlaceholdersAndBindValuesForUpdateFieldsMultipleData(
            $data,
            $fieldNameExists,
            $updateFnPlaceholders,
            $updateFnBindValues,
            $updateFvPlaceholders,
            $updateFvBindValues
        );

        if (!empty($dataDesc)) {
            $updateFdPlaceholders = [];
            $updateFdBindValues = [];
            $this->buildPlaceholdersAndBindValuesDescForUpdateFieldsMultipleData(
                $dataDesc,
                $fieldNameExists,
                $updateFdPlaceholders,
                $updateFdBindValues
            );
        }
        unset($fieldNameExists);
        // end build bind placeholders & values for `UPDATE`. -------

        // execute `INSERT` command. --------------------------------
        if (!empty($insertFnPlaceholders)) {
            $sql = 'INSERT INTO `' . $this->tableName . '` (`' . $this->objectIdName . '`, `field_name`, `field_value`' . (!empty($dataDesc) ? ', `field_description`' : '') . ')' . PHP_EOL;
            $sql .= 'VALUES' . PHP_EOL;
            $totalPlaceholders = count($insertFnPlaceholders);
            for ($i = 0; $i < $totalPlaceholders; ++$i) {
                $sql .= '    (' . $objectId . ', ' . $insertFnPlaceholders[$i] . ', ' . $insertFvPlaceholders[$i] . ', ';
                if (isset($insertFdPlaceholders) && is_array($insertFdPlaceholders) && array_key_exists($i, $insertFdPlaceholders)) {
                    $sql .= $insertFdPlaceholders[$i];
                } else {
                    $sql .= '`field_description`';
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
            $this->bindValuesForUpdateFieldsMultipleData($Sth, $insertFnBindValues);
            $this->bindValuesForUpdateFieldsMultipleData($Sth, $insertFvBindValues);
            if (isset($insertFdBindValues) && is_array($insertFdBindValues)) {
                $this->bindValuesForUpdateFieldsMultipleData($Sth, $insertFdBindValues);
            }
            unset($insertFdBindValues, $insertFdPlaceholders);
            unset($insertFnBindValues, $insertFnPlaceholders, $insertFvBindValues, $insertFvPlaceholders);
            $Sth->execute();
            $insertResult = $Sth->closeCursor();
            unset($Sth);
        }// endif; insert `field_name` placeholders is not empty.
        // end execute `INSERT` command. ---------------------------

        // execute `UPDATE` command. -------------------------------
        if (!empty($updateFnPlaceholders)) {
            $sql = 'UPDATE `' . $this->tableName . '`' . PHP_EOL;
            $sql .= '    SET `field_value` = CASE' . PHP_EOL;
            $totalPlaceholders = count($updateFnPlaceholders);
            for ($i = 0; $i < $totalPlaceholders; ++$i) {
                $sql .= '        WHEN `field_name` = ' . $updateFnPlaceholders[$i] . ' THEN ' . $updateFvPlaceholders[$i] . PHP_EOL;
            }// endfor;
            unset($i, $totalPlaceholders);
            $sql .= '    END';
            if (!empty($updateFdPlaceholders)) {
                $sql .= ',';
            }
            $sql .= PHP_EOL;
            if (!empty($updateFdPlaceholders)) {
                $sql .= '    `field_description` = CASE' . PHP_EOL;
                $totalPlaceholders = count($updateFdPlaceholders);
                for ($i = 0; $i < $totalPlaceholders; ++$i) {
                    $sql .= '        WHEN `field_name` = ' . $updateFnPlaceholders[$i] . ' THEN ' . $updateFdPlaceholders[$i] . PHP_EOL;
                }// endfor;
                unset($i, $totalPlaceholders);
                $sql .= '    END' . PHP_EOL;
            }// endif; field_description placeholders is not empty.
            $sql .= 'WHERE `' . $this->objectIdName . '` = :objectId AND `field_name` IN (' . implode(', ', $updateFnPlaceholders) . ')' . PHP_EOL;

            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            $Sth->bindValue(':objectId', $objectId, \PDO::PARAM_INT);
            $this->bindValuesForUpdateFieldsMultipleData($Sth, $updateFnBindValues);
            $this->bindValuesForUpdateFieldsMultipleData($Sth, $updateFvBindValues);
            if (isset($updateFdBindValues) && is_array($updateFdBindValues)) {
                $this->bindValuesForUpdateFieldsMultipleData($Sth, $updateFdBindValues);
            }
            unset($updateFdBindValues, $updateFdPlaceholders);
            unset($updateFnBindValues, $updateFnPlaceholders, $updateFvBindValues, $updateFvPlaceholders);
            $Sth->execute();
            $updateResult = $Sth->closeCursor();
            unset($Sth);
        }// endif; update `field_name` placeholders is not empty.
        // end execute `UPDATE` command. --------------------------

        $this->storageFile = $this->getStorageFileName($objectId);
        $this->deleteCachedFile();

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
    }// updateFieldsMultipleData


}
