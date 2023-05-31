<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Users\Traits;


/**
 * Users editing trait.
 */
trait UsersEditingTrait
{


    /**
     * Check if target user has higher priority role than a user who is editing them.
     * 
     * @param string|int $user_id The target user ID.
     * @return bool Return `false` if not editing higher priority role, return `true` if yes.
     */
    protected function isEditingHigherRole($user_id): bool
    {
        if (!is_numeric($user_id)) {
            // if target user_id is not number.
            // return true to prevent user from editing.
            return true;
        } else {
            $user_id = (int) $user_id;
        }

        if (
            isset($this->userSessionCookieData) && 
            is_array($this->userSessionCookieData) && 
            array_key_exists('user_id', $this->userSessionCookieData)
        ) {
            // if there is required property and array.
            $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
            return $UsersRolesDb->isEditingHigherRole((int) $this->userSessionCookieData['user_id'], $user_id);
        }// endif; there is required property and array.

        // return true to prevent user from editing.
        return true;
    }// isEditingHigherRole


    /**
     * Check that the specified `$user_id` is me or not.
     * 
     * @param string|int $user_id User ID to check. Cannot leave blank!
     * @return bool Return `true` if it was me, `false` if not.
     */
    protected function isMe($user_id): bool
    {
        if (!is_int($user_id) && !is_string($user_id)) {
            return false;
        }

        $user_id = (int) $user_id;

        if ($this->Container->has('UsersSessionsTrait')) {
            if (isset($this->Container['UsersSessionsTrait']->userSessionCookieData['user_id'])) {
                $myUserId = (int) $this->Container['UsersSessionsTrait']->userSessionCookieData['user_id'];
            }
        }

        if (isset($myUserId) && $myUserId === $user_id) {
            return true;
        }

        unset($myUserId);

        return false;
    }// isMe


}
