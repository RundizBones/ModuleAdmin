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
        }

        if (
            isset($this->userSessionCookieData) && 
            is_array($this->userSessionCookieData) && 
            array_key_exists('user_id', $this->userSessionCookieData)
        ) {
            $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
            $options = [];
            $options['where']['user_id'] = $this->userSessionCookieData['user_id'];
            $options['limit'] = 1;
            $options['sortOrders'] = [['sort' => 'userrole_priority', 'order' => 'ASC']];
            $myRoles = $UsersRolesDb->listItems($options);
            unset($options);

            if (isset($myRoles['items'])) {
                $myRoles = array_shift($myRoles['items']);

                $options = [];
                $options['where']['user_id'] = $user_id;
                $targetRoles = $UsersRolesDb->listItems($options);
                unset($options);

                if (isset($targetRoles['items']) && is_array($targetRoles['items']) && isset($targetRoles['total'])) {
                    $i = 0;
                    foreach ($targetRoles['items'] as $row) {
                        if ($row->userrole_priority < $myRoles->userrole_priority) {
                            // if target user's role is higher priority than user who is editing.
                            // return true to prevent user from editing.
                            unset($myRoles, $row, $targetRoles, $UsersRolesDb);
                            return true;
                        }
                        $i++;
                    }// endforeach;
                    unset($row);

                    if (isset($i) && $i == $targetRoles['total']) {
                        unset($myRoles, $targetRoles, $UsersRolesDb);
                        return false;
                    }
                }

                unset($targetRoles);
            }

            unset($myRoles, $UsersRolesDb);
        }

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
