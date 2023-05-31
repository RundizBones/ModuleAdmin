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


    /**
     * @var int The ID of user who has higher role than normal.
     */
    protected $higherUserId = -1;


    /**
     * @var int The ID of normal role level. Typically it is 3 which is 'member'.
     */
    protected $normalRoleId = 3;


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

        $this->Db = $this->Container['Db'];

        if ($this->Db->currentConnectionKey() === null) {
            $this->markTestIncomplete('Unable to connect to DB.');
        }

        // setup roles. ----------------------------------------
        $UserRolesDb = new \Rdb\Modules\RdbAdmin\Models\UserRolesDb($this->Container);
        $this->higherRoleId = $UserRolesDb->add([
            'userrole_name' => 'higherThanMember_for_test' . time() . uniqid(),
            'userrole_priority' => 10,
        ]);
        if (is_numeric($this->higherRoleId)) {
            $this->higherRoleId = (int) $this->higherRoleId;
        }
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
        $UsersDb->delete($this->higherUserId);
        $UsersDb->delete($this->normalUserId);
        unset($UsersDb, $UserRolesDb);
        // end clean up things. -------------------------------

        $this->Db->disconnectAll();
    }// tearDown


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


}
