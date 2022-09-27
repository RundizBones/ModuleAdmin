<?php

use Phinx\Migration\AbstractMigration;


class AddFaviconConfiguration extends AbstractMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $TableAdapter = new \Phinx\Db\Adapter\TablePrefixAdapter($this->getAdapter());
        $configTable = $TableAdapter->getAdapterTableName('config');

        $Sth = $this->query('SELECT * FROM `' . $configTable . '` WHERE `config_name` = \'rdbadmin_SiteFavicon\'');
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        if (empty($result)) {
            $singleRow = [
                'config_name'    => 'rdbadmin_SiteFavicon',
                'config_value'  => '',
                'config_description' => 'Favicon file related from public (root web) path. Do not begins with slash.',
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
        $this->execute('DELETE FROM `' . $configTable . '` WHERE `config_name` = \'rdbadmin_SiteFavicon\'');

        unset($configTable, $TableAdapter);
    }// down
}
