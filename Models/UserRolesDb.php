<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Models;


/**
 * User roles DB model.
 * 
 * @since 0.1
 */
class UserRolesDb extends \Rdb\System\Core\Models\BaseModel
{


    /**
     * @var array Allowed sort columns in db.
     */
    protected $allowedSort = ['userrole_name', 'userrole_priority', 'userrole_create', 'userrole_create_gmt', 'userrole_lastupdate', 'userrole_lastupdate_gmt'];


    /**
     * @var array Restrict priority that cannot re-order, delete them. The priority numbers in the array MUST be integer.
     */
    protected $restrictedPriority = [1, 9999, 10000];


    /**
     * @var string Table name that already added prefix.
     */
    protected $tableName;


    /**
     * Class constructor
     * 
     * @inheritDoc
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->tableName = $this->Db->tableName('user_roles');
    }// __construct


    /**
     * Magic __get
     */
    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
    }// __get


    /**
     * Add user role data.
     * 
     * @param array $data The associative array where key is column name and value is its value.
     * @return mixed Return inserted ID if successfully inserted, return `0` (zero), or `false` if failed to insert.
     */
    public function add(array $data)
    {
        $defaultData = [];
        $defaultData['userrole_create'] = date('Y-m-d H:i:s');
        $defaultData['userrole_create_gmt'] = gmdate('Y-m-d H:i:s');
        $defaultData['userrole_lastupdate'] = date('Y-m-d H:i:s');
        $defaultData['userrole_lastupdate_gmt'] = gmdate('Y-m-d H:i:s');

        $data = array_merge($defaultData, $data);
        unset($defaultData);

        $data['userrole_priority'] = $this->getNewPriority();

        $insertResult = $this->Db->insert($this->tableName, $data);
        if ($insertResult === true) {
            return $this->Db->PDO()->lastInsertId();
        }
        return false;
    }// add


    /**
     * Delete user roles with roles in `users_roles` table.
     * 
     * @param array $roleIds The role IDs.
     * @return bool Return `true` on success, `false` for otherwise.
     */
    public function delete(array $roleIds): bool
    {
        $sql = 'DELETE FROM `' . $this->tableName . '` WHERE';
        $values = [];
        $placeholders = [];
        foreach ($roleIds as $userrole_id) {
            $placeholders[] = '?';
            $values[] = $userrole_id;
        }// endforeach;
        unset($userrole_id);
        $sql .= '`userrole_id` IN (' . implode(', ', $placeholders) . ')';

        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);

        $result = $Sth->execute($values);
        unset($Sth);

        // delete in users_roles table.
        $UsersRolesDb = new UsersRolesDb($this->Container);
        $UsersRolesDb->delete(null, $roleIds);
        unset($UsersRolesDb);

        // delete in user_permissions table.
        $sql = 'DELETE FROM `' . $this->Db->tableName('user_permissions') . '` WHERE';
        $sql .= '`userrole_id` IN (' . implode(', ', $placeholders) . ')';
        $Sth = $this->Db->PDO()->prepare($sql);
        unset($sql);
        $Sth->execute($values);
        unset($Sth);

        unset($placeholders, $values);

        return $result;
    }// delete


    /**
     * Get a single role data.
     * 
     * @param array $where The associative array where key is column name and value is its value.
     * @return mixed Return object if result was found, return `empty`, `null`, `false` if it was not found.
     */
    public function get(array $where = [])
    {
        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE 1';
        $values = [];
        $placeholders = [];

        $genWhereValues = $this->Db->buildPlaceholdersAndValues($where);
        if (isset($genWhereValues['values'])) {
            $values = array_merge($values, $genWhereValues['values']);
        }
        if (isset($genWhereValues['placeholders'])) {
            $placeholders = array_merge($placeholders, $genWhereValues['placeholders']);
        }
        unset($genWhereValues);

        $sql .= ' AND ' . implode(' AND ', $placeholders);
        unset($placeholders);
        $sql .= ' LIMIT 0, 1';

        $Sth = $this->Db->PDO()->prepare($sql);
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $sql, $value, $values);

        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($Sth);

        return $result;
    }// get


    /**
     * Get new priority that is lowest between exists or restricted priorities.
     * 
     * @link https://stackoverflow.com/a/3559597/128761 Original source code about check array contains only integer.
     * @link https://stackoverflow.com/a/4163225/128761 Original source code about find missing numbers.
     * @return int
     */
    protected function getNewPriority(): int
    {
        $sql = 'SELECT `userrole_id`, `userrole_priority` FROM `' . $this->tableName . '`
            WHERE `userrole_priority` NOT IN (';

        $whereNotIn = [];
        $whereNotInValue = [];
        foreach ($this->restrictedPriority as $userrole_id) {
            $whereNotIn[] = '?';
            $whereNotInValue[] = $userrole_id;
        }
        unset($userrole_id);

        $sql .= implode(',', $whereNotIn) . ') 
            ORDER BY `userrole_priority` DESC
            LIMIT 1 OFFSET 0';

        $Sth = $this->Db->PDO()->prepare($sql);
        $Sth->execute($whereNotInValue);
        unset($whereNotIn, $whereNotInValue);

        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($sql, $Sth);

        if (isset($result->userrole_priority) && !empty($result->userrole_priority)) {
            // if found at least one priority that is NOT in restricted priority.
            $newPriority = (int) (intval($result->userrole_priority) + 1);
        } else {
            // if not found any priorities that are not in restricted priority.
            $newPriority = 3;

            if (
                is_array($this->restrictedPriority) && 
                !empty($this->restrictedPriority) &&
                array_filter($this->restrictedPriority, 'is_int') === $this->restrictedPriority
            ) {
                // if restricted priority was set and contains only number (integer).
                // in case that restricted priority was not set or invalid then use the number 3 above.

                // assume that restricted priority is 1, 4, 5
                // get max priority.
                $maximum = (int) max($this->restrictedPriority);
                // get min priority.
                $minimum = (int) min($this->restrictedPriority);
                // construct new array from 1 to max (5). it will be 1, 2, 3, 4, 5.
                $array2 = range(1, $maximum);
                // get missing numbers as array.
                $missing = array_diff($this->restrictedPriority, $array2);
                unset($array2);

                if (is_array($missing) && !empty($missing)) {
                    $minMissing = (int) min($missing);
                    $newPriority = (int) ($minMissing + 1);
                    unset($minMissing);
                }

                unset($maximum, $minimum, $missing);
            }
        }
        unset($result);

        return $newPriority;
    }// getNewPriority


    /**
     * List roles.
     * 
     * @param array $options The associative array options. Available options keys:<br>
     *                          `search` (string) the search term,<br>
     *                          `roleIdsIn` (array) the role IDs to use in the sql command `WHERE IN (...)`<br>
     *                          `where` (array) the where conditions where key is column name and value is its value,<br>
     *                          `sortOrders` (array) the sort order where `sort` key is column name, `order` key is mysql order (ASC, DESC),<br>
     *                          `unlimited` (bool) set to `true` to show unlimited items, unset or set to `false` to show limited items,<br>
     *                          `limit` (int) limit items per page. maximum is 100,<br>
     *                          `offset` (int) offset or start at record. 0 is first record,<br>
     * @return array Return associative array with `total` and `items` in keys.
     */
    public function listItems(array $options = []): array
    {
        // prepare options and check if incorrect.
        if (!isset($options['offset']) || !is_numeric($options['offset'])) {
            $options['offset'] = 0;
        }
        if (!isset($options['unlimited']) || (isset($options['unlimited']) && $options['unlimited'] !== true)) {
            if (!isset($options['limit']) || !is_numeric($options['limit'])) {
                $ConfigDb = new ConfigDb($this->Container);
                $options['limit'] = $ConfigDb->get('rdbadmin_AdminItemsPerPage', 20);
                unset($ConfigDb);
            } elseif (isset($options['limit']) && $options['limit'] > 100) {
                $options['limit'] = 100;
            }
        }

        $bindValues = [];
        $output = [];
        $sql = 'SELECT %*% FROM `' . $this->tableName . '` AS `user_roles`
            WHERE 1';
        if (array_key_exists('search', $options) && is_scalar($options['search']) && !empty($options['search'])) {
            $sql .= ' AND (';
            $sql .= '`user_roles`.`userrole_name` LIKE :search';
            $sql .= ' OR `user_roles`.`userrole_description` LIKE :search';
            $sql .= ')';
            $bindValues[':search'] = '%' . $options['search'] . '%';
        }

        if (array_key_exists('roleIdsIn', $options) && is_array($options['roleIdsIn'])) {
            // role IDs IN(..).
            $sql .= ' AND';

            $roleIdsInPlaceholder = [];
            $i = 0;
            foreach ($options['roleIdsIn'] as $userrole_id) {
                $roleIdsInPlaceholder[] = ':roleIdsIn' . $i;
                $bindValues[':roleIdsIn' . $i] = $userrole_id;
                $i++;
            }// endforeach;
            unset($i, $userrole_id);

            $sql .= ' `userrole_id` IN (' . implode(', ', $roleIdsInPlaceholder) . ')';
            unset($roleIdsInPlaceholder);
        }

        if (isset($options['where'])) {
            // where conditions.
            $placeholders = [];
            $genWhereValues = $this->Db->buildPlaceholdersAndValues($options['where']);
            if (isset($genWhereValues['values'])) {
                $bindValues = array_merge($bindValues, $genWhereValues['values']);
            }
            if (isset($genWhereValues['placeholders'])) {
                $placeholders = array_merge($placeholders, $genWhereValues['placeholders']);
            }
            unset($genWhereValues);
            $sql .= ' AND ' . implode(' AND ', $placeholders);
            unset($placeholders);
        }

        // prepare and get 'total' records while not set limit and offset.
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', 'COUNT(*)', $sql));
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $Sth->execute();
        $output['total'] = $Sth->fetchColumn();
        $Sth->closeCursor();
        unset($Sth);

        // sort and order.
        if (array_key_exists('sortOrders', $options) && is_array($options['sortOrders']) && !empty($options['sortOrders'])) {
            $orderby = [];
            foreach ($options['sortOrders'] as $sort) {
                if (
                    is_array($sort) && 
                    array_key_exists('sort', $sort) && 
                    in_array($sort['sort'], $this->allowedSort) && 
                    array_key_exists('order', $sort) && 
                    in_array(strtoupper($sort['order']), $this->allowedOrders)
                ) {
                    $orderby[] = '`user_roles`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
                }
            }// endforeach;
            unset($sort);

            if (!empty($orderby)) {
                $sql .= ' ORDER BY ';
                $sql .= implode(', ', $orderby);
            }
            unset($orderby);
        }

        // limited or unlimited.
        if (!isset($options['unlimited']) || (isset($options['unlimited']) && $options['unlimited'] !== true)) {
            // if limited.
            $sql .= ' LIMIT ' . $options['limit'] . ' OFFSET ' . $options['offset'];
        }

        // prepare and get 'items'.
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', '*', $sql));
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($bindValues, $sql, $Sth);

        $output['items'] = $result;

        unset($result);
        return $output;
    }// listItems


    /**
     * Update user role data.
     * 
     * @param array $data The associative array where its key is column name and value is its value to update.
     * @param array $where The associative array where its key is column name and value is its value.
     * @return bool Return `true` on success update, `false` for otherwise.
     */
    public function update(array $data, array $where): bool
    {
        $data['userrole_lastupdate'] = date('Y-m-d H:i:s');
        $data['userrole_lastupdate_gmt'] = gmdate('Y-m-d H:i:s');

        return $this->Db->update($this->tableName, $data, $where);
    }// update


}
