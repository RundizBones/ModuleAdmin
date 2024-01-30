<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Models;


class UserFieldsDbTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\System\Libraries\Db
     */
    protected $Db;


    /**
     * @var array
     */
    protected $userFieldsVals = [];


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

        // put current value in DB to property to restore them later. ---------------------------
        // get only first user id records.
        $PDO = $this->Db->PDO();
        $Sth = $PDO->prepare('SELECT `user_id` FROM `' . $this->Db->tableName('user_fields') . '` ORDER BY `user_id` ASC LIMIT 0, 1');
        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($Sth);

        if (is_object($result)) {
            // retrieve all data related to the first user found.
            $Sth = $PDO->prepare('SELECT `userfield_id`, `user_id`, `field_name`, `field_value` FROM `' . $this->Db->tableName('user_fields') . '` WHERE `user_id` = :user_id');
            $Sth->bindValue(':user_id', $result->user_id);
            $Sth->execute();
            $results = $Sth->fetchAll();
            $Sth->closeCursor();
            unset($Sth);
            if (is_iterable($results)) {
                $this->userFieldsVals = $results;
            }
            unset($results);
        }// endif; found user.
        unset($result);
        // end put current value in DB to property to restore them later. -----------------------
    }// setup


    public function tearDown(): void
    {
        // restore to original value in DB from property.
        foreach ($this->userFieldsVals as $userFieldsRow) {
            $this->Db->update($this->Db->tableName('user_fields'), ['field_value' => $userFieldsRow->field_value], ['userfield_id' => $userFieldsRow->userfield_id]);
        }// endforeach;
        unset($userFieldsRow);

        // disconnect DB.
        $this->Db->disconnectAll();
    }// tearDown


    public function testPropertyRdbaUserFields()
    {
        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);

        $this->assertTrue(isset($UserFieldsDb->rdbaUserFields['rdbadmin_uf_registerconfirm_key']));
        $this->assertGreaterThan(2, mb_strlen($UserFieldsDb->rdbaUserFields['rdbadmin_uf_registerconfirm_key']));
    }// testPropertyRdbaUserFields


    public function testGet()
    {
        $UserFieldsDb = new \Rdb\Modules\RdbAdmin\Models\UserFieldsDb($this->Container);
        $Serializer = new \Rundiz\Serializer\Serializer();

        // test original stored data must matched those retrieve via UserFieldsDb class.
        foreach ($this->userFieldsVals as $userFieldsRow) {
            $cacheVal = $UserFieldsDb->get($userFieldsRow->user_id, $userFieldsRow->field_name);
            $this->assertSame($cacheVal->field_value, $Serializer->maybeUnserialize($userFieldsRow->field_value));
        }// endforeach;
        unset($userFieldsRow);
    }// testGet


}
