<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\Modules\Assets;


/**
 * Module's assets controller.
 * 
 * @since 1.1.8
 */
class AssetsController extends \Rdb\Modules\RdbAdmin\Controllers\Admin\AdminBaseController
{


    use \Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits\CommonDataTrait;


    use Traits\AssetsTrait;


    /**
     * Get module's assets.
     * 
     * @param array $configDb
     * @return array
     */
    protected function getModulesAssets(array $configDb): array
    {
        $output = [];

        $offset = (int) $this->Input->get('start', 0, FILTER_SANITIZE_NUMBER_INT);
        $limit = $configDb['rdbadmin_AdminItemsPerPage'];

        $DI = new \FilesystemIterator(MODULE_PATH, \FilesystemIterator::SKIP_DOTS);
        $totalModules = iterator_count($DI);
        $DI = new \Rdb\Modules\RdbAdmin\Libraries\SPLIterators\FilterOnlyDir($DI);
        $filteredTotal = iterator_count($DI);
        $DI = (new \Rdb\Modules\RdbAdmin\Libraries\SPLIterators\SortableIterator($DI, \Rdb\Modules\RdbAdmin\Libraries\SPLIterators\SortableIterator::SORT_BY_NAME_NATURAL))->getIterator();
        $DI = new \LimitIterator($DI, $offset, $limit);
        unset($limit, $offset);

        $items = [];
        $i = 0;
        foreach ($DI as $File) {
            $assetsPath = $File->getPathname() . DIRECTORY_SEPARATOR . 'assets';
            if (is_dir($assetsPath)) {
                $RDI = new \RecursiveDirectoryIterator($assetsPath, \FilesystemIterator::SKIP_DOTS);
                $RDI = new \RecursiveIteratorIterator(
                    $RDI,
                    \RecursiveIteratorIterator::SELF_FIRST,
                    \RecursiveIteratorIterator::CATCH_GET_CHILD
                );
                $totalAssets = iterator_count($RDI);
                unset($RDI);
            } else {
                $totalAssets = 0;
            }

            $items[$i] = [
                'id' => $File->getFilename(),
                'module_number_assets' => $totalAssets,
                'module_location' => ($totalAssets > 0 ? $assetsPath : ''),
                'enabled' => (is_file($File->getPathname() . DIRECTORY_SEPARATOR . '.disabled') ? false : true),
            ];
            $i++;
            unset($assetsPath, $totalAssets);
        }// endforeach;
        unset($DI, $File, $i);

        $output['draw'] = $this->Input->get('draw', 1, FILTER_SANITIZE_NUMBER_INT);
        $output['recordsTotal'] = $totalModules;
        $output['recordsFiltered'] = $filteredTotal;
        $output['listItems'] = $items;

        unset($filteredTotal, $totalModules);
        return $output;
    }// getModulesAssets


    /**
     * List module's assets page.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        // processing part ----------------------------------------------------------------------------------------------------
        $this->checkPermission('RdbAdmin', 'RdbAdminModulesAssets', ['publishAssets']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $Csrf = new \Rdb\Modules\RdbAdmin\Libraries\Csrf();
        $Url = new \Rdb\System\Libraries\Url($this->Container);

        $output = [];
        $output['configDb'] = $this->getConfigDb();
        $output = array_merge($output, $Csrf->createToken());
        unset($Csrf);

        $output['urls'] = $this->getMAssetsUrlsMethods();

        $output['pageTitle'] = __('Modules Assets');
        $output['pageHtmlTitle'] = $this->getPageHtmlTitle($output['pageTitle'], $output['configDb']['rdbadmin_SiteName']);
        $output['pageHtmlClasses'] = $this->getPageHtmlClasses();

        if ($this->Input->isNonHtmlAccept()) {
            // if custom accept type.
            $output = array_merge($output, $this->getModulesAssets($output['configDb']));
        } else {
            $UserPermissionsDb = new \Rdb\Modules\RdbAdmin\Models\UserPermissionsDb($this->Container);
            $output['permissions'] = [];
            $output['permissions']['publishAssets'] = $UserPermissionsDb->checkPermission('RdbAdmin', 'RdbAdminModulesAssets', 'publishAssets');
            unset($UserPermissionsDb);
        }

        if (strpos(MODULE_PATH, PUBLIC_PATH) === 0) {
            $output['formResultStatus'] = 'info';
            $output['formResultMessage'] = __('Your module folder is already in public, it is no need to publish anything.');
        }

        // display, response part ---------------------------------------------------------------------------------------------
        if ($this->Input->isNonHtmlAccept()) {
            // if custom HTTP accept, response content.
            // response the data.
            $this->responseNoCache();
            return $this->responseAcceptType($output);
        } else {
            // if not custom HTTP accept.
            $rdbAdminAssets = $this->getRdbAdminAssets();
            $Assets = new \Rdb\Modules\RdbAdmin\Libraries\Assets($this->Container);

            $Assets->addMultipleAssets('css', ['datatables', 'rdbaCommonListDataPage'], $rdbAdminAssets);
            $Assets->addMultipleAssets('js', ['rdbaModulesAssets'], $rdbAdminAssets);
            $Assets->addJsObject(
                'rdbaModulesAssets',
                'RdbaModulesAssetsObject',
                [
                    'isInDataTablesPage' => true,
                    'csrfName' => $output['csrfName'],
                    'csrfValue' => $output['csrfValue'],
                    'csrfKeyPair' => $output['csrfKeyPair'],
                    'permissions' => $output['permissions'],
                    'txtAreYouSurePublish' => __('Are you sure?') . "\n" . __('This will be overwrite any existing assets files and folders in the public folder.'),
                    'txtDisabled' => __('Disabled'),
                    'txtEnabled' => __('Enabled'),
                    'txtPleaseSelectAction' => __('Please select an action.'),
                    'txtPleaseSelectAtLeastOneModule' => __('Please select at least one module.'),
                    'urlAppBased' => $Url->getAppBasedPath(),
                    'urls' => $output['urls'],
                ]
            );

            $this->setCssAssets($Assets, $rdbAdminAssets);
            $this->setJsAssetsAndObject($Assets, $rdbAdminAssets);

            $output['Assets'] = $Assets;
            $output['Modules'] = $this->Modules;
            $output['Url'] = $Url;
            $output['Views'] = $this->Views;
            $output['pageContent'] = $this->Views->render('Admin/Modules/Assets/index_v', $output);

            unset($Assets, $rdbAdminAssets, $Url);
            return $this->Views->render('common/Admin/mainLayout_v', $output);
        }
    }// indexAction


}
