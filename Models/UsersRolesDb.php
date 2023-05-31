<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Models;


/**
 * Users roles DB model.
 * 
 * @since 0.1
 */
class UsersRolesDb extends \Rdb\System\Core\Models\BaseModel
{


    /**
     * @var array Allowed sort columns in db.
     */
    protected $allowedSort = ['userrole_id', 'userrole_name', 'userrole_description', 'userrole_priority', 'userrole_create', 'userrole_lastupdate'];


    /**
     * @var array Debug update for internal use in `update()` method. This added to prevent "creation of dynamic property is deprecated" error.
     */
    protected $debugUpdate = [];


    /**
     * Add user to roles.
     * 
     * Also verify that user ID exists, role ID(s) are exists before add.<br>
     * This is not verify that roles will be duplicate or not.
     * 
     * @param int $user_id The user ID.
     * @param array $roleIds Role IDs.
     * @return bool Return `true` on success, `false` on failure.
     */
    public function add(int $user_id, array $roleIds): bool
    {
        // verify user before add.
        $UsersDb = new UsersDb($this->Container);
        $result = $UsersDb->get(['user_id' => $user_id]);
        unset($UsersDb);
        if (empty($result) || is_null($result) || !isset($result->user_id)) {
            // if not found user.
            return false;
        }
        unset($result);

        // verify that role really exists.
        $placeholders = [];
        foreach ($roleIds as $userrole_id) {
            $placeholders[] = '?';
        }
        unset($userrole_id);
        $sql = 'SELECT * FROM `' . $this->Db->tableName('user_roles') . '` WHERE `userrole_id` IN (' . implode(', ', $placeholders) . ')';
        $Sth = $this->Db->PDO()->prepare($sql);
        $Sth->execute($roleIds);
        $result = $Sth->fetchAll();
        if (empty($result) || !is_array($result)) {
            // if role is not exists.
            return false;
        } else {
            $existsRoleIds = [];
            foreach ($result as $row) {
                $existsRoleIds[] = $row->userrole_id;
            }// endforeach;
            unset($row);
            $roleIds = [];// reset
            $roleIds = $existsRoleIds;
            unset($existsRoleIds);
        }
        unset($placeholders, $result, $sql, $Sth);

        // start insert.
        $inserted = 0;
        foreach ($roleIds as $userrole_id) {
            $result = $this->Db->insert(
                $this->Db->tableName('users_roles'), 
                [
                    'user_id' => $user_id, 
                    'userrole_id' => $userrole_id,
                ]
            );

            if ($result === true) {
                $inserted++;
            }
            unset($result);
        }// endforeach;
        unset($userrole_id);

        // delete permission cache that has user roles id.
        $UserPermissionsDb = new UserPermissionsDb($this->Container);
        $UserPermissionsDb->deleteCheckPermissionCache(['user_id' => $user_id]);
        unset($UserPermissionsDb);

        if ($inserted > 0) {
            return true;
        }
        return false;
    }// add


    /**
     * Delete role IDs from users_roles table.
     * 
     * Also delete permission cache (not delete user ID in user_permissions table).
     * 
     * @param int $user_id User ID.
     * @param array $roleIds The role IDs.
     * @return bool Return `true` on success, `false` for otherwise.
     */
    public function delete(int $user_id = null, array $roleIds = []): bool
    {
        $values = [];

        $sql = 'DELETE FROM `' . $this->Db->tableName('users_roles') . '` WHERE 1';

        if (!is_null($user_id) || !empty($roleIds)) {
            $sql .= ' AND ';
        }

        if (!is_null($user_id)) {
            $sql .= '`user_id` = ?';
            $values[] = $user_id;
        }

        if (!is_null($user_id) && !empty($roleIds)) {
            $sql .= ' AND ';
        }

        if (!empty($roleIds)) {
            $placeholders = [];
            $sql .= '`userrole_id` IN (';

            foreach ($roleIds as $userrole_id) {
                $placeholders[] = '?';
                $values[] = $userrole_id;
            }

            $sql .= implode(', ', $placeholders) . ')';
            unset($placeholders);
        }

        $Sth = $this->Db->PDO()->prepare($sql);

        unset($sql);

        // delete permission cache that has user roles id.
        $UserPermissionsDb = new UserPermissionsDb($this->Container);
        $UserPermissionsDb->deleteCheckPermissionCache(['user_id' => $user_id]);
        unset($UserPermissionsDb);

        return $Sth->execute($values);
    }// delete


    /**
     * Check that is current user is editing selected user(s) that has higher role.
     * 
     * @param int $currentUserId The user ID of current user.
     * @param int|array $selectedUserIds The user ID (int) or (array) of user IDs of selected user(s).
     * @return bool Return `false` if current user is not editing user who has higher role. Return `true` for otherwise.
     */
    public function isEditingHigherRole(int $currentUserId, $selectedUserIds): bool
    {
        if ($currentUserId <= 0) {
            // if current user is guest?
            return true;
        }

        $options = [];
        $options['where']['user_id'] = $currentUserId;
        $options['limit'] = 1;
        $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
        $myRoles = $this->listItems($options);
        unset($options);

        if (isset($myRoles['items'])) {
            // if found current user.
            $myRoles = array_shift($myRoles['items']);

            $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
            $options = [];
            if (is_scalar($selectedUserIds)) {
                $options['where']['users.user_id'] = $selectedUserIds;
            } else {
                $options['userIdsIn'] = $selectedUserIds;
            }
            $listUsers = $UsersDb->listItems($options);
            unset($options, $UsersDb);

            if (isset($listUsers['items']) && is_array($listUsers['items'])) {
                // if found selected user(s).
                foreach ($listUsers['items'] as $eachUser) {
                    if ($eachUser->user_id <= 0) {
                        // if guest.
                        return true;
                    }

                    if (isset($eachUser->users_roles) && is_array($eachUser->users_roles)) {
                        foreach ($eachUser->users_roles as $eachRole) {
                            if ($eachRole->userrole_priority < $myRoles->userrole_priority) {
                                // if selected user role has higher role than current user.
                                return true;
                            }
                        }// endforeach;
                        unset($eachRole);
                    }
                }// endforeach;
                unset($eachUser);

                return false;
            }// endif found selected user(s).
        }// endif found current user.

        unset($myRoles);

        // return true by default to prevent editing action.
        return true;
    }// isEditingHigherRole


    /**
     * Check if current user is in selected role or may be higher priority (depend on options).
     * 
     * If option `userrole_priority` and `userrole_id` both are set, the `userrole_priority` will be use for checking.
     * 
     * If option `userrole_id` is set but without `isHigherOrEquals` option to `true`, it will be checking for the same role ID number only.<br>
     * If with `isHigherOrEquals` option to `true`, it will be checking with priority that must equals or higher (lower number).
     * 
     * @since 1.2.7
     * @param int $currentUserId The current user ID.
     * @param array $options Associative array:<br>
     *      `userrole_id` (int) The user role ID. The `userrole_id` or `userrole_priority` is required. If both are set, the `userrole_priority` will be use.<br>
     *      `userrole_priority` (int) The user role priority number. The `userrole_id` or `userrole_priority` is required. If both are set, the `userrole_priority` will be use.<br>
     *      `isHigherOrEquals` (bool) Set to `true` to check for higher or equal priority with destination role option. Default is `false`.<br>
     * @return bool Return `true` if current user is in selected role (or may be higher). Return `false` for otherwise.
     * @throws \InvalidArgumentException Throws the error if not found required options key.
     */
    public function isInRole(int $currentUserId, array $options = []): bool
    {
        if (!isset($options['userrole_id']) && !isset($options['userrole_priority'])) {
            // if no option about `userrole_id` or `userrole_priority` at all.
            // can't verify that.
            throw new \InvalidArgumentException('The `options` argument required `userrole_id` or `userrole_priority` key.');
        }

        $listOptions = [];
        $listOptions['where'] = [
            'users_roles.user_id' => $currentUserId,
        ];
        $listUsRs = $this->listItems($listOptions);
        unset($listOptions);

        // set target role's priority. -----------------------------------
        if (isset($options['userrole_id'])) {
            // if there is `userrole_id` option.
            // get its data for check role's priority.
            $UserRolesDb = new UserRolesDb($this->Container);
            $roleResult = $UserRolesDb->get(['userrole_id' => $options['userrole_id']]);
            unset($UserRolesDb);

            if (is_object($roleResult) && !empty($roleResult)) {
                $targetRolePriority = $roleResult->userrole_priority;
            }
            unset($roleResult);
        }
        // end set target role's priority. ------------------------------

        if (isset($listUsRs['items']) && is_array($listUsRs['items'])) {
            foreach ($listUsRs['items'] as $userRoleRow) {
                if (isset($options['userrole_priority'])) {
                    // if there is option role to check for priority.
                    if (
                        isset($options['isHigherOrEquals']) && 
                        $options['isHigherOrEquals'] === true &&
                        $userRoleRow->userrole_priority <= $options['userrole_priority']
                    ) {
                        // if options is mark to check higher or equals priority.
                        // AND role's priority in DB is higher or equals to expected. 
                        // (use `<=` to compare because lower number is higher priority).
                        return true;
                    } else {
                        // if there is no option to check higher or quals.
                        // just check for equals only.
                        if ($userRoleRow->userrole_priority == $options['userrole_priority']) {
                            return true;
                        }
                    }// endif; there is option to check higher or quals or just equals only.
                } elseif (isset($options['userrole_id'])) {
                    // if there is option role to check for the role id.
                    if (
                        isset($options['isHigherOrEquals']) && 
                        $options['isHigherOrEquals'] === true &&
                        isset($targetRolePriority) &&
                        $userRoleRow->userrole_priority <= $targetRolePriority
                    ) {
                        // if options is mark to check higher or equals priority.
                        // AND role's priority in DB is higher or equals to expected. 
                        // (use `<=` to compare because lower number is higher priority).
                        return true;
                    } else {
                        if ($userRoleRow->userrole_id == $options['userrole_id']) {
                            // if this user's has the selected role ID.
                            // this is DIFFERENT than above, it is not check for same or equals priority which may be different role ID but it is checking for same role ID.
                            return true;
                        }
                    }
                    unset($targetRolePriority);
                }// endif; option role to check.
            }// endforeach;
            unset($userRoleRow);
        }
        unset($listUsRs, $targetRolePriority);

        return false;
    }// isInRole


    /**
     * List user's roles items.
     * 
     * @param array $options The associative array options. Available options keys:<br>
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
        $sql = 'SELECT %*%, `users_roles`.`userrole_id` AS `userrole_id` FROM `' . $this->Db->tableName('users_roles') . '` AS `users_roles`
            LEFT JOIN `' . $this->Db->tableName('user_roles') . '` AS `user_roles` ON `users_roles`.`userrole_id` = `user_roles`.`userrole_id`
            WHERE 1';

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

            $sql .= ' `users_roles`.`userrole_id` IN (' . implode(', ', $roleIdsInPlaceholder) . ')';
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
     * Update roles.
     * 
     * @param int $user_id The user ID.
     * @param array $roleIds 2D array of role IDs.
     * @return boolean Return `true` on success, `false` on failure.
     */
    public function update(int $user_id, array $roleIds)
    {
        // get current roles of selected user.
        $options = [];
        $options['where'] = ['user_id' => $user_id];
        $options['unlimited'] = true;
        $listRoles = $this->listItems($options);
        unset($options);

        if (is_array($listRoles) && array_key_exists('items', $listRoles) && is_array($listRoles['items'])) {
            $deleteList = [];
            $ignoreList = [];
            foreach ($listRoles['items'] as $row) {
                if (!in_array($row->userrole_id, $roleIds)) {
                    // if current role is not in selected roles.
                    // mark delete.
                    $deleteList[] = $row->userrole_id;
                } else {
                    // if current role is already in selected roles.
                    // remove from add list and mark to ignore.
                    $roleIds = array_diff($roleIds, [$row->userrole_id]);// @link https://stackoverflow.com/a/369608/128761 Original source code.
                    $ignoreList[] = $row->userrole_id;
                }
            }// endforeach;
            unset($row);
        }

        // delete unselected roles.
        if (isset($deleteList) && is_array($deleteList) && !empty($deleteList)) {
            $deleteParams = $deleteList;
            $sql = 'DELETE FROM `' . $this->Db->tableName('users_roles') . '` WHERE `user_id` = ? AND `userrole_id` IN (' . rtrim(str_repeat('?, ', count($deleteParams)), ', ') . ')';
            array_unshift($deleteParams, $user_id);
            $Sth = $this->Db->PDO()->prepare($sql);
            unset($sql);
            $deleteResult = $Sth->execute($deleteParams);
            $Sth->closeCursor();
            unset($deleteParams, $Sth);
        }

        // add new selected roles.
        if (!empty($roleIds)) {
            $roleIds = array_values($roleIds);// have to re-index otherwise it maybe cause error with pdo `execute()`.
            $addResult = $this->add($user_id, $roleIds);
        }

        $this->debugUpdate = [
            'deleteList' => ($deleteList ?? []),
            'ignoreList' => ($ignoreList ?? []),
            'addList' => ($roleIds ?? []),
            'deleteResult' => ($deleteResult ?? ''),
            'addResult' => ($addResult ?? ''),
        ];

        // delete permission cache that has user roles id.
        $UserPermissionsDb = new UserPermissionsDb($this->Container);
        $UserPermissionsDb->deleteCheckPermissionCache(['user_id' => $user_id]);
        unset($UserPermissionsDb);

        if (isset($deleteResult) && $deleteResult !== true) {
            // if there is delete but failed.
            return false;
        } elseif (isset($addResult) && $addResult !== true) {
            // if there is add but failed.
            return false;
        }

        // otherwise.. no delete, or no add, or there is delete and success, or there is add and success.
        return true;
    }// update


}
