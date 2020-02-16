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

        unset($configTable, $TableAdapter);
    }// down
}
