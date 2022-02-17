<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Models;


/**
 * Users DB model.
 * 
 * @since 0.1
 */
class UsersDb extends \Rdb\System\Core\Models\BaseModel
{


    /**
     * @var array Allowed sort columns in db.
     */
    protected $allowedSort = ['user_id', 'user_login', 'user_email', 'user_display_name', 'user_create', 'user_create_gmt', 'user_lastupdate', 'user_lastupdate_gmt', 'user_lastlogin', 'user_lastlogin_gmt', 'user_status', 'user_statustext'];

        /**
     * @var int The password algorithm.
     */
    protected $passwordAlgo;


    /**
     *
     * @var array The password algo's options.
     */
    protected $passwordAlgoOptions = [];


    /**
     * @var int The username or password is incorrect.
     */
    const LOGIN_ERR_USERPASSWORD_INCORRECT = 1;
    /**
     * @var int Your account has been disabled. (%1$s)
     */
    const LOGIN_ERR_ACCOUNT_DISABLED = 2;


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        if ($Container->has('Config')) {
            $Config = $Container->get('Config');
        } else {
            $Config = new \Rdb\System\Config();
        }
        $Config->setModule('RdbAdmin');
        $this->passwordAlgo = $Config->get('algo', 'password', PASSWORD_DEFAULT);
        $this->passwordAlgoOptions = $Config->get('options', 'password', []);
        $Config->setModule('');// restore to default.
    }// __construct


    /**
     * Add user data.
     * 
     * @param array $data The associative array where key is column name and value is its value.
     * @return mixed Return inserted ID if successfully inserted, return `0` (zero), or `false` if failed to insert.
     */
    public function add(array $data)
    {
        $defaultData = [];
        $defaultData['user_create'] = date('Y-m-d H:i:s');
        $defaultData['user_create_gmt'] = gmdate('Y-m-d H:i:s');
        $defaultData['user_lastupdate'] = date('Y-m-d H:i:s');
        $defaultData['user_lastupdate_gmt'] = gmdate('Y-m-d H:i:s');

        $data = array_merge($defaultData, $data);
        unset($defaultData);

        $insertResult = $this->Db->insert($this->Db->tableName('users'), $data);
        if ($insertResult === true) {
            return $this->Db->PDO()->lastInsertId();
        }
        return false;
    }// add


    /**
     * Check login credentials.
     * 
     * Try to check login with username or email and password with these conditions.<br>
     * 1. username or email must be found.<br>
     * 2. user status must be enabled.<br>
     * 3. password must be matched (by hashed).<br>
     * This will be not set any cookie when login correctly.
     * 
     * @param array $data The login data such as `user_login`, `user_password` in the array key.
     * @return array Return associate array.<br>
     *                          On success, return keys: `user_id` (int),<br>
     *                          `user` (object),<br>
     *                          `userStatus` (int),<br>
     *                          `result` (bool).<br>
     *                          On failure, return keys: `user_id` (int - optional) - if found user but something wrong,<br>
     *                          `result` (bool),<br>
     *                          `errorCode` (int),<br>
     *                          `userStatus` (int - optional),<br>
     *                          `userStatusText` (string - optional),<br>
     */
    public function checkLogin(array $data): array
    {
        $sql = 'SELECT '
            . '`user_id`, `user_login`, `user_email`, `user_password`, `user_display_name`, `user_status`, `user_statustext`, `user_deleted` '
            . 'FROM `'.$this->Db->tableName('users').'` '
            . ' WHERE 1';

        $bindValueLogin = '';
        if (isset($data['user_login_or_email'])) {
            $sql .= ' AND (`user_login` = :user_login OR `user_email` = :user_login)';
            $bindValueLogin = $data['user_login_or_email'];
        } elseif (isset($data['user_login'])) {
            $sql .= ' AND `user_login` = :user_login';
            $bindValueLogin = $data['user_login'];
        } elseif (isset($data['user_email'])) {
            $sql .= ' AND `user_email` = :user_login';
            $bindValueLogin = $data['user_email'];
        }
        $sql .= ' AND `user_deleted` = 0';
        $Sth = $this->Db->PDO()->prepare($sql);
        $Sth->bindValue(':user_login', $bindValueLogin);
        $Sth->execute();
        $row = $Sth->fetch();
        unset($bindValueLogin, $sql, $Sth);

        $output = [];

        if ($row !== false && (is_object($row) && !empty($row))) {
            // if found username or email.
            $output['user_id'] = (int) $row->user_id;
            $output['userStatus'] = (int) $row->user_status;
            $UserLoginsDb = new UserLoginsDb($this->Container);

            if ($row->user_status === '1') {
                // if user is enabled.
                if ($this->checkPassword($data['user_password'], $row->user_password) === true) {
                    // if password checked and passed.
                    // set output result and user array key.
                    $output['result'] = true;
                    $output['user'] = [
                        'user_id' => $row->user_id,
                        'user_display_name' => $row->user_display_name,
                    ];

                    // update password if using new hash algo. or new options.
                    if ($this->passwordNeedsRehash($row->user_password)) {
                        $newHashedPassword = $this->hashPassword($data['user_password']);

                        if ($newHashedPassword !== false) {
                            $dataUpdate = [];
                            $dataUpdate['user_password'] = $newHashedPassword;
                            $this->Db->update($this->Db->tableName('users'), $dataUpdate, ['user_id' => $row->user_id]);
                            unset($dataUpdate);
                            $output['user']['passwordRehashed'] = true;
                        } else {
                            if ($this->Container->has('Logger')) {
                                /* @var $Logger \Rdb\System\Libraries\Logger */
                                $Logger = $this->Container->get('Logger');
                                $Logger->write(
                                    'module/rdbadmin/models/usersdb/checklogin', 
                                    5, 
                                    'Unable to hash password. (algo: {algo}, options: {options}).', 
                                    ['algo' => $this->passwordAlgo, 'options' => $this->passwordAlgoOptions]
                                );
                                unset($Logger);
                            }
                        }

                        unset($newHashedPassword);
                    }
                } else {
                    // if password check failed.
                    $output['result'] = false;
                    $output['errorCode'] = static::LOGIN_ERR_USERPASSWORD_INCORRECT;
                }
            } else {
                // if user is disabled.
                $output['result'] = false;
                $output['errorCode'] = static::LOGIN_ERR_ACCOUNT_DISABLED;
                $output['userStatusText'] = $row->user_statustext;
            }

            unset($UserLoginsDb);
        } else {
            // if not found username or email.
            $output['result'] = false;
            $output['errorCode'] = static::LOGIN_ERR_USERPASSWORD_INCORRECT;
        }// endif; $row

        unset($row);
        return $output;
    }// checkLogin


    /**
     * Check password that match hashed.
     * 
     * @link https://www.php.net/manual/en/function.password-verify.php password verify function.
     * @param string $rawPassword The user input password.
     * @param string $hashedPassword The hashed password that store in the database.
     * @return bool Return true if password checked and passed, false for otherwise.
     */
    protected function checkPassword(string $rawPassword, string $hashedPassword): bool
    {
        return password_verify($rawPassword, $hashedPassword);
    }// checkPassword


    /**
     * Delete a user and related tables.
     * 
     * @param int $user_id The user ID.
     * @return bool Return `true` on success, `false` for otherwise.
     */
    public function delete(int $user_id): bool
    {
        // tables to delete: `users`, `users_roles`, `user_fields`, `user_logins`, `user_permissions`

        // delete user_fields table.
        $UserFieldsDb = new UserFieldsDb($this->Container);
        $UserFieldsDb->deleteAllUserFields($user_id);
        unset($UserFieldsDb);

        // delete user_logins table.
        $UserLoginsDb = new UserLoginsDb($this->Container);
        $UserLoginsDb->delete(['user_id' => $user_id]);
        unset($UserLoginsDb);

        // delete user_permissions table.
        $UserPermissionsDb = new UserPermissionsDb($this->Container);
        $UserPermissionsDb->delete(['user_id' => $user_id]);
        unset($UserPermissionsDb);

        // delete users_roles table.
        $UsersRolesDb = new UsersRolesDb($this->Container);
        $UsersRolesDb->delete($user_id);
        unset($UsersRolesDb);

        // delete users table.
        $where = [];
        $where['user_id'] = $user_id;
        return $this->Db->delete($this->Db->tableName('users'), $where);
    }// delete


    /**
     * Get a single user data.
     * 
     * @param array $where The associative array where key is column name and value is its value.
     * @param array $options The associative array with these options.<br>
     *                                      `getUserFields` (bool) to get all data from `user_fields` table.
     * @return mixed Return object if result was found, return `empty`, `null`, `false` if it was not found.
     */
    public function get(array $where = [], array $options = [])
    {
        if (!isset($where['user_deleted'])) {
            $where['user_deleted'] = 0;
        } elseif ($where['user_deleted'] === '*') {
            // if user_deleted condition is **any**.
            unset($where['user_deleted']);
        }

        $sql = 'SELECT * FROM `' . $this->Db->tableName('users') . '` WHERE 1';
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

        if (is_object($result) && !empty($result)) {
            // if result was found.
            // remove some fields for security and privacy.
            unset($result->user_password);
            if (isset($options['getUserFields']) && $options['getUserFields'] === true) {
                $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
                $resultFields = $UserFieldsDb->get($result->user_id);
                if (is_array($resultFields)) {
                    $result->user_fields = $resultFields;
                }
                unset($resultFields, $UserFieldsDb);
            }
        }

        return $result;
    }// get


    /**
     * Hash the password.
     * 
     * @link https://www.php.net/manual/en/function.password-hash.php password hash function.
     * @param string $password The user's password (readable).
     * @param int $algo The password algorithm. Leave `null` to use config.
     * @param array $options The password algorithm options. Leave empty to use config.
     * @return string|bool Return the hashed password or `false` for failure.
     */
    public function hashPassword(string $password, int $algo = null, array $options = [])
    {
        if (is_null($algo)) {
            $algo = $this->passwordAlgo;
        }

        if (empty($options)) {
            $options = $this->passwordAlgoOptions;
        }

        return password_hash($password, $algo, $options);
    }// hashPassword


    /**
     * List users.
     * 
     * @param array $options The associative array options. Available options keys:<br>
     *                          `search` (string) the search term,<br>
     *                          `userIdsIn` (array) the user IDs to use in the sql command `WHERE IN (...)`<br>
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
            if (!isset($options['where']['user_deleted'])) {
                $options['where']['user_deleted'] = 0;
            } elseif ($options['where']['user_deleted'] === '*') {
                unset($options['where']['user_deleted']);
            }
        }

        // SQL_CALC_FOUND_ROWS is slower than [SELECT COUNT() and then SELECT *] in many cases.
        // see https://stackoverflow.com/questions/186588/which-is-fastest-select-sql-calc-found-rows-from-table-or-select-count for more details.
        $bindValues = [];
        $output = [];
        // sql left join is required for user listing that filter role.
        $sql = 'SELECT %*%, `users`.`user_id` AS `user_id` FROM `' . $this->Db->tableName('users') . '` AS `users`
            LEFT JOIN `' . $this->Db->tableName('users_roles') . '` AS `users_roles` ON `users_roles`.`user_id` = `users`.`user_id`
            WHERE 1';
        if (array_key_exists('search', $options) && is_scalar($options['search']) && !empty($options['search'])) {
            $sql .= ' AND (';
            $sql .= '`users`.`user_login` LIKE :search';
            $sql .= ' OR `users`.`user_email` LIKE :search';
            $sql .= ' OR `users`.`user_display_name` LIKE :search';
            $sql .= ' OR `users`.`user_statustext` LIKE :search';
            $sql .= ')';
            $bindValues[':search'] = '%' . $options['search'] . '%';
        }

        if (array_key_exists('userIdsIn', $options) && is_array($options['userIdsIn'])) {
            // user IDs IN(..).
            $sql .= ' AND';

            $userIdsInPlaceholder = [];
            $i = 0;
            foreach ($options['userIdsIn'] as $user_id) {
                $userIdsInPlaceholder[] = ':userIdsIn' . $i;
                $bindValues[':userIdsIn' . $i] = $user_id;
                $i++;
            }// endforeach;
            unset($i, $user_id);

            $sql .= ' `users`.`user_id` IN (' . implode(', ', $userIdsInPlaceholder) . ')';
            unset($userIdsInPlaceholder);
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
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', 'COUNT(DISTINCT `users`.`user_id`) AS `total`', $sql));
        // bind whereValues
        foreach ($bindValues as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $value);
        $Sth->execute();
        $output['total'] = $Sth->fetchColumn();
        $Sth->closeCursor();
        unset($Sth);

        $sql .= ' GROUP BY `users`.`user_id`';

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
                    $orderby[] = '`users`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
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

        // populate result.
        if (is_array($result)) {
            $newResult = [];
            $i = 0;
            $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);

            foreach ($result as $row) {
                if (!empty($row->user_statustext) && function_exists('__')) {
                    $row->user_statustext = __($row->user_statustext);
                }
                unset($row->user_password);

                // set `user_fields` table
                $resultFields = $UserFieldsDb->get($row->user_id);
                if (is_array($resultFields)) {
                    $row->user_fields = $resultFields;
                } else {
                    $row->user_fields = [];
                }
                unset($resultFields);

                // set `users_roles` table
                $sql = 'SELECT * FROM `' . $this->Db->tableName('users_roles') . '` AS `usrs`
                    INNER JOIN `' . $this->Db->tableName('user_roles') . '` AS `urs` ON `usrs`.`userrole_id` = `urs`.`userrole_id`
                    WHERE `usrs`.`user_id` = :user_id
                ';
                $Sth = $this->Db->PDO()->prepare($sql);
                unset($sql);
                $Sth->bindValue(':user_id', $row->user_id, \PDO::PARAM_INT);
                $Sth->execute();
                $row->users_roles = $Sth->fetchAll();
                $Sth->closeCursor();
                unset($Sth);

                $newResult[$i] = $row;
                $i++;
            }// endforeach;
            unset($row);

            $result = $newResult;
            unset($i, $newResult, $UserFieldsDb);
        }// endif;

        $output['items'] = $result;

        unset($result);
        return $output;
    }// listItems


    /**
     * Checks if the given hash matches the given options.
     * 
     * @link https://www.php.net/manual/en/function.password-needs-rehash.php password needs rehash function.
     * @param string $hashedPassword The hashed password that store in the database.
     * @param int $algo The password algorithm. Leave `null` to use config.
     * @param array $options The password algorithm options. Leave empty to use config.
     * @return bool Return `true` if hash should be rehash, `false` for otherwise.
     */
    protected function passwordNeedsRehash(string $hashedPassword, int $algo = null, array $options = []): bool
    {
        if (is_null($algo)) {
            $algo = $this->passwordAlgo;
        }

        if (empty($options)) {
            $options = $this->passwordAlgoOptions;
        }

        return password_needs_rehash($hashedPassword, $algo, $options);
    }// passwordNeedsRehash


    /**
     * Get pre-defined status text for use in `user_statustext` column.
     * 
     * @param string|int $getKey Use empty string to get all, use `integer` value to get its array key.
     */
    public function predefinedStatusText($getKey = '')
    {
        if ($getKey !== '' && !is_int($getKey)) {
            $getKey = '';
        }

        if (!function_exists('noop__')) {
            if ($this->Container->has('Languages')) {
                $Languages = $this->Container->get('Languages');
            } else {
                $Languages = new \Rdb\Modules\RdbAdmin\Libraries\Languages($this->Container);
            }
            $Languages->bindTextDomain(
                'rdbadmin', 
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'translations'
            );
        }

        $statusTexts = [
            0 => noop__('Confirmation required.'),
            1 => noop__('Please wait for administrator confirmation.'),
        ];

        if (is_int($getKey) && array_key_exists($getKey, $statusTexts)) {
            return $statusTexts[$getKey];
        } else {
            return $statusTexts;
        }
    }// predefinedStatusText


    /**
     * Update user data.
     * 
     * @param array $data The associative array where its key is column name and value is its value to update.
     * @param array $where The associative array where its key is column name and value is its value.
     * @return bool Return `true` on success update, `false` for otherwise.
     */
    public function update(array $data, array $where): bool
    {
        $data['user_lastupdate'] = date('Y-m-d H:i:s');
        $data['user_lastupdate_gmt'] = gmdate('Y-m-d H:i:s');

        if (!isset($where['user_deleted'])) {
            $where['user_deleted'] = 0;
        }

        return $this->Db->update($this->Db->tableName('users'), $data, $where);
    }// update


}
