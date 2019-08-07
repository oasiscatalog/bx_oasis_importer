<?php

namespace Bitrix\OasisImporter;

/**
 * Class Params
 * @package Bitrix\OasisImporter
 */
class Params
{
    /**
     * @return array
     */
    public static function getList()
    {
        global $DB;

        $result = [];
        $query = $DB->Query("SELECT * FROM b_oasis_importer_params", false);
        while ($row = $query->Fetch()) {
            $result[$row['NAME']] = json_decode($row['PARAMS'], true, 512, JSON_UNESCAPED_UNICODE);
        }
        return $result;
    }

    /**
     * @param $options
     * @throws \Bitrix\Main\ObjectException
     */
    public static function save($options)
    {
        global $DB, $USER;

        $existParams = self::getList();

        foreach ($options as $optionId => $attr) {
            if ($attr) {
                $date = new \Bitrix\Main\Type\DateTime();
                if (isset($existParams['option_' . $optionId])) {
                    $res = $DB->Update("b_oasis_importer_params", [
                        'PARAMS'         => "'" . json_encode(['attr' => $attr], JSON_UNESCAPED_UNICODE) . "'",
                        'MODIFIED_BY_ID' => $USER->GetID(),
                        'DATE_MODIFY'    => "'" . $date->format('Y-m-d H:i:s') . "'",
                    ], "WHERE NAME='" . 'option_' . $optionId . "'", '', false);
                } else {
                    $DB->Insert("b_oasis_importer_params", [
                        'NAME'          => "'" . 'option_' . $optionId . "'",
                        'PARAMS'        => "'" . json_encode(['attr' => $attr], JSON_UNESCAPED_UNICODE) . "'",
                        'CREATED_BY_ID' => $USER->GetID(),
                        'DATE_CREATE'   => "'" . $date->format('Y-m-d H:i:s') . "'",
                        'DATE_MODIFY'   => "'" . $date->format('Y-m-d H:i:s') . "'",
                    ], '', false);
                }
            }
        }
    }
}
