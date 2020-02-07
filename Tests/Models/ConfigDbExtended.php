<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Models;


/**
 * Description of ConfigDbExtend
 *
 * @author mr.v
 */
class ConfigDbExtended extends \Rdb\Modules\RdbAdmin\Models\ConfigDb
{


    /**
     * {@inheritDoc}
     */
    public function getMultiple(array $names, array $defaults = array()): array
    {
        return parent::getMultiple($names, $defaults);
    }// getMultiple


    /**
     * {@inheritDoc}
     */
    public function getRow(string $name, $default = '')
    {
        return parent::getRow($name, $default);
    }// getRow


}
