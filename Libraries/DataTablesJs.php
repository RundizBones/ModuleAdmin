<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Libraries;


/**
 * DataTables class that work with datatables.net (JS).
 * 
 * @since 0.1
 */
class DataTablesJs
{


    /**
     * Build sort & order values from parameters such as GET method.
     * 
     * @link https://en.wikipedia.org/wiki/Sorting The word definition of sort and order.
     * @link http://www.mathsteacher.com.au/year7/ch02_power/06_asc/asc.htm The word definition of sort and order.
     * @link https://www.mathsisfun.com/definitions/ascending-order.html The word definition of sort and order.
     * @param array $columns The array from `$_GET` value of 'columns' name.
     * @param array $order The array from `$_GET` value of 'order' name.
     * @return array Return array list of sort & order values by the order of multi sort. Example:
     * <pre>
     * array(
     *     array(
     *         'sort' => 'my_field_name1',
     *         'order' => 'asc',
     *     ),
     *     array(
     *         'sort' => 'my_field_name2',
     *         'order' => 'asc',
     *     ),
     * );
     * </pre>
     */
    public function buildSortOrdersFromInput(array $columns = [], array $order = []): array
    {
        $output = [];
        $i = 0;

        if (is_array($order)) {
            foreach ($order as $index => $items) {
                if (
                    is_array($items) && 
                    array_key_exists('column', $items) && 
                    array_key_exists('dir', $items) &&
                    is_scalar($items['dir']) &&
                    is_numeric($items['column'])
                ) {
                    if (
                        is_array($columns) && 
                        array_key_exists($items['column'], $columns) && 
                        array_key_exists('data', $columns[$items['column']]) && 
                        is_scalar($columns[$items['column']]['data']) && 
                        array_key_exists('orderable', $columns[$items['column']]) && 
                        $columns[$items['column']]['orderable'] === 'true'
                    ) {
                        $output[$i] = [
                            'sort' => str_replace(';', '', $columns[$items['column']]['data']),
                            'order' => str_replace(';', '', $items['dir']),// asc, desc
                        ];
                    }
                    $i++;
                }
            }// endforeach;
            unset($index, $items);
        }

        return $output;
    }// buildSortOrdersFromInput


}
