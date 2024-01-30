<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Models;


/**
 * User fields DB model.
 * 
 * @since 0.1
 * @property-read array $rdbaUserFields Registered fields data that come with this module.
 */
class UserFieldsDb extends \Rdb\System\Core\Models\BaseModel
{


    use Traits\MetaFieldsTrait;


    /**
     * Some of these fields must be remove on update user in `/Admin/Users/EditController->preventUpdateFields` property.
     * 
     * @var array Register the fields data that come with this module.
     */
    private $rdbaUserFields = [
        'rdbadmin_uf_adduser_waitactivation_since' => 'Add user and wait for activation since date/time.',
        'rdbadmin_uf_admindashboardwidgets_order' => 'Admin dashboard widgets ordering.',
        'rdbadmin_uf_changeemail_value' => 'New email will be here and wait for confirmation link clicked.',
        'rdbadmin_uf_changeemail_key' => 'Change email confirmation key for send to user email and verify via the link.',
        'rdbadmin_uf_changeemail_time' => 'Change email expire date/time for the specific key.',
        'rdbadmin_uf_changeemail_history' => 'List of email that has been changed.',
        'rdbadmin_uf_login2stepverification' => 'Login second step verification method.',
        'rdbadmin_uf_login2stepverification_key' => 'Login second step verification key.',
        'rdbadmin_uf_login2stepverification_time' => 'Login second step verification expire date/time.',
        'rdbadmin_uf_login2stepverification_tmpdata' => 'Temporary data while waiting 2 step auth.',
        'rdbadmin_uf_registerconfirm_key' => 'User register key for self confirm registration that send to user email and verify via the link.',
        'rdbadmin_uf_resetpassword_key' => 'Reset password key for send to user email and verify via the link.',
        'rdbadmin_uf_resetpassword_time' => 'Reset password expire date/time for the specific key.',
        'rdbadmin_uf_securitysimultaneouslogin' => 'How to handle with simultaneous logins?',
        'rdbadmin_uf_simultaneouslogin_reset_key' => 'Login key to use after there is simultaneous logins and get logged out.',
        'rdbadmin_uf_simultaneouslogin_reset_time' => 'Login key expire date/time.',
        'rdbadmin_uf_avatar' => 'User\'s profile picture (avatar).',
        'rdbadmin_uf_avatar_type' => 'Avatar type (upload or gravatar).',
        'rdbadmin_uf_website' => 'User\'s website.',
    ];


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->storagePath = STORAGE_PATH . '/cache/Modules/RdbAdmin/Models/user_fields';
        $this->tableName = $this->Db->tableName('user_fields');
        $this->objectIdName = 'user_id';
        $this->beginMetaFieldsTrait($Container);
    }// __construct


    /**
     * Magic get.
     * 
     * @param string $name Property name.
     * @return mixed Return its value depend on property.
     */
    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
    }// __get


    /**
     * Delete user field.
     * 
     * @param int $user_id The user ID.
     * @param string $field_name Field name.
     * @return bool Return `true` on success, `false` for otherwise.
     */
    public function delete(int $user_id, string $field_name): bool
    {
        if (empty($field_name)) {
            return false;
        }

        return $this->deleteFieldsData($user_id, $field_name);
    }// delete


    /**
     * Delete all fields for specific user ID.
     * 
     * @param int $user_id The user ID.
     * @return bool Return `true` on success, `false` for otherwise.
     */
    public function deleteAllUserFields(int $user_id): bool
    {
        return $this->deleteAllFieldsData($user_id);
    }// deleteAllUserFields


    /**
     * Generate key with wait time.
     * 
     * If the key was generated and still within wait time then it will be use the old one, otherwise it will be generate new.<br>
     * This method did not create/update/delete the data in DB.
     * 
     * @param int $user_id User ID.
     * @param string $keyFieldName Name of field that contain key in `field_value`.
     * @param string $timeFieldName Name of field that contain time in `field_value`.
     * @param int $waitMinute Minutes to wait.
     * @param array $options The associative array options:<br>
     *                                      'keyLength' (int) The key length to generate. Default is 8.<br>
     *                                      'keyCharacters (string) The key string to generate. Default is empty string means 0-9, a-z.
     * @return array Return associative array with 'readableKey', 'encryptedKey', 'keyTime', 'regenerate' keys.
     */
    public function generateKeyWithWaitTime(
        int $user_id, 
        string $keyFieldName, 
        string $timeFieldName, 
        int $waitMinute = 10,
        array $options = []
    ): array
    {
        /* @var $Config \Rdb\System\Config */
        if ($this->Container->has('Config')) {
            $Config = $this->Container->get('Config');
        } else {
            $Config = new \Rdb\System\Config();
        }
        $Config->setModule('RdbAdmin');
        $hashKey = $Config->get('rdbaUserFieldsKeys', 'hash');
        $Config->setModule('');// restore to default.
        unset($Config);

        $Encryption = new \Rdb\Modules\RdbAdmin\Libraries\Encryption();

        $output = [];
        $keyRow = $this->get($user_id, $keyFieldName);
        $timeRow = $this->get($user_id, $timeFieldName);
        $regenerate = false;

        if (
            empty($keyRow) || 
            !isset($keyRow->field_value) ||
            (isset($keyRow->field_value) && empty($keyRow->field_value)) ||
            empty($timeRow) ||
            !isset($timeRow->field_value) ||
            (isset($timeRow->field_value) && empty($timeRow->field_value))
        ) {
            $regenerate = true;
        } else {
            $NowDt = new \DateTime();
            $NowDt->add(new \DateInterval('PT2M'));// add 2 minute from now.
            $ResetDt = new \DateTime($timeRow->field_value);// reset date/time should be the time in future.

            if ($NowDt > $ResetDt) {
                // if current time is over reset timeout.
                $regenerate = true;
            } else {
                // if current time is not over reset timeout.
                $output['encryptedKey'] = $keyRow->field_value;
                $output['readableKey'] = $Encryption->decrypt($output['encryptedKey'], $hashKey);
                $output['keyTime'] = $timeRow->field_value;
                $output['regenerate'] = false;
            }

            unset($NowDt, $ResetDt);
        }

        unset($keyRow, $timeRow);

        if (
            $regenerate === true ||
            !isset($output['encryptedKey']) ||
            !isset($output['readableKey']) ||
            !isset($output['keyTime'])
        ) {
            // if it have to regenerate key.
            // default options
            $defaultOptions = [
                'keyLength' => 8,
                'keyCharacters' => '',
            ];
            $options = array_merge($defaultOptions, $options);
            unset($defaultOptions);

            $RdbaString = new \Rdb\Modules\RdbAdmin\Libraries\RdbaString();
            $DateTime = new \DateTime();
            $DateTime->add(new \DateInterval('PT' . $waitMinute . 'M'));
            $output['readableKey'] = $RdbaString->random($options['keyLength'], $options['keyCharacters']);
            $output['encryptedKey'] = $Encryption->encrypt($output['readableKey'], $hashKey);
            $output['keyTime'] = $DateTime->format('Y-m-d H:i:s');
            $output['regenerate'] = true;
            unset($DateTime, $RdbaString);
        }

        unset($Encryption, $hashKey, $regenerate);

        return $output;
    }// generateKeyWithWaitTime


    /**
     * Get user fields data by name.
     * 
     * @param int $user_id The user ID.
     * @param string $field_name Meta field name. If this field is empty then it will get all fields that matched this user ID.
     * @return mixed Return the row(s) of user fields data. If it was not found then return null.<br>
     *                          The return value may be unserialize if it is not scalar and not `null`.
     */
    public function get(int $user_id, string $field_name = '')
    {
        return $this->getFields($user_id, $field_name);
    }// get


    /**
     * List multiple users fields.
     * 
     * @since 1.2.9
     * @param array $userIds The multiple user IDs to search in.
     * @param string $field_name Meta field name. If this field is empty then it will get all fields that matched user IDs.
     * @return array Return associative array where key is each object ID in the `$objectIds` and its result will be the same as we get from `getFields()` method with `$field_name` parameter.
     */
    public function listUsersFields(array $userIds, string $field_name = ''): array
    {
        return $this->listObjectsFields($userIds, $field_name);
    }// listUsersFields


    /**
     * Update user field.
     * 
     * If data is not exists then it will be call add data automatically.
     * 
     * @param int $user_id The user ID.
     * @param string $field_name Field name.
     * @param mixed $field_value Field value.
     * @param string|false|true $field_description Field description. Set to `false` (default) to not change. Set to `true` to get it from registered fields data key and its description from `rdbaUserFields` property.
     * @param mixed $previousValue Previous field value to check that it must be matched, otherwise it will not be update and return `false`. Set this to `false` to skip checking.
     * @return mixed Return field ID if data is not exists then it will be use `add()` method. Return `true` if update success, `false` for otherwise.
     */
    public function update(int $user_id, string $field_name, $field_value, $field_description = false, $previousValue = false)
    {
        if ($field_description === true) {
            if (array_key_exists($field_name, $this->rdbaUserFields)) {
                $field_description = $this->rdbaUserFields[$field_name];
            } else {
                $field_description = null;
            }
        }

        return $this->updateFieldsData($user_id, $field_name, $field_value, $field_description, $previousValue);
    }// update


}
