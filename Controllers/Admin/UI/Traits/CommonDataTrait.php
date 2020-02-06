<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Controllers\Admin\UI\Traits;


/**
 * UI trait for use between admin controllers.
 * 
 * @since 0.1
 */
trait CommonDataTrait
{


    /**
     * Get config from DB.
     * 
     * This will get commonly used between admin controllers with these data.
     * <pre>
     * rdbadmin_SiteName,
     * rdbadmin_SiteTimezone,
     * rdbadmin_AdminItemsPerPage,
     * </pre>
     * 
     * @return array
     */
    protected function getConfigDb(): array
    {
        $ConfigDb = new \Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $configNames = [
            'rdbadmin_SiteName',
            'rdbadmin_SiteTimezone',
            'rdbadmin_AdminItemsPerPage',
        ];
        $configDefaults = [
            '',
            'Asia/Bangkok',
            '20',
        ];

        $output = $ConfigDb->get($configNames, $configDefaults);
        unset($ConfigDb, $configDefaults, $configNames);

        return $output;
    }// getConfigDb


    
    /**
     * Set CSS assets for common admin pages.
     * 
     * This is required to make basic admin pages working correctly.
     * 
     * @param \Modules\RdbAdmin\Libraries\Assets $Assets The Assets class.
     * @param array $assetsData The assets data. Please read more on `\Modules\RdbAdmin\Libraries\Assets::addMultipleAssets()`.
     */
    protected function setCssAssets(\Modules\RdbAdmin\Libraries\Assets $Assets, array $assetsData)
    {
        $Url = new \System\Libraries\Url($this->Container);

        $Assets->addMultipleAssets(
            'css',
            [
                'rdta',
                'rdbaCommonAdminMainLayout',
            ],
            $assetsData
        );
    }// setCss


    /**
     * Set JS assets and its object for XHR common data.
     * 
     * This is required to make basic admin pages working correctly.
     * 
     * @param \Modules\RdbAdmin\Libraries\Assets $Assets The Assets class.
     * @param array $assetsData The assets data. Please read more on `\Modules\RdbAdmin\Libraries\Assets::addMultipleAssets()`.
     */
    protected function setJsAssetsAndObject(\Modules\RdbAdmin\Libraries\Assets $Assets, array $assetsData)
    {
        $Url = new \System\Libraries\Url($this->Container);

        $Assets->addMultipleAssets(
            'js', 
            [
                'rdta',
                'rdbaUiXhrCommonData',
            ], 
            $assetsData
        );

        $Assets->addJsObject(
            'rdbaUiXhrCommonData',
            'RdbaUIXhrCommonData',
            [
                'uiXhrCommonDataUrl' => $Url->getAppBasedPath(true) . '/admin/ui/xhr-common-data',
                'uiXhrCommonDataMethod' => 'GET',
                'breadcrumbBased' => [$Url->getAppBasedPath(true) . '/admin', __('Admin home')],
                'currentUrl' => $Url->getCurrentUrl() . $Url->getQuerystring(),
                'currentUrlRaw' => $Url->getCurrentUrl(true) . $Url->getQuerystring(),
                'currentLanguage' => ($_SERVER['RUNDIZBONES_LANGUAGE'] ?? ''),
                'currentLocale' => (isset($_SERVER['RUNDIZBONES_LANGUAGE_LOCALE']) ? json_decode($_SERVER['RUNDIZBONES_LANGUAGE_LOCALE'], true) : []),
                'configDb' => $this->getConfigDb(),
                'paginationSymbol' => [
                    'first' => '&laquo;',
                    'last' => '&raquo;',
                    'previous' => '&lsaquo;',
                    'next' => '&rsaquo;',
                ],
                'userData' => new \stdClass(),
            ]
        );
    }// setJsAssetsAndObject


}
