<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Tests\Libraries;


class DataTableJsTest extends \Tests\Rdb\BaseTestCase
{


    /**
     * @var \Modules\RdbAdmin\Libraries\DataTablesJs
     */
    protected $DataTablesJs;


    /**
     * @var \Modules\RdbAdmin\Libraries\Input
     */
    protected $Input;


    public function setup()
    {
        $this->runApp('GET', '/?draw=5&columns[0][data]=&columns[0][name]=&columns[0][searchable]=false&columns[0][orderable]=false&columns[0][search][value]=&columns[0][search][regex]=false&columns[1][data]=id&columns[1][name]=&columns[1][searchable]=false&columns[1][orderable]=false&columns[1][search][value]=&columns[1][search][regex]=false&columns[2][data]=user_login&columns[2][name]=&columns[2][searchable]=true&columns[2][orderable]=true&columns[2][search][value]=&columns[2][search][regex]=false&columns[3][data]=user_display_name&columns[3][name]=&columns[3][searchable]=true&columns[3][orderable]=true&columns[3][search][value]=&columns[3][search][regex]=false&columns[4][data]=user_email&columns[4][name]=&columns[4][searchable]=true&columns[4][orderable]=true&columns[4][search][value]=&columns[4][search][regex]=false&columns[5][data]=groups&columns[5][name]=&columns[5][searchable]=true&columns[5][orderable]=false&columns[5][search][value]=&columns[5][search][regex]=false&columns[6][data]=user_status&columns[6][name]=&columns[6][searchable]=true&columns[6][orderable]=true&columns[6][search][value]=&columns[6][search][regex]=false&order[0][column]=2&order[0][dir]=asc&order[1][column]=4&order[1][dir]=desc&start=0&length=20&search[value]=&search[regex]=false&_=1568003314938');

        $this->Input = new \Modules\RdbAdmin\Libraries\Input();
        $this->DataTablesJs = new \Modules\RdbAdmin\Libraries\DataTablesJs();
    }// setup


    public function testBuildSortOrdersFromInput()
    {
        $buildResult = $this->DataTablesJs->buildSortOrdersFromInput(
            $this->Input->get('columns', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY),
            $this->Input->get('order', [], FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY)
        );

        $assert = [
            [
                'sort' => 'user_login',
                'order' => 'asc',
            ],
            [
                'sort' => 'user_email',
                'order' => 'desc',
            ],
        ];
        $this->assertArraySubset($assert, $buildResult);
    }// testBuildSortOrdersFromInput


}
