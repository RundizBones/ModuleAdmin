<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Tests\Controllers\SubControllers;


use Rdb\Modules\RdbAdmin\Tests\PHPUnitFunctions\Arrays;


class MenuItemsTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var \Rdb\Modules\RdbAdmin\Tests\Controllers\_SubControllers\MenuItemsExtended
     */
    protected $MenuItems;


    protected $menuItems = [];


    public function setup(): void
    {
        $this->Container = new \Rdb\System\Container();
        $Modules = new \Rdb\System\Modules($this->Container);
        $this->Container['Modules'] = function ($c) use ($Modules) {
            return $Modules;
        };
        unset($Modules);

        $this->MenuItems = new \Rdb\Modules\RdbAdmin\Tests\Controllers\_SubControllers\MenuItemsExtended($this->Container);

        $this->menuItems = [
            0 => [
                'id' => 'admin-home',
                'name' => 'Admin home',
                'link' => '/admin',
            ],// 0
            5 => [
                'id' => 'admin-posts',
                'name' => 'Posts',
                'link' => '/admin/posts',
            ],
            100 => [
                'id' => 'admin-users',
                'name' => 'Users',
                'link' => '/admin/users',
                'subMenu' => [
                    0 => [
                        'id' => 'admin-users-list',
                        'name' => 'Manage users',
                        'link' => '/admin/users',
                    ],
                    1 => [
                        'id' => 'admin-users-add',
                        'name' => 'Add new user',
                        'link' => '/admin/users/new',
                    ],
                ],
            ],
        ];
    }// setup


    public function tearDown(): void
    {
        $this->menuItems = [];
    }// tearDown


    public function testAddToMenu()
    {
        $this->assertCount(3, $this->menuItems);
        $menuItems = $this->MenuItems->convertMenuArrayKeyToFloat($this->menuItems);

        $additionalMenuItems = [
            6 => [
                'id' => 'admin-pages',
                'name' => 'Pages',
                'link' => '/admin/pages',
            ],
        ];
        $menuItems = $this->MenuItems->addToMenu(
            $menuItems, 
            $this->MenuItems->convertMenuArrayKeyToFloat($additionalMenuItems)
        );
        $this->assertCount(4, $menuItems);
        $assertItem = [
            '6.0' => [
                'id' => 'admin-pages',
                'name' => 'Pages',
                'link' => '/admin/pages',
            ],
        ];
        $this->assertTrue(is_array($menuItems));
        $this->assertTrue(is_array($assertItem));
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive($assertItem, $menuItems))
        );

        $additionalMenuItems = [
            5 => [
                'id' => 'admin-posts',
                'name' => 'Posts',
                'link' => '/admin/posts',
            ],
        ];
        $menuItems = $this->MenuItems->addToMenu(
            $menuItems, 
            $this->MenuItems->convertMenuArrayKeyToFloat($additionalMenuItems)
        );
        $this->assertCount(5, $menuItems);
        $assertItem = [
            '5.1' => [
                'id' => 'admin-posts',
                'name' => 'Posts',
                'link' => '/admin/posts',
            ],
        ];
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive($assertItem, $menuItems))
        );
        $assert = ['0.0', '5.0', '5.1', '6.0', '100.0'];// assert added and sorted.
        $this->assertSame($assert, array_keys($menuItems));

        $additionalMenuItems = [
            100 => [
                'subMenu' => [
                    1 => [
                        'id' => 'admin-users-list2',
                        'name' => 'Additional list users',
                        'link' => '/admin/users?list=2',
                    ],
                    3 => [
                        'id' => 'admin-users-viewlogins',
                        'name' => 'View logins',
                        'link' => '/admin/users/logins',
                    ],
                ],
            ],
        ];
        $menuItems = $this->MenuItems->addToMenu(
            $menuItems, 
            $this->MenuItems->convertMenuArrayKeyToFloat($additionalMenuItems)
        );
        $assert = ['0.0', '5.0', '5.1', '6.0', '100.0'];// assert added and sorted.
        $this->assertSame($assert, array_keys($menuItems));
        $assert = ['0.0', '1.0', '1.1', '3.0'];// assert added and sorted of sub menu of key 100.
        $this->assertSame($assert, array_keys($menuItems['100.0']['subMenu']));
        $assert = [
            '0.0' => [
                'id' => 'admin-users-list',
                'name' => 'Manage users',
                'link' => '/admin/users',
            ],
            '1.0' => [
                'id' => 'admin-users-add',
                'name' => 'Add new user',
                'link' => '/admin/users/new',
            ],
            '1.1' => [
                'id' => 'admin-users-list2',
                'name' => 'Additional list users',
                'link' => '/admin/users?list=2',
            ],
            '3.0' => [
                'id' => 'admin-users-viewlogins',
                'name' => 'View logins',
                'link' => '/admin/users/logins',
            ],
        ];
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive($assert, $menuItems['100.0']['subMenu']))
        );
    }// testAddToMenu


    public function testConvertMenuArrayKeyToFloat()
    {
        $menuItems = $this->MenuItems->convertMenuArrayKeyToFloat($this->menuItems);
        $assert = [
            '0.0' => [
                'id' => 'admin-home',
                'name' => 'Admin home',
                'link' => '/admin',
            ],// 0
            '5.0' => [
                'id' => 'admin-posts',
                'name' => 'Posts',
                'link' => '/admin/posts',
            ],
            '100.0' => [
                'id' => 'admin-users',
                'name' => 'Users',
                'link' => '/admin/users',
                'subMenu' => [
                    '0.0' => [
                        'id' => 'admin-users-list',
                        'name' => 'Manage users',
                        'link' => '/admin/users',
                    ],
                    '1.0' => [
                        'id' => 'admin-users-add',
                        'name' => 'Add new user',
                        'link' => '/admin/users/new',
                    ],
                ],
            ],
        ];
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive($assert, $menuItems))
        );
        // convert again but still be the same array key, not possible to convert 1 to '1.0' and '1.0.0'. just '1.0'.
        $this->assertTrue(
            empty(Arrays::array_diff_assoc_recursive($assert, $this->MenuItems->convertMenuArrayKeyToFloat($menuItems)))
        );
    }// testConvertMenuArrayKeyToFloat


    public function testIncreaseArrayKeyDec()
    {
        $menuItems = $this->MenuItems->convertMenuArrayKeyToFloat($this->menuItems);

        $increasedNumber = $this->MenuItems->increaseArrayKeyDec('5.0', $menuItems);
        $this->assertSame('5.1', $increasedNumber);

        $menuItems = [
            '0.0' => [
                'id' => 'admin-home',
                'name' => 'Admin home',
                'link' => '/admin',
            ],// 0
            '5.0' => [
                'id' => 'admin-posts',
                'name' => 'Posts',
                'link' => '/admin/posts',
            ],
            '5.1' => [
                'id' => 'admin-posts-5.1',
                'name' => 'Additional posts',
                'link' => '/admin/posts5.1',
            ],
            '100.0' => [
                'id' => 'admin-users',
                'name' => 'Users',
                'link' => '/admin/users',
                'subMenu' => [
                    '0.0' => [
                        'id' => 'admin-users-list',
                        'name' => 'Manage users',
                        'link' => '/admin/users',
                    ],
                    '1.0' => [
                        'id' => 'admin-users-add',
                        'name' => 'Add new user',
                        'link' => '/admin/users/new',
                    ],
                ],
            ],
        ];

        $increasedNumber = $this->MenuItems->increaseArrayKeyDec('5.0', $menuItems);
        $this->assertSame('5.2', $increasedNumber);

        $increasedNumber = $this->MenuItems->increaseArrayKeyDec('955.0', $menuItems);
        $this->assertSame('955.0', $increasedNumber);
    }// testIncreaseArrayKeyDec


}
