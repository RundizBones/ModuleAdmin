<?php

use Phinx\Migration\AbstractMigration;

class AddMoreUserConfigurations extends AbstractMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $TableAdapter = new \Phinx\Db\Adapter\TablePrefixAdapter($this->getAdapter());
        $configTable = $TableAdapter->getAdapterTableName('config');

        $Sth = $this->query('SELECT * FROM `' . $configTable . '` WHERE `config_name` = \'rdbadmin_UserRegisterWaitVerification\'');
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        if (empty($result)) {
            $singleRow = [
                'config_name'    => 'rdbadmin_UserRegisterWaitVerification',
                'config_value'  => 2,
                'config_description' => 'How many days that user needs to take action to verify their email on register or added by admin?',
            ];

            $this->table('config')->insert($singleRow)->save();
        }

        $Sth = $this->query('SELECT * FROM `' . $configTable . '` WHERE `config_name` = \'rdbadmin_UserLoginLogsKeep\'');
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        if (empty($result)) {
            $singleRow = [
                'config_name'    => 'rdbadmin_UserLoginLogsKeep',
                'config_value'  => 90,
                'config_description' => 'How many days that user logins data to keep in database?',
            ];

            $this->table('config')->insert($singleRow)->save();
        }

        $Sth = $this->query('SELECT * FROM `' . $configTable . '` WHERE `config_name` = \'rdbadmin_UserDeleteSelfGrant\'');
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        if (empty($result)) {
            $singleRow = [
                'config_name'    => 'rdbadmin_UserDeleteSelfGrant',
                'config_value'  => 0,
                'config_description' => 'Allow user to delete themself?\n0=do not allowed\n1=allowed.',
            ];

            $this->table('config')->insert($singleRow)->save();
        }

        $Sth = $this->query('SELECT * FROM `' . $configTable . '` WHERE `config_name` = \'rdbadmin_UserDeleteSelfKeep\'');
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        if (empty($result)) {
            $singleRow = [
                'config_name'    => 'rdbadmin_UserDeleteSelfKeep',
                'config_value'  => 30,
                'config_description' => 'On delete user wether delete themself or by admin, How many days before it gets actual delete?',
            ];

            $this->table('config')->insert($singleRow)->save();
        }

        unset($configTable, $result, $singleRow, $Sth, $TableAdapter);
    }// up


    /**
     * Migrate down.
     */
    public function down()
    {
        $TableAdapter = new \Phinx\Db\Adapter\TablePrefixAdapter($this->getAdapter());
        $configTable = $TableAdapter->getAdapterTableName('config');
        $this->execute('DELETE FROM `' . $configTable . '` WHERE `config_name` = \'rdbadmin_UserRegisterWaitVerification\'');
        $this->execute('DELETE FROM `' . $configTable . '` WHERE `config_name` = \'rdbadmin_UserLoginLogsKeep\'');
        $this->execute('DELETE FROM `' . $configTable . '` WHERE `config_name` = \'rdbadmin_UserDeleteSelfGrant\'');
        $this->execute('DELETE FROM `' . $configTable . '` WHERE `config_name` = \'rdbadmin_UserDeleteSelfKeep\'');

        unset($configTable, $TableAdapter);
    }// down
}
