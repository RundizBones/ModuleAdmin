<?php
/**
 * Module Name: RdbAdmin module.
 * Description: The administrator module for RundizBones framework.
 * Requires PHP: 7.4.0
 * Requires Modules: Languages
 * Author: Vee W.
 * Gettext Domain: rdbadmin
 * 
 * @package RdbAdmin
 * @version 1.2.9a-20240203
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin;


/**
 * Module installer class for RdbAdmin.
 * 
 * @since 0.1
 */
class Installer implements \Rdb\System\Interfaces\ModuleInstaller
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var \Rdb\System\Libraries\Db
     */
    protected $Db;


    /**
     * @var \Rdb\System\Libraries\Logger
     */
    protected $Logger;


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;

        if ($this->Container->has('Db')) {
            $this->Db = $this->Container->get('Db');
        } else {
            $this->Db = new \Rdb\System\Libraries\Db($Container);
        }

        if ($this->Container->has('Logger')) {
            $this->Logger = $this->Container->get('Logger');
        } else {
            $this->Logger = new \Rdb\System\Libraries\Logger($Container);
        }
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function install()
    {
        try {
            $sqlString = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Installer.sql');
            $expSql = explode(';' . "\n", str_replace(["\r\n", "\r", "\n"], "\n", $sqlString));
            unset($sqlString);

            if (is_array($expSql)) {
                foreach ($expSql as $eachStatement) {
                    if (empty(trim($eachStatement))) {
                        continue;
                    }

                    $eachStatement = trim($eachStatement) . ';';
                    preg_match('/%\$(.[^ ]+)%/iu', $eachStatement, $matches);
                    if (isset($matches[1])) {
                        $tableName = $this->Db->tableName((string) $matches[1]);
                    }
                    unset($matches);

                    if (isset($tableName)) {
                        $eachStatement = preg_replace('/%\$(.[^ ]+)%/iu', $tableName, $eachStatement);

                        if (empty($eachStatement)) {
                            continue;
                        }

                        $this->Logger->write('modules/rdbadmin/installer', 0, $eachStatement);

                        $Sth = $this->Db->PDO()->prepare($eachStatement);
                        $execResult = $Sth->execute();
                        $Sth->closeCursor();
                        unset($Sth);
                        if ($execResult === true) {
                            $this->Db->convertCharsetAndCollation($tableName, null);
                        }
                        unset($execResult, $tableName);
                    }
                }// endforeach;
                unset($eachStatement);
            }
            unset($expSql);
        } catch (\Exception $e) {
            $this->Logger->write('modules/rdbadmin/installer', 3, $e->getMessage());
            throw $e;
        }
    }// install


    /**
     * {@inheritDoc}
     */
    public function uninstall()
    {
        try {
            $sqlString = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Installer.sql');
            // remove comments --------------------------------------------------------------------------
            // @link https://regex101.com/r/GXb0a5/2 pattern original source code.
            $pattern = '/["\'`][^"\'`]*(?!\\\\)["\'`](*SKIP)(*F)       # Make sure we\'re not matching inside of quotes, double quotes or backticks
                |(?m-s:\s*(?:\-{2}|\#)[^\n]*$) # Single line comment
                |(?:
                  \/\*.*?\*\/                  # Multi-line comment
                  (?(?=(?m-s:[\t ]+$))         # Get trailing whitespace if any exists and only if it\'s the rest of the line
                    [\t ]+
                  )
                )/iusx';
            $sqlString = preg_replace($pattern, '', $sqlString);
            // end remove comments ---------------------------------------------------------------------

            preg_match_all('/%\$(.[^ ]+)%/miu', $sqlString, $matches);

            if (isset($matches[1]) && is_array($matches[1])) {
                $tables = array_unique($matches[1]);
                foreach ($tables as $table) {
                    $sql = 'DROP TABLE IF EXISTS `' . $this->Db->tableName($table) . '`;';

                    $stmt = $this->Db->PDO()->prepare($sql);
                    unset($sql);
                    $stmt->execute();
                    $stmt->closeCursor();
                    unset($stmt);
                }// endforeach;
                unset($table, $tables);
            }
            unset($sqlString);
        } catch (\Exception $e) {
            $this->Logger->write('modules/rdbadmin/installer', 3, $e->getMessage());
            throw $e;
        }
    }// uninstall


    /**
     * {@inheritDoc}
     */
    public function update()
    {
        try {
            if (method_exists($this->Db, 'alterStructure')) {
                $sqlString = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Installer.sql');
                $sqlString = $this->Db->removeSQLComments($sqlString);
                $expSql = explode(';' . "\n", str_replace(["\r\n", "\r", "\n"], "\n", $sqlString));
                unset($sqlString);

                if (is_array($expSql)) {
                    foreach ($expSql as $eachStatement) {
                        if (empty(trim($eachStatement))) {
                            continue;
                        }

                        $eachStatement = trim($eachStatement) . ';';
                        preg_match('/%\$(.[^ ]+)%/iu', $eachStatement, $matches);
                        if (isset($matches[1])) {
                            $tableName = $this->Db->tableName((string) $matches[1]);
                        }
                        unset($matches);

                        if (isset($tableName)) {
                            $eachStatement = preg_replace('/%\$(.[^ ]+)%/iu', $tableName, $eachStatement);

                            if (empty($eachStatement)) {
                                continue;
                            }

                            $this->Logger->write('modules/rdbadmin/installer', 0, $eachStatement);

                            $alterResults = $this->Db->alterStructure($eachStatement);
                            $this->Logger->write('modules/rdbadmin/installer', 0, 'Alter results: {alterResults}', ['alterResults' => $alterResults]);
                            $this->Db->convertCharsetAndCollation($tableName, null);
                            unset($alterResults, $tableName);
                        }
                    }// endforeach;
                    unset($eachStatement);
                }// endif;
                unset($expSql);
            }// endif; `alterStructure` method exists
        } catch (\Exception $e) {
            $this->Logger->write('modules/rdbadmin/installer', 3, $e->getMessage());
            throw $e;
        }
    }// update


}
