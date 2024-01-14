<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Controllers\Admin\UI\Traits;


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
        $ConfigDb = new \Rdb\Modules\RdbAdmin\Models\ConfigDb($this->Container);
        $configNames = [
            'rdbadmin_SiteName',
            'rdbadmin_SiteTimezone',
            'rdbadmin_AdminItemsPerPage',
            'rdbadmin_SiteAllowOrigins',
            'rdbadmin_SiteFavicon',
        ];
        $configDefaults = [
            '',
            'Asia/Bangkok',
            '20',
            '',
            '',
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
     * @param \Rdb\Modules\RdbAdmin\Libraries\Assets $Assets The Assets class.
     * @param array $assetsData The assets data. Please read more on `\Rdb\Modules\RdbAdmin\Libraries\Assets::addMultipleAssets()`.
     */
    protected function setCssAssets(\Rdb\Modules\RdbAdmin\Libraries\Assets $Assets, array $assetsData)
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);

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
     * @param \Rdb\Modules\RdbAdmin\Libraries\Assets $Assets The Assets class.
     * @param array $assetsData The assets data. Please read more on `\Rdb\Modules\RdbAdmin\Libraries\Assets::addMultipleAssets()`.
     */
    protected function setJsAssetsAndObject(\Rdb\Modules\RdbAdmin\Libraries\Assets $Assets, array $assetsData)
    {
        $Url = new \Rdb\System\Libraries\Url($this->Container);

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
                    'first' => '<i class="fa-solid fa-backward-step fontawesome-icon"></i>',
                    'last' => '<i class="fa-solid fa-forward-step fontawesome-icon"></i>',
                    'previous' => '<i class="fa-solid fa-caret-left fontawesome-icon"></i>',
                    'next' => '<i class="fa-solid fa-caret-right fontawesome-icon"></i>',
                ],
                'userData' => new \stdClass(),
                'urls' => [
                    'baseUrl' => $Url->getAppBasedPath(),
                    'baseUrlRaw' => $Url->getAppBasedPath(true),
                    'publicUrl' => $Url->getPublicUrl(),
                ],
            ]
        );
    }// setJsAssetsAndObject


}
