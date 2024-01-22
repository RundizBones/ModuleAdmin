<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Models;


class UsersRolesDbTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var int The ID of higher role than normal role.
     */
    protected $higherRoleId = -1;
    protected $higherRoleId2 = -1;


    /**
     * @var int The priority of role that is higher than normal. The normal role (member) has priority 9999.
     */
    protected $higherRolePriority = 50;


    /**
     * @var int The ID of user who has higher role than normal.
     */
    protected $higherUserId = -1;


    /**
     * @var int The ID of normal role level. Typically it is 3 which is 'member'.
     */
    protected $normalRoleId = 3;


    /**
     * @var int The priority of role that is normal role (member), default is 9999.
     */
    protected $normalRolePriority = 9999;


    /**
     * @var int The ID of user who has normal role level.
     */
    protected $normalUserId = -1;


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

        // setup roles. ----------------------------------------
        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
        $this->higherRoleId = $UserRolesDb->add([
            'userrole_name' => 'higherThanMember_for_test' . time() . uniqid(),
        ]);
        if (is_numeric($this->higherRoleId)) {
            $this->higherRoleId = (int) $this->higherRoleId;
        }
        // update role priority because it is not possible to set when add.
        $UserRolesDb->update(['userrole_priority' => $this->higherRolePriority], ['userrole_id' => $this->higherRoleId]);

        $this->higherRoleId2 = $UserRolesDb->add([
            'userrole_name' => 'higherThanMember_role2_for_test' . time() . uniqid(),
        ]);
        if (is_numeric($this->higherRoleId2)) {
            $this->higherRoleId2 = (int) $this->higherRoleId2;
        }
        // update role priority because it is not possible to set when add.
        $UserRolesDb->update(['userrole_priority' => $this->higherRolePriority], ['userrole_id' => $this->higherRoleId2]);
        unset($UserRolesDb);
        // end setup roles. -----------------------------------
        // setup users. ---------------------------------------
        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
        // setup higher role user.
        $username = 'for_test_higher_' . time() . uniqid();
        $this->higherUserId = $UsersDb->add([
            'user_login' => $username,
            'user_email' => $username . '@local.localhost',
            'user_status' => 1,
        ]);
        if (is_numeric($this->higherUserId)) {
            $this->higherUserId = (int) $this->higherUserId;
        }
        $UsersRolesDb->add($this->higherUserId, [$this->higherRoleId]);

        // setup normal role user.
        $username = 'for_test_normal_' . time() . uniqid();
        $this->normalUserId = $UsersDb->add([
            'user_login' => $username,
            'user_email' => $username . '@local.localhost',
            'user_status' => 1,
        ]);
        if (is_null($this->normalUserId)) {
            $this->normalUserId = (int) $this->normalUserId;
        }
        $UsersRolesDb->add($this->normalUserId, [$this->normalRoleId]);
        unset($username, $UsersDb, $UsersRolesDb);
        // end setup users. ----------------------------------
    }// setup


    public function tearDown(): void
    {
        // clean up things. ------------------------------------
        $UsersDb = new \Rdb\Modules\RdbAdmin\Models\UsersDb($this->Container);
        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
        $UserRolesDb->delete([$this->higherRoleId]);
        $UserRolesDb->delete([$this->higherRoleId2]);
        $UsersDb->delete($this->higherUserId);
        $UsersDb->delete($this->normalUserId);
        unset($UsersDb, $UserRolesDb);
        // end clean up things. -------------------------------

        $this->Db->disconnectAll();
    }// tearDown


    public function testMakeSureAddRoleMatchPriority()
    {
        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
        $higherRole1Result = $UserRolesDb->get(['userrole_id' => $this->higherRoleId]);
        $this->assertSame($this->higherRolePriority, (int) $higherRole1Result->userrole_priority);
        $higherRole2Result = $UserRolesDb->get(['userrole_id' => $this->higherRoleId2]);
        $this->assertSame($this->higherRolePriority, (int) $higherRole2Result->userrole_priority);
        unset($higherRole1Result, $higherRole2Result, $UserRolesDb);
    }// testMakeSureAddRoleMatchPriority


    public function testIsEditingHigherRole()
    {
        $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
        // yes, normal user is editing user who has higher role level.
        $this->assertTrue($UsersRolesDb->isEditingHigherRole($this->normalUserId, $this->higherUserId));
        // no, both current and target users has the same role level.
        $this->assertFalse($UsersRolesDb->isEditingHigherRole($this->higherUserId, $this->higherUserId));
        $this->assertFalse($UsersRolesDb->isEditingHigherRole($this->normalUserId, $this->normalUserId));
        // no, higher role user is editing normal role user. no means it is allowed to edit.
        $this->assertFalse($UsersRolesDb->isEditingHigherRole($this->higherUserId, $this->normalUserId));
        unset($UsersRolesDb);
    }// testIsEditingHigherRole


    public function testIsInRole()
    {
        $UsersRolesDb = new \Rdb\Modules\RdbAdmin\Models\UsersRolesDb($this->Container);
        // check by priority. --------------------------------------
        // yes, this user has same priority.
        $this->assertTrue($UsersRolesDb->isInRole($this->higherUserId, ['userrole_priority' => $this->higherRolePriority]));
        $this->assertTrue($UsersRolesDb->isInRole($this->normalUserId, ['userrole_priority' => $this->normalRolePriority]));
        // yes, this user has higher or equals priority.
        $this->assertTrue($UsersRolesDb->isInRole($this->higherUserId, ['userrole_priority' => $this->higherRolePriority, 'isHigherOrEquals' => true]));
        $this->assertTrue($UsersRolesDb->isInRole($this->higherUserId, ['userrole_priority' => $this->normalRolePriority, 'isHigherOrEquals' => true]));
        $this->assertTrue($UsersRolesDb->isInRole($this->normalUserId, ['userrole_priority' => $this->normalRolePriority, 'isHigherOrEquals' => true]));
        // no, this user has lower priority.
        $this->assertFalse($UsersRolesDb->isInRole($this->normalUserId, ['userrole_priority' => $this->higherRolePriority]));
        $this->assertFalse($UsersRolesDb->isInRole($this->normalUserId, ['userrole_priority' => $this->higherRolePriority, 'isHigherOrEquals' => true]));

        // check by role id. -----------------------------------------
        // yes, this user has same role id.
        $this->assertTrue($UsersRolesDb->isInRole($this->higherUserId, ['userrole_id' => $this->higherRoleId]));
        // no, this user has not same role id (even same priority). this is because it doesn't check with higher or equals priority.
        $this->assertFalse($UsersRolesDb->isInRole($this->higherUserId, ['userrole_id' => $this->higherRoleId2]));
        // yes, this user has higher or equals priority. this is because check with `isHigherOrEquals` option.
        $this->assertTrue($UsersRolesDb->isInRole($this->higherUserId, ['userrole_id' => $this->higherRoleId2, 'isHigherOrEquals' => true]));
        // no, this user has not the same role id.
        $this->assertFalse($UsersRolesDb->isInRole($this->higherUserId, ['userrole_id' => $this->normalRolePriority]));
        // no, this user has not the same role id. however with `isHigherOrEquals` option to `true`, the target role's priority is till lower.
        $this->assertFalse($UsersRolesDb->isInRole($this->higherUserId, ['userrole_id' => $this->normalRolePriority, 'isHigherOrEquals' => true]));
        unset($UsersRolesDb);
    }// testIsInRole


}
