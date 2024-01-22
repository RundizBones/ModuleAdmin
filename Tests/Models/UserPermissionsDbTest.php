<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Models;


class UserPermissionsDbTest extends \Rdb\Tests\BaseTestCase
{


    protected $userIdSA = -1;


    protected $userIdMember = -1;


    /**
     * @var int Normal role level. Typically it is 3 which is 'member'.
     */
    protected $normalRoleId = 3;


    /**
     * @var int Normal role level > permission ID.
     */
    protected $normalRolePermissionId = -1;


    public function setup(): void
    {
        $this->Container = new \Rdb\System\Container();
        $this->Container['Config'] = function ($c) {
            return new \Rdb\System\Config();
        };
        $this->Container['Db'] = function ($c) {
            return new \Rdb\System\Libraries\Db($c);
        };

        $this->Db = $this->Container->get('Db');

        if ($this->Db->currentConnectionKey() === null) {
            $this->markTestIncomplete('Unable to connect to DB.');
        }

        // setup users. -------------------------------------------------------------------------
        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
        $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
        // setup super admin user for testing. -----------
        $username = 'for_test_sa_' . time() . uniqid();
        $this->userIdSA = $UsersDb->add([
            'user_login' => $username,
            'user_email' => $username . '@local.localhost',
            'user_status' => 1,
        ]);
        if (is_numeric($this->userIdSA)) {
            $this->userIdSA = (int) $this->userIdSA;
        }
        $UsersRolesDb->add($this->userIdSA, [1]);// 1 is always super admin role.

        // setup member user for testing. -----------------
        $username = 'for_test_member_' . time() . uniqid();
        $this->userIdMember = $UsersDb->add([
            'user_login' => $username,
            'user_email' => $username . '@local.localhost',
            'user_status' => 1,
        ]);
        if (is_numeric($this->userIdMember)) {
            $this->userIdMember = (int) $this->userIdMember;
        }
        $UsersRolesDb->add($this->userIdMember, [$this->normalRoleId]);
        $UserPermissionsDb->add([
            'module_system_name' => 'RdbAdmin',
            'permission_page' => 'RdbAdminUsers',
            'permission_action' => 'list',
            'user_id' => $this->userIdMember,
        ]);// add permission for user.
        if (
            !$UserPermissionsDb->get(['module_system_name' => 'RdbAdmin',
                'permission_page' => 'RdbAdminTools',
                'permission_action' => 'manageCache',
                'userrole_id' => $this->normalRoleId
            ])
        ) {
            // if not found permission for this for member role then add it.
            $this->normalRolePermissionId = $UserPermissionsDb->add([
                'module_system_name' => 'RdbAdmin',
                'permission_page' => 'RdbAdminTools',
                'permission_action' => 'manageCache',
                'userrole_id' => $this->normalRoleId,
            ]);// add permission for role.
            if (is_numeric($this->normalRolePermissionId)) {
                $this->normalRolePermissionId = (int) $this->normalRolePermissionId;
            }
        }// endif;
        unset($username, $UsersDb, $UserPermissionsDb, $UsersRolesDb);
        // end setup users. ---------------------------------------------------------------------
    }// setup


    public function tearDown(): void
    {
        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
        // delete super admin user.
        $UsersDb->delete($this->userIdSA);
        // delete member user.
        $UsersDb->delete($this->userIdMember);
        // delete permission that was set before (maybe).
        if ($this->normalRolePermissionId > 0) {
            $UserPermissionsDb->delete(['permission_id' => $this->normalRolePermissionId]);
        }
        unset($UsersDb, $UserPermissionsDb);
        $this->Db->disconnectAll();
    }// tearDown


    public function testCheckPermission()
    {
        $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);

        // super admin user must be always `true` for any module or page or actions.
        $this->assertTrue($UserPermissionsDb->checkPermission('RdbAdmin', 'whatEverPage', 'whatEverAction', ['user_id' => $this->userIdSA]));

        // this user id has permission to view users.
        $this->assertTrue($UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminUsers', 'list', ['user_id' => $this->userIdMember]));
        // this user id has no permission to view logins.
        $this->assertFalse($UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminUsers', 'viewLogins', ['user_id' => $this->userIdMember]));

        // member role id has permission for manage cache.
        $this->assertTrue($UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminTools', 'manageCache', ['userrole_id' => $this->normalRoleId]));
        // member role id must not has permission for change site settings.
        $this->assertFalse($UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminSettings', 'changeSettings', ['userrole_id' => $this->normalRoleId]));
        // member role id must not has permission for not existing page and actions.
        $this->assertFalse($UserPermissionsDb->checkPermission('RdbAdmin', 'whatEverPage', 'whatEverAction', ['userrole_id' => $this->normalRoleId]));
    }// testCheckPermission


}
