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
     * Add meta field data.
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

        $Serializer = new \Rundiz\Serializer\Serializer();
        $data = [];
        $data[$this->objectIdName] = $objectId;
        $data['field_name'] = $field_name;
        $data['field_value'] = (is_scalar($field_value) || is_null($field_value) ? $field_value : $Serializer->maybeSerialize($field_value));
        $data['field_description'] = $field_description;
        unset($Serializer);

        $PDO = $this->Db->PDO();
        $PDO->beginTransaction();
        $insertResult = $this->Db->insert($this->tableName, $data);

        if ($insertResult === true) {
            $output = $PDO->lastInsertId();
        } else {
            $output = false;
        }

        $this->storageFile = 'object-id-' . $objectId . '-' . $this->tableName . '.php';
        $this->deleteCachedFile();

        $PDO->commit();
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
        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `' . $this->objectIdName . '` = :object_id';
        $Pdo = $this->Db->PDO();
        $Sth = $Pdo->prepare($sql);
        $Sth->bindValue(':object_id', $this->objectId);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($Pdo, $sql, $Sth);

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

        $this->storageFile = 'object-id-' . $objectId . '-' . $this->tableName . '.php';
        $this->deleteCachedFile();

        if (is_bool($result)) {
            return $result;
        }
        return false;
    }// deleteAllFieldsData


    /**
     * Delete meta field data.
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

        $this->storageFile = 'object-id-' . $objectId . '-' . $this->tableName . '.php';
        $this->deleteCachedFile();

        if (is_bool($result)) {
            return $result;
        }
        return false;
    }// deleteFieldsData


    /**
     * Get meta fields data by conditions.
     * 
     * @param int $objectId The object ID.
     * @param string $field_name The field name to search in. If this is empty then it will return all.
     * @return mixed Return a single row of field or all rows depend on field name to search. If it was not found then return null.<br>
     *                          The return value may be unserialize if it is not scalar and not `null`.
     */
    protected function getFields(int $objectId, string $field_name = '')
    {
        $this->storageFile = 'object-id-' . $objectId . '-' . $this->tableName . '.php';
        $this->objectId = $objectId;

        $this->loadCacheData([$this, 'buildCacheContent']);

        if (is_array($this->storageData)) {
            $Serializer = new \Rundiz\Serializer\Serializer();

            if (!empty($field_name)) {
                foreach ($this->storageData as $item) {
                    if (is_object($item) && isset($item->field_name) && $item->field_name === $field_name) {
                        $item->field_value = $Serializer->maybeUnserialize($item->field_value);
                        return $item;
                    }
                }// endforeach;
                unset($item);
            } else {
                foreach ($this->storageData as $item) {
                    $item->field_value = $Serializer->maybeUnserialize($item->field_value);
                }// endforeach;
                unset($item);
                return $this->storageData;
            }

            unset($Serializer);
        }

        return null;
    }// getFields


    /**
     * Update meta field data.
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

        $result = $this->getFields($objectId, $field_name);
        if (empty($result)) {
            unset($result);
            return $this->addFieldsData($objectId, $field_name, $field_value, $field_description);
        }

        $Serializer = new \Rundiz\Serializer\Serializer();

        if ($previousValue !== false) {
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
        $data['field_value'] = (is_scalar($field_value) || is_null($field_value) ? $field_value : $Serializer->maybeSerialize($field_value));
        if ($field_description !== false) {
            $data['field_description'] = $field_description;
        }
        unset($Serializer);

        $updateResult = $this->Db->update($this->tableName, $data, $identifier);
        unset($data, $identifier);

        $this->storageFile = 'object-id-' . $objectId . '-' . $this->tableName . '.php';
        $this->deleteCachedFile();

        return $updateResult;
    }// updateFieldsData


}
