<?php

namespace Bitrix\OasisImporter;

use COption;

/**
 * Class Bootstrap
 * @package Bitrix\OasisImporter
 */
class Bootstrap
{
    private static $moduleId = 'oasis_importer';

    /**
     * @return bool|string|null
     */
    public static function getApiKey()
    {
        return COption::GetOptionString(self::$moduleId, "API_KEY", "");
    }

    /**
     * @param $apiKey
     */
    public static function setApiKey($apiKey)
    {
        COption::SetOptionString(self::$moduleId, "API_KEY", $apiKey);
    }

    /**
     * @return bool|string|null
     */
    public static function getPriceFactor()
    {
        return COption::GetOptionString(self::$moduleId, "PRICE_FACTOR", 1);
    }

    /**
     * @param $priceFactor
     */
    public static function setPriceFactor($priceFactor)
    {
        COption::SetOptionString(self::$moduleId, "PRICE_FACTOR", $priceFactor);
    }

    /**
     * @return bool|string|null
     */
    public static function getWarehouse()
    {
        return COption::GetOptionString(self::$moduleId, "WAREHOUSE", "");
    }

    /**
     * @param $warehouse
     */
    public static function setWarehouse($warehouse)
    {
        COption::SetOptionString(self::$moduleId, "WAREHOUSE", $warehouse);
    }
}
