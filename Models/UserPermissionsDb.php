<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Models;


/**
 * User permissions DB.
 * 
 * @since 0.1
 */
class UserPermissionsDb extends \Rdb\System\Core\Models\BaseModel
{


    /**
     * @var string Cache key for permission module data of certain module and page. This cache key will be set and delete only in this model.
     */
    protected $cachKeyPermissionModuleData = 'permissionsModuleData.%module_system_name%.%permission_page%';


    /**
     * @var string Cache key for permissions per user role ID. This cache key will be set and delete only in this model.
     */
    protected $cacheKeyPermissionUserRoleId = 'permissionUser.userRoleID.user_%user_id%';


    /**
     * @var string Cache key for permissions per user but can contain many role IDs. This cache key will be set and delete only in this model.
     */
    protected $cacheKeyPermissionUserRoleIdsData = 'permissionUser.userRoleIDsData.user_%user_id%';


    /**
     * @var string Cache folder path.
     */
    protected $cachePath = STORAGE_PATH . '/cache/Modules/RdbAdmin/Models/UserPermissionsDb';


    /**
     * Add permission data.
     * 
     * @param array $data The associative array where key is column name and value is its value.
     * @return mixed Return inserted ID if successfully inserted, return `0` (zero), or `false` if failed to insert.
     */
    public function add(array $data)
    {
        // delete cache.
        $deleteCacheOptions = [];
        if (isset($data['module_system_name'])) {
            $deleteCacheOptions['module_system_name'] = $data['module_system_name'];
        }
        if (isset($data['permission_page'])) {
            $deleteCacheOptions['permission_page'] = $data['permission_page'];
        }
        if (isset($data['user_id'])) {
            $deleteCacheOptions['user_id'] = $data['user_id'];
        }
        $this->deleteCheckPermissionCache($deleteCacheOptions);
        unset($deleteCacheOptions);
        // end delete cache.

        $insertResult = $this->Db->insert($this->Db->tableName('user_permissions'), $data);
        if ($insertResult === true) {
            return $this->Db->PDO()->lastInsertId();
        }
        return false;
    }// add


    /**
     * Check permission for role, user.
     * 
     * @param string $module The module (module system name or folder name) to check.
     * @param string $page The page name to check.
     * @param string|array $action The action(s) on that page. Use string if check for single action, use array if check for multiple actions.<br>
     *                                      If checking for multiple actions, any single action matched with certain module, page will be return `true`.
     * @param array $identity The associative array of identity. Accepted keys:<br>
     *                                      'userrole_id' (int|array) check by role.<br>
     *                                      'user_id' (int) check by user.<br>
     *                                      Leave blank for auto detect role by default.<br>
     *                                      In order of default auto detect, it will check for role's permission first but if not found then it will be check for user's permission.
     * @return bool Return `true` if permission granted, `false` for permission denied.
     */
    public function checkPermission(string $module, string $page, $action, array $identity = []): bool
    {
        // make cache object ready.
        $Cache = $this->getCacheObject();
        $cacheExpire = (24 * 60 * 60);// 24 hours

        // verify and get identities. ---------------------------------------------------------------------------------
        $cacheKeyRolesData = null;
        $identity = $this->checkPermissionVerifyIdentity($identity, $Cache, $cacheExpire, $cacheKeyRolesData);
        // end verify and get identities. -----------------------------------------------------------------------------

        // verify that this user role is highest priority. -------------------------------------------------------------
        if (true === $this->checkPermissionVerifyRoleHighestPriority($identity, $Cache, $cacheExpire, $cacheKeyRolesData)) {
            return true;
        }
        // verify that this user role is highest priority. -------------------------------------------------------------
        unset($cacheKeyRolesData);

        // get permission for selected module and put into cache.
        $cacheKeyPermissionsModuleData = str_replace(
            ['%module_system_name%', '%permission_page%'], 
            [$module, $page], 
            $this->cachKeyPermissionModuleData
        );
        if (!$Cache->has($cacheKeyPermissionsModuleData)) {
            $options = [];
            $options['where'] = [
                'module_system_name' => $module,
                'permission_page' => $page,
            ];
            $options['unlimited'] = true;
            $permissionsModule = $this->listItems($options);
            unset($options);
            if (isset($permissionsModule['items']) && is_array($permissionsModule['items'])) {
                $Cache->set($cacheKeyPermissionsModuleData, $permissionsModule['items'], $cacheExpire);
                $permissionsModule = $permissionsModule['items'];
            } else {
                $permissionsModule = [];
            }
        } else {
            $permissionsModule = $Cache->get($cacheKeyPermissionsModuleData);
        }
        unset($cacheKeyPermissionsModuleData);
        unset($Cache, $cacheExpire);

        // verify permissions. ----------------------------------------------------------------------------------------
        if (isset($permissionsModule) && is_array($permissionsModule)) {
            foreach ($permissionsModule as $row) {
                if (
                    $row->module_system_name === $module &&
                    $row->permission_page === $page
                ) {
                    // if module and page is matched.
                    if (is_string($action) && $row->permission_action === $action) {
                        // if action is string and is matched.
                        $actionMatched = true;
                    } elseif (is_array($action) && in_array($row->permission_action, $action)) {
                        // if action is array and found matched.
                        $actionMatched = true;
                    }

                    if (isset($actionMatched) && $actionMatched === true) {
                        // if checked for action and matched.
                        // now, check for user role and/or user ID.
                        if (isset($identity['userrole_id']) && is_array($identity['userrole_id']) && in_array($row->userrole_id, $identity['userrole_id'])) {
                            // if matched with userrole_id.
                            unset($actionMatched, $permissionsModule, $row);
                            return true;
                        }
                        if (isset($identity['user_id']) && $row->user_id == $identity['user_id']) {
                            // if matched with user_id.
                            unset($actionMatched, $permissionsModule, $row);
                            return true;
                        }
                    }
                }

                // clear `$actionMatched` variable here to let new iteration work properly.
                unset($actionMatched);
            }// endforeach;
            unset($row);
        }
        unset($permissionsModule);
        // end verify permissions. -----------------------------------------------------------------------------------

        return false;
    }// checkPermission


    /**
     * Verify and get identities.
     * 
     * This method's visibility must be `protected` to let extended class use it.
     * 
     * @since 1.2.10
     * @param array $identity The identity.
     * @see \Rdb\Modules\KPApartment\Models\UserPermissionsDb::checkPermissionPerBuilding() For reference.
     * 
     * @param \Psr\SimpleCache\CacheInterface $Cache The cache object.
     * @param int $cacheExpire The cache expiration TTL.
     * @param string|null $cacheKeyRolesData Cache key of roles data. This parameter will be altered for later usage.
     * @return array Return verified and get identities.
     */
    protected function checkPermissionVerifyIdentity(
        array $identity, 
        \Psr\SimpleCache\CacheInterface $Cache,
        int $cacheExpire,
        ?string &$cacheKeyRolesData
    ): array
    {
        if (isset($identity['userrole_id'])) {
            // if userrole_id in identity was set
            if (!is_int($identity['userrole_id']) && !is_array($identity['userrole_id'])) {
                // if userrole_id is not integer and not array.
                // remove it.
                unset($identity['userrole_id']);
            } elseif (is_int($identity['userrole_id'])) {
                // if userrole_id is integer.
                // make it array.
                $identity['userrole_id'] = [$identity['userrole_id']];
            }
        }

        if (isset($identity['user_id']) && !is_int($identity['user_id'])) {
            // if user_id in identity was set but not integer.
            // remove it.
            unset($identity['user_id']);
        }

        if (
            empty($identity) || 
            (!isset($identity['userrole_id']) && !isset($identity['user_id']))
        ) {
            // if no identity specified.
            if ($this->Container->has('UsersSessionsTrait')) {
                // if it was check logged in at admin base controller.
                $identity['user_id'] = (int) ($this->Container['UsersSessionsTrait']->userSessionCookieData['user_id'] ?? 0);
            } else {
                // if it was not check logged in, this maybe because plugin register hook 
                // ...maybe from admin base constructor -> front base constructor -> register hooks
                // try to get user id from cookie.
                $Cookie = new \Rdb\Modules\RdbAdmin\Libraries\Cookie($this->Container);
                $Cookie->setEncryption('rdbaLoggedinKey');
                $cookieData = $Cookie->get('rdbadmin_cookie_users');// contain `user_id`, `user_display_name`, `sessionKey`.
                unset($Cookie);

                if (isset($cookieData['user_id'])) {
                    $identity['user_id'] = (int) $cookieData['user_id'];
                } else {
                    $identity['user_id'] = 0;
                }
            }
        }

        if (!isset($identity['userrole_id']) && isset($identity['user_id'])) {
            // if userrole_id was not set but user_id was set.
            // get user roles data for this user.
            $cacheKey = str_replace(
                ['%user_id%'],
                [$identity['user_id']],
                $this->cacheKeyPermissionUserRoleId
            );
            $cacheKeyRolesData = str_replace(
                ['%user_id%'],
                [$identity['user_id']],
                $this->cacheKeyPermissionUserRoleIdsData
            );

            if ($Cache->has($cacheKey)) {
                $identity['userrole_id'] = $Cache->get($cacheKey);
            } else {
                $UsersRolesDb = new UsersRolesDb($this->Container);
                $options = [];
                $options['where'] = [
                    'user_id' => $identity['user_id'],
                ];
                $options['unlimited'] = true;
                $listRoles = $UsersRolesDb->listItems($options);
                if (isset($listRoles['items']) && is_array($listRoles['items'])) {
                    $identity['userrole_id'] = [];
                    foreach ($listRoles['items'] as $row) {
                        $identity['userrole_id'][] = $row->userrole_id;
                    }// endforeach;
                    unset($row);
                    $Cache->set($cacheKey, $identity['userrole_id'], $cacheExpire);
                    $Cache->set($cacheKeyRolesData, $listRoles['items'], $cacheExpire);
                }
                unset($listRoles, $options, $UsersRolesDb);
            }

            unset($cacheKey);
        }

        if (isset($identity) && is_array($identity)) {
            return $identity;
        } else {
            return [];
        }
    }// checkPermissionVerifyIdentity


    /**
     * Verify that this user's role is highest priority.
     * 
     * This method's visibility must be `protected` to let extended class use it.
     * 
     * @since 1.2.10
     * @param array $identity The identity.
     * @see \Rdb\Modules\KPApartment\Models\UserPermissionsDb::checkPermissionPerBuilding() For reference.
     * 
     * @param \Psr\SimpleCache\CacheInterface $Cache The cache object.
     * @param int $cacheExpire The cache expiration TTL.
     * @param string|null $cacheKeyRolesData Cache key of roles data. This parameter will be altered for later usage.
     * @return bool Return `true` on success, `false` for otherwise. If return `false` it must be check with another process but if return `true` then check permission process should be ended.
     */
    protected function checkPermissionVerifyRoleHighestPriority(
        array $identity, 
        \Psr\SimpleCache\CacheInterface $Cache,
        int $cacheExpire,
        ?string &$cacheKeyRolesData
    ): bool
    {
        if (isset($identity['userrole_id']) && is_array($identity['userrole_id'])) {
            // if userrole_id was set.
            if (!isset($cacheKeyRolesData)) {
                // if there is no cache key of user's roles data before.
                // The cache key below will be set and use only in this model. This cache has no deletion (no need). It can be cleared from admin > tools menu.
                $cacheKeyRolesData = 'permissionUserRoles.userRoleIDsData.userRoleIDs_' . json_encode($identity['userrole_id']);
            }

            if (!$Cache->has($cacheKeyRolesData)) {
                $UsersRolesDb = new UsersRolesDb($this->Container);
                $options = [];
                $options['roleIdsIn'] = $identity['userrole_id'];
                $options['unlimited'] = true;
                $listRoles = $UsersRolesDb->listItems($options);
                unset($options, $UsersRolesDb);
                if (isset($listRoles['items']) && is_array($listRoles['items'])) {
                    $Cache->set($cacheKeyRolesData, $listRoles['items'], $cacheExpire);
                }
                unset($listRoles);
            }

            if ($Cache->has($cacheKeyRolesData)) {
                $listRoles = $Cache->get($cacheKeyRolesData);
                if (is_array($listRoles)) {
                    foreach ($listRoles as $row) {
                        if (isset($row->userrole_priority) && $row->userrole_priority == '1') {
                            // if this user role is highest priority.
                            unset($Cache, $listRoles, $row);
                            return true;
                        }
                    }// endforeach;
                    unset($row);
                }
                unset($listRoles);
            }
        }

        return false;
    }// checkPermissionVerifyRoleHighestPriority


    /**
     * Delete permission.
     * 
     * Also delete permission cache.
     * 
     * @param array $where The condition to delete.
     * @return bool Return `true` on success, `false` for otherwise.
     */
    public function delete(array $where): bool
    {
        if (empty($where)) {
            return false;
        }

        // delete cache.
        $deleteCacheOptions = [];
        if (isset($where['module_system_name'])) {
            $deleteCacheOptions['module_system_name'] = $where['module_system_name'];
        }
        if (isset($where['permission_page'])) {
            $deleteCacheOptions['permission_page'] = $where['permission_page'];
        }
        if (isset($where['user_id'])) {
            $deleteCacheOptions['user_id'] = $where['user_id'];
        }
        $this->deleteCheckPermissionCache($deleteCacheOptions);
        unset($deleteCacheOptions);
        // end delete cache.

        return $this->Db->delete($this->Db->tableName('user_permissions'), $where);
    }// delete


    /**
     * Delete check permission cache.
     * 
     * @param array $options The associative array. Accepted keys:<br>
     *                                      'user_id' (int).<br>
     *                                      Following keys must use together.<br>
     *                                      'module_system_name' (string).<br>
     *                                      'permission_page' (string).
     */
    public function deleteCheckPermissionCache(array $options)
    {
        $Cache = $this->getCacheObject();

        if (isset($options['user_id']) && is_numeric($options['user_id'])) {
            // if user_id in was set
            // delete user roles data cache for this user.
            $options['user_id'] = (int) $options['user_id'];
            $Cache->delete(str_replace(
                ['%user_id%'],
                [$options['user_id']],
                $this->cacheKeyPermissionUserRoleId
            ));
            $Cache->delete(str_replace(
                ['%user_id%'],
                [$options['user_id']],
                $this->cacheKeyPermissionUserRoleIdsData
            ));
        }

        if (
            isset($options['module_system_name']) && 
            is_string($options['module_system_name']) &&
            !empty($options['module_system_name']) &&
            isset($options['permission_page']) && 
            is_string($options['permission_page']) &&
            !empty($options['permission_page'])
        ) {
            // if module_system_name was set.
            $cacheKeyPermissionsModuleData = str_replace(
                ['%module_system_name%', '%permission_page%'], 
                [$options['module_system_name'], $options['permission_page']], 
                $this->cachKeyPermissionModuleData
            );
            $Cache->delete($cacheKeyPermissionsModuleData);
            unset($cacheKeyPermissionsModuleData);
        }

        unset($Cache);
    }// deleteCheckPermissionCache


    /**
     * Get a permission data.
     * 
     * @param array $where The associative array where key is column name and value is its value.
     * @return mixed Return object if result was found, return `empty`, `null`, `false` if it was not found.
     */
    public function get(array $where = [])
    {
        $sql = 'SELECT * FROM `' . $this->Db->tableName('user_permissions') . '` WHERE 1';
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
     * Get cache object.
     * 
     * This method's visibility must be `protected` to let extended class use it.
     * 
     * @since 1.2.10
     * @return \Psr\SimpleCache\CacheInterface
     */
    protected function getCacheObject(): \Psr\SimpleCache\CacheInterface
    {
        return (new \Rdb\Modules\RdbAdmin\Libraries\Cache(
            $this->Container,
            [
                'cachePath' => $this->cachePath,
            ]
        ))->getCacheObject();
    }// getCacheObject


    /**
     * List permissions saved in DB.
     * 
     * @param array $options The associative array options. Available options keys:<br>
     *                          `search` (string) the search term,<br>
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
        $sql = 'SELECT %*%, `user_permissions`.`userrole_id` AS `userrole_id`, `user_permissions`.`user_id` AS `user_id`
            FROM `' . $this->Db->tableName('user_permissions') . '` AS `user_permissions`
            WHERE 1';
        if (array_key_exists('search', $options) && is_scalar($options['search']) && !empty($options['search'])) {
            $sql .= ' AND (';
            $sql .= '`user_permissions`.`module_system_name` LIKE :search';
            $sql .= ' OR `user_permissions`.`permission_page` LIKE :search';
            $sql .= ' OR `user_permissions`.`permission_action` LIKE :search';
            $sql .= ')';
            $bindValues[':search'] = '%' . $options['search'] . '%';
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
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', 'COUNT(`user_permissions`.`permission_id`) AS `total`', $sql));
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
                    $orderby[] = '`user_permissions`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
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


}
