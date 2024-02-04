<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Models;


/**
 * User logins DB model.
 * 
 * Working on track login sessions including device cookie for prevent brute-force attack.
 *
 * @author mr.v
 */
class UserLoginsDb extends \Rdb\System\Core\Models\BaseModel
{


    /**
     * @var array Allowed sort columns in db.
     */
    protected $allowedSort = ['userlogin_id', 'user_id', 'userlogin_session_key', 'userlogin_ua', 'userlogin_ip', 'userlogin_date', 'userlogin_date_gmt', 'userlogin_dc_sign', 'userlogin_dc_lockout', 'userlogin_dc_lockout_until', 'userlogin_dc_lockout_until_gmt', 'userlogin_result', 'userlogin_result_text', 'userlogin_result_text_data'];


    /**
     * @var array|null The result that have got from called to `dcIsInLockoutList()` method.
     */
    protected $dcLockoutListResult;


    /**
     * @var string Table name that already added prefix.
     */
    protected $tableName;


    /**
     * @var array|null User logins results data.
     */
    protected $userLoginsResult;


    /**
     * Class constructor
     * 
     * @inheritDoc
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->tableName = $this->Db->tableName('user_logins');
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
     * Count the number of failed authentication within time period from both device cookie or untrusted clients.
     *
     * @param int $timePeriod The time period in minutes.
     * @param array $where The associative array where key is field.
     * @return int Return total number of failed authentication counted.
     */
    public function dcCountFailedAttemptInPeriod(int $timePeriod, array $where = []): int
    {
        $sql = 'SELECT COUNT(*) AS `total_failed` FROM `' . $this->tableName . '` AS `tuserlogins`'
                . ' INNER JOIN `' . $this->Db->tableName('users') . '` AS `tusers`'
                . ' ON `tuserlogins`.`user_id` = `tusers`.`user_id`'
                . ' WHERE `userlogin_date` >= NOW() - INTERVAL :time_period MINUTE'
                . ' AND `tusers`.`user_deleted` = 0';
        $values = [];
        $values[':time_period'] = $timePeriod;
        $placeholders = [];

        if (isset($where['user_id'])) {
            $where['tuserlogins.user_id'] = $where['user_id'];
            unset($where['user_id']);
        }
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

        $Sth = $this->Db->PDO()->prepare($sql);
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $sql, $value, $values);

        $Sth->execute();
        $result = $Sth->fetchObject();
        unset($Sth);

        if (is_object($result) && isset($result->total_failed)) {
            return intval($result->total_failed);
        } else {
            return 0;
        }
    }// dcCountFailedAttemptInPeriod


    /**
     * Check if current user is in lockout list.
     *
     * If device cookie signature is specified, then it will check for specific device cookie.<br>
     * If device cookie is `null` then it will check for untrusted clients.<br>
     * After you called to this method, you can access its value via `dcLockoutListResult` property.
     * 
     * @param string $user_login_email The input login identity (username or email depend on how system use it).
     * @param string $userlogin_dc_sign The device cookie signature.
     * @return bool Return `true` if it is in lockout list, `false` if it is not.
     */
    public function dcIsInLockoutList(string $user_login_email = null, string $userlogin_dc_sign = null): bool
    {
        if (
            (
                is_null($user_login_email) ||
                $user_login_email === ''
            ) &&
            (
                is_null($userlogin_dc_sign) ||
                empty($userlogin_dc_sign)
            )
        ) {
            // if both arguments are not set. no need to spend cpu here.
            return false;
        }

        $sql = 'SELECT * FROM `' . $this->tableName . '` AS `tuserlogins`';
        if (!is_null($user_login_email) && !empty($user_login_email)) {
            $sql .= ' INNER JOIN `' . $this->Db->tableName('users') . '` AS `tusers`'
                . ' ON `tuserlogins`.`user_id` = `tusers`.`user_id`';
        }
        $sql .= ' WHERE `userlogin_dc_lockout_until_gmt` >= :userlogin_dc_lockout_until_gmt'
                . ' AND `userlogin_dc_lockout` != 0';
        if (!is_null($user_login_email) && !empty($user_login_email)) {
            $sql .= ' AND `tusers`.`user_deleted` = 0';
        }

        $where = [];
        $where[':userlogin_dc_lockout_until_gmt'] = gmdate('Y-m-d H:i:s');

        if (!is_null($user_login_email) && !empty($user_login_email)) {
            $sql .= ' AND (`tusers`.`user_login` = :user_login_email OR `tusers`.`user_email` = :user_login_email)';
            $where[':user_login_email'] = $user_login_email;
        }

        if (!is_null($userlogin_dc_sign) && !empty($userlogin_dc_sign)) {
            $sql .= ' AND `userlogin_dc_sign` = :userlogin_dc_sign';
            $where[':userlogin_dc_sign'] = $userlogin_dc_sign;
        } else {
            $sql .= ' AND `userlogin_dc_sign` IS NULL';
        }

        $sql .= ' ORDER BY `userlogin_id` DESC';

        $Sth = $this->Db->PDO()->prepare($sql);
        foreach ($where as $column => $value) {
            $Sth->bindValue($column, $value);
        }// endforeach;
        unset($column, $value);
        $Sth->execute();
        $result = $Sth->fetchAll();
        unset($Sth);

        if (is_array($result) && count($result) > 0) {
            $this->dcLockoutListResult = $result;
            unset($result);
            return true;
        }

        $this->dcLockoutListResult = null;
        unset($result);
        return false;
    }// dcIsInLockoutList


    /**
     * Lockout the client from enter the credential for `user_id`.
     * 
     * This will be lockout both device cookie or untrusted clients depend on who is trying to login with wrong credentials.
     * 
     * @param int $timePeriod The time period in minutes.
     * @param array $data The data to update.
     * @param array $where The sql where conditions.
     */
    public function dcLockoutUser(int $timePeriod, array $data, array $where)
    {
        // get the data (userlogin_id) before update.
        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `userlogin_date` >= NOW() - INTERVAL :time_period MINUTE';
        $values = [];
        $values[':time_period'] = $timePeriod;
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
        $sql .= ' ORDER BY `userlogin_id` DESC LIMIT 0, 1';

        $Sth = $this->Db->PDO()->prepare($sql);
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $sql, $value, $values);

        $Sth->execute();
        $result = $Sth->fetchObject();
        if (is_object($result) && !empty($result)) {
            $userlogin_id = $result->userlogin_id;
        }
        unset($result, $Sth);

        if (isset($userlogin_id)) {
            // now update the record.
            $where = [];
            $where['userlogin_id'] = $userlogin_id;
            $this->Db->update($this->tableName, $data, $where);
        }

        unset($userlogin_id);
    }// dcLockoutUser


    /**
     * Delete user logins.
     * 
     * @param array $where The condition to delete.
     * @return bool Return `true` on success, `false` for otherwise.
     */
    public function delete(array $where = []): bool
    {
        if (empty($where)) {
            return false;
        }

        return $this->Db->delete($this->tableName, $where);
    }// delete


    /**
     * Generate session key that must NOT duplicate with any session key for certain user.
     * 
     * This method should be called once check login success.<br>
     * This method did not update, insert the session key to DB.
     * 
     * @param int $user_id The user id to check.
     * @return string Return generated session key.
     */
    public function generateSessionKey(int $user_id): string
    {
        $String = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();
        $exists = true;
        $sessionKey = $user_id . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . time();
        $i = 0;

        while ($exists == true) {
            if ($i > 0) {
                $sessionKey .= $String->random(20);
            }

            $sql = 'SELECT '
                . 'COUNT(*) AS `totalSessionKey` '
                . 'FROM `' . $this->tableName . '` '
                . 'WHERE `user_id` = :user_id AND `userlogin_session_key` = :userlogin_session_key';
            $Sth = $this->Db->PDO()->prepare($sql);
            $Sth->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
            $Sth->bindValue(':userlogin_session_key', hash('sha512', $sessionKey));
            $Sth->execute();
            if ($Sth->fetchColumn() <= 0) {
                $exists = false;
            }
            unset($sql, $Sth);

            $i++;
        }// endwhile;

        unset($exists, $i, $String);
        return hash('sha512', $sessionKey);
    }// generateSessionKey


    /**
     * Check if user is logged in.
     * 
     * If result is passed (`true` or number of sessions), you can access its data via `userLoginsResult` property.
     * 
     * @param int $user_id The user ID.
     * @param array $where The associative array where key is db column to check and value is its value that must be matched in the db value.
     * @return bool|int Return `false` if not logged in or check failed, <br>
     *                                  Return `true` if logged in or check passed and there is only 1 login session, <br>
     *                                  Return number (`int`) of login sessions if there are more than 1 sessions that logged in.
     */
    public function isUserLoggedIn(int $user_id, array $where = [])
    {
        unset($where['user_id'], $where['user_status'], $where['user_deleted'], $where['userlogin_result']);// remove un-necessary where column.

        $sql = 'SELECT `user_logins`.*, 
                `users`.`user_id`, 
                `users`.`user_display_name`, 
                `users`.`user_status`, 
                `users`.`user_statustext`, 
                `users`.`user_deleted`
            FROM `' . $this->tableName . '` AS `user_logins`
            INNER JOIN `' . $this->Db->tableName('users') . '` AS `users`
                ON `user_logins`.`user_id` = `users`.`user_id`
            WHERE `user_logins`.`user_id` = :user_id 
                AND `users`.`user_status` = 1 
                AND `users`.`user_deleted` = 0
                AND `user_logins`.`userlogin_result` = 1
            ORDER BY `userlogin_id` DESC
            LIMIT 10 OFFSET 0';
        $Sth = $this->Db->PDO()->prepare($sql);
        $Sth->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($sql, $Sth);

        if (empty($result) || !is_array($result) || count($result) <= 0) {
            // if not found user id that is enabled and not deleted.
            unset($result);
            return false;
        }

        $totalSessions = count($result);

        if (empty($where)) {
            // if additional condition is not set.
            $this->userLoginsResult = $result;
            unset($result);

            if ($totalSessions == 1) {
                return true;
            } else {
                return (int) $totalSessions;
            }
        } else {
            // if additional condition was set.
            $iSessions = 0;// login session.
            $totalWhere = count($where);
            foreach ($result as $row) {
                $iWhere = 0;
                foreach ($where as $column => $value) {
                    if (isset($row->{$column}) && $row->{$column} === $value) {
                        $iWhere++;
                    }
                }// endforeach;

                if ($iWhere == $totalWhere) {
                    // if all additional conditions were met.
                    // count login session +1.
                    $iSessions++;
                }
                unset($column, $iWhere, $value);
            }// endforeach;
            unset($row, $totalWhere);

            if ($iSessions <= 0) {
                // if total checked login sessions is less than or equal to zero. (not logged in).
                unset($iSessions, $result, $totalSessions);
                return false;
            } else {
                // if total checked login is more than zero. (logged in).
                $this->userLoginsResult = $result;
                unset($iSessions, $result);

                if ($totalSessions == 1) {
                    return true;
                } else {
                    return (int) $totalSessions;
                }
            }
        }
    }// isUserLoggedIn


    /**
     * List user logins.
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
        $sql = 'SELECT %*% FROM `' . $this->tableName . '` AS `user_logins`
            WHERE 1';
        if (array_key_exists('search', $options) && is_scalar($options['search']) && !empty($options['search'])) {
            $sql .= ' AND (';
            $sql .= '`user_logins`.`userlogin_session_key` LIKE :search';
            $sql .= ' OR `user_logins`.`userlogin_ua` LIKE :search';
            $sql .= ' OR `user_logins`.`userlogin_ip` LIKE :search';
            $sql .= ' OR `user_logins`.`userlogin_date` LIKE :search';
            $sql .= ' OR `user_logins`.`userlogin_date_gmt` LIKE :search';
            $sql .= ' OR `user_logins`.`userlogin_dc_sign` LIKE :search';
            $sql .= ' OR `user_logins`.`userlogin_result_text` LIKE :search';
            $sql .= ' OR `user_logins`.`userlogin_result_text_data` LIKE :search';
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
        $Sth = $this->Db->PDO()->prepare(str_replace('%*%', 'COUNT(`user_logins`.`userlogin_id`) AS `total`', $sql));
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
                    $orderby[] = '`user_logins`.`' . $sort['sort'] . '` ' . strtoupper($sort['order']);
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
            $Serializer = new \Rundiz\Serializer\Serializer();

            foreach ($result as $row) {
                if (!empty($row->userlogin_result_text_data)) {
                    $row->userlogin_result_text_data = $Serializer->maybeUnserialize($row->userlogin_result_text_data);
                    // translate all data.
                    if (is_array($row->userlogin_result_text_data)) {
                        foreach ($row->userlogin_result_text_data as $key => $eachData) {
                            $row->userlogin_result_text_data[$key] = __($eachData);
                        }// endforeach;
                        unset($eachData, $key);
                    }
                }

                $row->userlogin_result_text_withdata = '';
                if (!empty($row->userlogin_result_text)) {
                    if (!empty($row->userlogin_result_text_data)) {
                        $row->userlogin_result_text_withdata = sprintf(__($row->userlogin_result_text), ...$row->userlogin_result_text_data);
                    } else {
                        $row->userlogin_result_text_withdata = sprintf(__($row->userlogin_result_text), '');
                    }
                }
            }// endforeach;

            unset($row, $Serializer);
        }// endif;

        $output['items'] = $result;

        unset($result);
        return $output;
    }// listItems


    /**
     * Record login attempts.
     * 
     * @param array $data Associative array where key is matched table field. The required keys are `user_id`, `userlogin_result`.<br>
     *                                  If `userlogin_result` is 0 then `userlogin_result_text` key is required.<br>
     *                                  The `userlogin_result_text` key should NOT be translated but will be able to translated later.<br>
     *                                  So, if `userlogin_result_text` key contains some replacement string such as `%s` then it should be as is raw data without replace anything.<br>
     *                                  To keep replace data, add them into `userlogin_result_text_data` key.<br>
     *                                  It will be replace and translate later on display logins page.<br>
     *                                  Example: `$data['userlogin_result_text'] = 'Your account has been disabled since %s.';`<br>
     *                                      `$data['userlogin_result_text_data'] = serialize([date('Y-m-d H:i:s')]);`
     * @return mixed Return the inserted ID on success, `false` on failure.
     * @throws \InvalidArgumentException
     */
    public function recordLogins(array $data)
    {
        if (
            !is_array($data) ||
            (
                is_array($data) &&
                (
                    !array_key_exists('user_id', $data) ||
                    !array_key_exists('userlogin_result', $data)
                )
            )
        ) {
            throw new \InvalidArgumentException('The $data argument is required and must be array. The required array keys for $data argument are user_id, userlogin_result.');
        }

        if (!is_numeric($data['user_id']) || !is_numeric($data['userlogin_result'])) {
            return false;
        }

        $Input = new \Rdb\Modules\RdbAdmin\Libraries\Input();

        $defaults = [
            'userlogin_ua' => htmlspecialchars($Input->server('HTTP_USER_AGENT', null), ENT_QUOTES),
            'userlogin_ip' => $Input->server('REMOTE_ADDR', null, FILTER_VALIDATE_IP),
            'userlogin_date' => date('Y-m-d H:i:s'),
            'userlogin_date_gmt' => gmdate('Y-m-d H:i:s'),
        ];
        $data = array_merge($defaults, $data);
        unset($defaults, $Input);

        if (empty($data)) {
            return false;
        }

        if (!isset($data['userlogin_session_key'])) {
            // if session key was not set.
            // try to get it from previous cookie.
            if ($this->Container->has('UsersSessionsTrait')) {
                $UsersSessionsTrait = $this->Container->get('UsersSessionsTrait');
                $userData = $UsersSessionsTrait->userSessionCookieData;
                unset($UsersSessionsTrait);
            } else {
                $Cookie = new \Rdb\Modules\RdbAdmin\Libraries\Cookie($this->Container);
                $Cookie->setEncryption('rdbaLoggedinKey');
                $userData = $Cookie->get('rdbadmin_cookie_users');
                unset($Cookie);
            }
            if (!empty($userData) && isset($userData['sessionKey']) && !empty($userData['sessionKey'])) {
                $data['userlogin_session_key'] = $userData['sessionKey'];
            }
            unset($userData);
        }

        // insert to DB.
        $this->Db->insert($this->tableName, $data);
        return $this->Db->PDO()->lastInsertId();
    }// recordLogins


}
