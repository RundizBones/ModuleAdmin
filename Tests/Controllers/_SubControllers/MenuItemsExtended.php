<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Controllers\_SubControllers;


/**
 * Extended of MenuItems class.
 * 
 * @since 0.1
 */
class MenuItemsExtended extends \Rdb\Modules\RdbAdmin\Controllers\_SubControllers\MenuItems
{


    public function addToMenu(array $menuItems, array $additionalMenuItems): array
    {
        return parent::addToMenu($menuItems, $additionalMenuItems);
    }// addToMenu


    public function convertMenuArrayKeyToFloat(array $menuItems): array
    {
        return parent::convertMenuArrayKeyToFloat($menuItems);
    }// convertMenuArrayKeyToFloat


    public function increaseArrayKeyDec($number, array $menuItems): string
    {
        return parent::increaseArrayKeyDec($number, $menuItems);
    }// increaseArrayKeyDec


}
