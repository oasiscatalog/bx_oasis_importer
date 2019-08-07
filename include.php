<?php

$pathJS = '/bitrix/js/oasis_importer';
$pathCSS = '/bitrix/js/oasis_importer/css';
$pathLang = BX_ROOT . '/modules/oasis_importer/lang/' . LANGUAGE_ID;

use Bitrix\Main\Loader;
use Bitrix\OasisImporter\Api;
use Bitrix\OasisImporter\Bootstrap;
use Bitrix\OasisImporter\Categories;
use Bitrix\OasisImporter\Products;

Loader::registerAutoLoadClasses(
    'oasis_importer',
    [
        '\Bitrix\OasisImporter\Api'        => 'lib/api.php',
        '\Bitrix\OasisImporter\Bootstrap'  => 'lib/bootstrap.php',
        '\Bitrix\OasisImporter\Categories' => 'lib/categories.php',
        '\Bitrix\OasisImporter\Params'     => 'lib/params.php',
        '\Bitrix\OasisImporter\Products'   => 'lib/products.php',
    ]
);

/**
 * Class COasisModule
 */
Class COasisModule
{
    /**
     * @return string
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\SystemException
     */
    public static function agent()
    {
        $apiKey = Bootstrap::getApiKey();
        $catalogMapping = Categories::getList();

        if (!empty($apiKey) && !empty($catalogMapping) && COption::GetOptionString("main", "agents_use_crontab", "N") == 'Y') {
            $cnt = 0;
            $api = new Api($apiKey);

            foreach ($catalogMapping as $sectionId => $sectionData) {
                $productsRaw = $api->getProductByCategory([$sectionData['CATEGORY_ID']], true);

                $products = [];
                foreach ($productsRaw as $product) {
                    $products[$product['group_id']][] = $product;
                }

                foreach ($products as $groupId => $productGroup) {
                    Products::upsertProduct($sectionData, $productGroup);
                    $cnt++;
                }
            }
        }

        return "COasisModule::agent();";
    }
}
