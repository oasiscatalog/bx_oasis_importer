<?php

namespace Bitrix\OasisImporter;

use CIBlock;
use CIBlockSection;
use CModule;

/**
 * Class Categories
 * @package Bitrix\OasisImporter
 */
class Categories
{
    /**
     * @return array
     */
    public static function getList()
    {
        global $DB;

        $result = [];
        $query = $DB->Query("SELECT * FROM b_oasis_importer_categories", false);
        while ($row = $query->Fetch()) {
            $result[$row['SECTION_ID']] = $row;
        }
        return $result;
    }

    /**
     * @return array
     */
    public static function getBxCatalog()
    {
        CModule::IncludeModule("catalog");
        CModule::IncludeModule("iblock");

        $catalog = ['plain' => [], 'tree' => []];
        $res = CIBlock::GetList([], ['TYPE' => 'catalog', 'ACTIVE' => 'Y']);
        while ($ar_res = $res->Fetch()) {
            $tree = CIBlockSection::GetTreeList(['IBLOCK_ID' => $ar_res['ID']]);
            while ($section = $tree->GetNext()) {
                $catalog['tree'][$ar_res['NAME']][(int)$section['IBLOCK_SECTION_ID']][$section['ID']] = $section['NAME'];
                $catalog['plain'][$section['ID']] = $section['NAME'];
            }
        }
        return $catalog;
    }

    /**
     * @param $sectionId
     * @param $categoryId
     * @return bool|string
     * @throws \Bitrix\Main\ObjectException
     */
    public static function save($sectionId, $categoryId)
    {
        global $DB;
        CModule::IncludeModule("catalog");
        CModule::IncludeModule("iblock");

        $result = false;
        $res = CIBlockSection::GetByID($sectionId);
        if ($ar_res = $res->GetNext()) {
            $date = new \Bitrix\Main\Type\DateTime();
            $result = $DB->Insert("b_oasis_importer_categories", [
                'IBLOCK_ID'   => $ar_res['IBLOCK_ID'],
                'SECTION_ID'  => $sectionId,
                'CATEGORY_ID' => $categoryId,
                'DATE_CREATE' => "'" . $date->format('Y-m-d H:i:s') . "'",
                'DATE_MODIFY' => "'" . $date->format('Y-m-d H:i:s') . "'",

            ]);
        }

        return $result;
    }

    /**
     * @param $sectionId
     * @return array|bool
     */
    public static function delete($sectionId)
    {
        global $DB;
        if (!$sectionId) {
            return false;
        }
        return $DB->Query("DELETE FROM b_oasis_importer_categories WHERE SECTION_ID = '" . (int)$sectionId . "'")->Fetch();
    }
}
