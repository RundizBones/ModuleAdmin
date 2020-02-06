<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Tests\Libraries;


/**
 * Extended Assets class for tests.
 * 
 * @since 0.1
 */
class AssetsExtended extends \Modules\RdbAdmin\Libraries\Assets
{


    public function generateAssetUrlWithVersion(array $item): string
    {
        return parent::generateAssetUrlWithVersion($item);
    }// generateAssetUrlWithVersion


    public function generateAttributes(array $attributes, array $disallowAttributes = array()): string
    {
        return parent::generateAttributes($attributes, $disallowAttributes);
    }// generateAttributes


    public function generateInlineScript(array $item, string $position = 'after'): string
    {
        return parent::generateInlineScript($item, $position);
    }// generateInlineScript


    public function generateInlineStyle(array $item): string
    {
        return parent::generateInlineStyle($item);
    }// generateInlineStyle


    public function generateJsObject(array $item): string
    {
        return parent::generateJsObject($item);
    }// generateJsObject


    public function getAddedAssets()
    {
        return $this->addedAssets;
    }// getAddedAssets


    public function getAssetsSorted()
    {
        return $this->assetsSorted;
    }// getAssetsSorted


    public function getDependencyExists(string $type, array $dependency): array
    {
        return parent::getDependencyExists($type, $dependency);
    }// getDependencyExists


    public function removeAsset(string $type, string $handle)
    {
        return parent::removeAsset($type, $handle);
    }// removeAsset


    public function topologicalSort(string $type)
    {
        return parent::topologicalSort($type);
    }// topologicalSort


    public function verifyType(string $type): string
    {
        return parent::verifyType($type);
    }// verifyType


}
