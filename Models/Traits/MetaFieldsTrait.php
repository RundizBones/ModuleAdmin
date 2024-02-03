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


}
