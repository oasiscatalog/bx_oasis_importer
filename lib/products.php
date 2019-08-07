<?php

namespace Bitrix\OasisImporter;

use Bitrix\Main\SystemException;
use CCatalog;
use CCatalogProduct;
use CCatalogStoreProduct;
use CFile;
use CIBlockElement;
use CIBlockProperty;
use CModule;
use CPrice;

/**
 * Class Products
 * @package Bitrix\OasisImporter
 */
class Products
{
    /**
     * @return array
     */
    public static function getCountBySectionId()
    {
        global $DB;

        $result = [];
        $query = $DB->Query("SELECT SECTION_ID, count(*) as CNT, max(DATE_MODIFY) as DATE_MODIFY FROM b_oasis_importer_products GROUP BY SECTION_ID", false);
        while ($row = $query->Fetch()) {
            $result[$row['SECTION_ID']] = $row;
        }
        return $result;
    }

    /**
     * @param $id
     * @param $iBlockId
     * @return array
     */
    public static function getProductById($id, $iBlockId)
    {
        global $DB;

        return $DB->Query("SELECT * FROM b_oasis_importer_products WHERE OASIS_PRODUCT_ID = '" . $id . "' AND IBLOCK_ID = " . $iBlockId,
            false)->Fetch();
    }

    /**
     * @param $category
     * @param $model
     * @return bool|mixed|null
     * @throws SystemException
     * @throws \Bitrix\Main\ObjectException
     */
    public static function upsertProduct($category, $model)
    {
        global $DB;
        CModule::IncludeModule("catalog");
        CModule::IncludeModule("iblock");

        $product = reset($model);

        $existProductMap = self::getProductById($product['id'], $category['IBLOCK_ID']);

        $arFields = [
            'NAME'              => $product['name'],
            'CODE'              => 'oasis_' . $product['article'],
            'IBLOCK_ID'         => $category['IBLOCK_ID'],
            'IBLOCK_SECTION_ID' => $category['SECTION_ID'],
            'ACTIVE'            => (isset($product['is_deleted']) && $product['is_deleted'] == true ? 'N' : 'Y'),
        ];

        $mainImage = isset($product['images'][0]['superbig']) ? $product['images'][0]['superbig'] : null;
        if ($mainImage && $product['images'][0]['updated_at'] > strtotime($existProductMap['DATE_MODIFY'])) {
            $arFields['PREVIEW_PICTURE'] = CFile::MakeFileArray($mainImage);
            $arFields['DETAIL_PICTURE'] = CFile::MakeFileArray($mainImage);
        }

        $optionsList = Params::getList();

        $properties = [];
        $propertiesRes = CIBlockProperty::GetList(["name" => "asc"],
            ["ACTIVE" => "Y", "IBLOCK_ID" => $category['IBLOCK_ID']]);
        while ($row = $propertiesRes->Fetch()) {
            if (isset($optionsList['option_' . $row['ID']])) {
                if ($optionsList['option_' . $row['ID']]['attr'] == 'images') {
                    foreach ($product['images'] as $k => $image) {
                        if ($image['updated_at'] > strtotime($existProductMap['DATE_MODIFY'])) {
                            $properties[$row['ID']]['n' . $k] = ['VALUE' => CFile::MakeFileArray($image['superbig'])];
                        }
                    }
                } else {
                    if ($row['PROPERTY_TYPE'] == 'L') {
                        $values = [];
                        if (isset($product[$optionsList['option_' . $row['ID']]['attr']])) {
                            $values[$product[$optionsList['option_' . $row['ID']]['attr']]] = 0;
                        } else {
                            foreach ((array)$product['attributes'] as $attr) {
                                if ($attr['name'] == $optionsList['option_' . $row['ID']]['attr']) {
                                    $values[$attr['value']] = 0;
                                }
                            }
                        }

                        if ($values) {
                            $cnt = 0;
                            $db_enum_list = CIBlockProperty::GetPropertyEnum($row['ID'], ['SORT' => 'ASC']);
                            while ($ar_enum = $db_enum_list->Fetch()) {
                                $cnt++;

                                if (isset($values[$ar_enum['VALUE']])) {
                                    $values[$ar_enum['VALUE']] = $ar_enum['ID'];
                                }

                                $ar_all_values[$ar_enum['ID']] = ['SORT' => $cnt, 'VALUE' => $ar_enum['VALUE']];
                            }
                            foreach ($values as $value => $enumId) {
                                if ($enumId == 0) {
                                    $cnt++;
                                    $ar_all_values[] = ['SORT' => $cnt, 'VALUE' => $value];
                                }
                            }

                            $CIBlockProp = new CIBlockProperty;
                            $CIBlockProp->UpdateEnum($row['ID'], $ar_all_values);

                            $db_enum_list = CIBlockProperty::GetPropertyEnum($row['ID'], ['sort' => 'asc']);
                            while ($ar_enum = $db_enum_list->Fetch()) {
                                if (isset($values[$ar_enum['VALUE']])) {
                                    if (count($values) > 1) {
                                        $properties[$row['ID']][] = $ar_enum['ID'];
                                    } else {
                                        $properties[$row['ID']] = ['VALUE' => $ar_enum['ID']];
                                    }
                                }
                            }
                        }
                    } else {
                        if (isset($product[$optionsList['option_' . $row['ID']]['attr']])) {
                            $properties[$row['ID']] = $product[$optionsList['option_' . $row['ID']]['attr']];
                        } else {
                            foreach ((array)$product['attributes'] as $attr) {
                                if ($attr['name'] == $optionsList['option_' . $row['ID']]['attr']) {
                                    $properties[$row['ID']] = $attr['value'];
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($properties) {
            $arFields['PROPERTY_VALUES'] = $properties;
        }

        $productId = null;
        $obElement = new CIBlockElement();
        $date = new \Bitrix\Main\Type\DateTime();
        if ($existProductMap) { // update
            $productId = $existProductMap['PRODUCT_ID'];
            if (strtotime($product['updated_at']) > strtotime($existProductMap['DATE_MODIFY'])) {
                $obElement->Update($existProductMap['PRODUCT_ID'], $arFields);
                $DB->Update("b_oasis_importer_products", [
                    'DATE_MODIFY' => "'" . $date->format('Y-m-d H:i:s') . "'",
                ], "WHERE PRODUCT_ID=" . $productId . "", '', false);
            }
        } else { // insert
            $productId = $obElement->Add($arFields, false, false, true);

            if ($productId) {
                $DB->Insert("b_oasis_importer_products", [
                    'IBLOCK_ID'        => $category['IBLOCK_ID'],
                    'SECTION_ID'       => $category['SECTION_ID'],
                    'PRODUCT_ID'       => $productId,
                    'OASIS_PRODUCT_ID' => "'" . $product['id'] . "'",
                    'DATE_CREATE'      => "'" . $date->format('Y-m-d H:i:s') . "'",
                    'DATE_MODIFY'      => "'" . $date->format('Y-m-d H:i:s') . "'",
                ], '', false);
            } else {
                throw new SystemException($obElement->LAST_ERROR);
            }
        }

        /** Создание/изменение товарного предложения */
        $offerIblock = CCatalog::GetList([],
            ['IBLOCK_TYPE_ID' => 'offers', 'PRODUCT_IBLOCK_ID' => $category['IBLOCK_ID']])->Fetch();

        foreach ($model as $productOffer) {
            $existOfferMap = self::getProductById($productOffer['id'], $offerIblock['IBLOCK_ID']);

            $arOfferFields = [
                'NAME'      => $productOffer['full_name'],
                'CODE'      => 'oasis_offer_' . $productOffer['article'],
                'IBLOCK_ID' => $offerIblock['IBLOCK_ID'],
                'ACTIVE'    => (isset($productOffer['is_deleted']) && $productOffer['is_deleted'] == true ? 'N' : 'Y'),
            ];

            $offerProperties = [
                $offerIblock['SKU_PROPERTY_ID'] => $productId,
            ];
            $propertiesRes = CIBlockProperty::GetList(["name" => "asc"],
                ["ACTIVE" => "Y", "IBLOCK_ID" => $offerIblock['IBLOCK_ID']]);
            while ($row = $propertiesRes->Fetch()) {
                if (isset($optionsList['option_' . $row['ID']])) {
                    if ($optionsList['option_' . $row['ID']]['attr'] == 'images') {
                        foreach ($productOffer['images'] as $k => $image) {
                            if ($image['updated_at'] > strtotime($existProductMap['DATE_MODIFY'])) {
                                $offerProperties[$row['ID']]['n' . $k] = ['VALUE' => CFile::MakeFileArray($image['superbig'])];
                            }
                        }
                    } else {
                        if ($row['PROPERTY_TYPE'] == 'L') {
                            $values = [];
                            if (isset($productOffer[$optionsList['option_' . $row['ID']]['attr']])) {
                                $values[$productOffer[$optionsList['option_' . $row['ID']]['attr']]] = 0;
                            } else {
                                foreach ((array)$productOffer['attributes'] as $attr) {
                                    if ($attr['name'] == $optionsList['option_' . $row['ID']]['attr']) {
                                        $values[$attr['value']] = 0;
                                    }
                                }
                            }

                            if ($values) {
                                $cnt = 0;
                                $db_enum_list = CIBlockProperty::GetPropertyEnum($row['ID'], ['SORT' => 'ASC']);
                                while ($ar_enum = $db_enum_list->Fetch()) {
                                    $cnt++;

                                    if (isset($values[$ar_enum['VALUE']])) {
                                        $values[$ar_enum['VALUE']] = $ar_enum['ID'];
                                    }

                                    $ar_all_values[$ar_enum['ID']] = ['SORT' => $cnt, 'VALUE' => $ar_enum['VALUE']];
                                }
                                foreach ($values as $value => $enumId) {
                                    if ($enumId == 0) {
                                        $cnt++;
                                        $ar_all_values[] = ['SORT' => $cnt, 'VALUE' => $value];
                                    }
                                }

                                $CIBlockProp = new CIBlockProperty;
                                $CIBlockProp->UpdateEnum($row['ID'], $ar_all_values);

                                $db_enum_list = CIBlockProperty::GetPropertyEnum($row['ID'], ['sort' => 'asc']);
                                while ($ar_enum = $db_enum_list->Fetch()) {
                                    if (isset($values[$ar_enum['VALUE']])) {
                                        if (count($values) > 1) {
                                            $offerProperties[$row['ID']][] = $ar_enum['ID'];
                                        } else {
                                            $offerProperties[$row['ID']] = ['VALUE' => $ar_enum['ID']];
                                        }
                                    }
                                }
                            }
                        } else {
                            if (isset($productOffer[$optionsList['option_' . $row['ID']]['attr']])) {
                                $offerProperties[$row['ID']] = $productOffer[$optionsList['option_' . $row['ID']]['attr']];
                            } else {
                                foreach ((array)$productOffer['attributes'] as $attr) {
                                    if ($attr['name'] == $optionsList['option_' . $row['ID']]['attr']) {
                                        $offerProperties[$row['ID']] = $attr['value'];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($offerProperties) {
                $arOfferFields['PROPERTY_VALUES'] = $offerProperties;
            }

            $offerId = null;
            $date = new \Bitrix\Main\Type\DateTime();
            if ($existOfferMap) { // update
                $offerId = $existOfferMap['PRODUCT_ID'];
                if (strtotime($productOffer['updated_at']) > strtotime($existOfferMap['DATE_MODIFY'])) {
                    $obElement->Update($existOfferMap['PRODUCT_ID'], $arOfferFields);
                    $DB->Update("b_oasis_importer_products", [
                        'DATE_MODIFY' => "'" . $date->format('Y-m-d H:i:s') . "'",
                    ], "WHERE PRODUCT_ID=" . $offerId . "", '', false);
                }
            } else { // insert
                $offerId = $obElement->Add($arOfferFields, false, false, true);

                if ($offerId) {
                    $DB->Insert("b_oasis_importer_products", [
                        'IBLOCK_ID'        => $offerIblock['IBLOCK_ID'],
                        'SECTION_ID'       => $category['SECTION_ID'],
                        'PRODUCT_ID'       => $offerId,
                        'OASIS_PRODUCT_ID' => "'" . $productOffer['id'] . "'",
                        'DATE_CREATE'      => "'" . $date->format('Y-m-d H:i:s') . "'",
                        'DATE_MODIFY'      => "'" . $date->format('Y-m-d H:i:s') . "'",
                    ], '', false);
                } else {
                    throw new SystemException($obElement->LAST_ERROR);
                }
            }

            $priceFactor = Bootstrap::getPriceFactor();

            $offerPrice = $productOffer['price'] * $priceFactor;

            $stock = 0;
            $warehouse = Bootstrap::getWarehouse();
            switch ($warehouse) {
                case 'moscow':
                    if (isset($productOffer['outlets']['000000029'])) {
                        $stock = $productOffer['outlets']['000000029'];
                    }

                    break;
                case 'remote':
                    if (isset($productOffer['outlets']['1-0000052'])) {
                        $stock = $productOffer['outlets']['1-0000052'];
                    }
                    break;
                default:
                    foreach ((array)$productOffer['outlets'] as $warehouseId => $outlet) {
                        $stock += $outlet;
                    }
                    break;
            }

            CCatalogStoreProduct::UpdateFromForm([
                "PRODUCT_ID" => $offerId,
                "STORE_ID"   => 1,
                "AMOUNT"     => $stock,
            ]);

            $weight = $width = $length = $height = 0;
            foreach ((array)$productOffer['package'] as $package) {
                if ($package['is_main']) {
                    $weight = (int)$package['weight'];

                    $sizeArr = explode("x", $package['size']);
                    if (count($sizeArr) == 3) {
                        $width = (int)$sizeArr[0];
                        $length = (int)$sizeArr[1];
                        $height = (int)$sizeArr[2];
                    }
                }
            }

            CCatalogProduct::Add(
                [
                    'ID'           => $offerId,
                    'VAT_INCLUDED' => 'Y',
                    'VAT_ID'       => 1,
                    'QUANTITY'     => $stock,
                    'WEIGHT'       => $weight,
                    'WIDTH'        => $width,
                    'LENGTH'       => $length,
                    'HEIGHT'       => $height,
                ]
            );

            CPrice::Add(
                [
                    "CURRENCY"         => "RUB",
                    "PRICE"            => $offerPrice,
                    "CATALOG_GROUP_ID" => 1,
                    "PRODUCT_ID"       => $offerId,
                ]
            );

        }

        return $productId;
    }
}
