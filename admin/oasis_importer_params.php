<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

$module_id = 'oasis_importer';

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\OasisImporter\Api;
use Bitrix\OasisImporter\Bootstrap;
use Bitrix\OasisImporter\Categories;
use Bitrix\OasisImporter\Params;
use Bitrix\OasisImporter\Products;

Loc::loadMessages(__FILE__);

Loader::IncludeModule($module_id);

if ($APPLICATION->GetGroupRight($module_id) < 'R') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

CModule::IncludeModule($module_id);
$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);

$apiKey = Bootstrap::getApiKey();

if (strlen($_POST['Update']) > 0 && check_bitrix_sessid() && $MOD_RIGHT >= 'W') {
    if (!empty($_POST['OPTION_ID'])) {
        Params::save($_POST['OPTION_ID']);

        LocalRedirect("oasis_importer_params.php");
    }
}

$catalogMapping = Categories::getList();

$paramsMapping = Params::getList();

// VIEW

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

if (!$apiKey) {
    CAdminMessage::ShowMessage(Loc::getMessage('OASIS_IMPORTER_NOT_FOUND_KEY'));
}

if (!$catalogMapping) {
    CAdminMessage::ShowMessage(Loc::getMessage('OASIS_IMPORTER_NOT_FOUND_CATALOG_MAPPING'));
}

$APPLICATION->SetTitle(Loc::getMessage('OASIS_IMPORTER_PARAMS_TITLE'));

if ($catalogMapping) {
    $iblockIds = [];
    $rubricsByIblock = [];
    $optionsByIblockId = [];
    foreach ($catalogMapping as $row) {
        $iblockIds[$row['IBLOCK_ID']] = $row['IBLOCK_ID'];
        $rubricsByIblock[$row['IBLOCK_ID']][] = $row['CATEGORY_ID'];
    }

    $tabs = [];
    $res = CIBlock::GetList(
        ['id' => 'ASC'],
        [
            'TYPE'   => 'catalog',
            'ACTIVE' => 'Y',
            "ID"     => $iblockIds,
        ]
    );
    $offerParentIblock = [];
    while ($ar_res = $res->Fetch()) {
        $tabs[] = [
            'DIV'   => 'edit_iblock_' . $ar_res['ID'],
            'TAB'   => Loc::getMessage('OASIS_IMPORTER_PARAMS_FOR') . ' "' . $ar_res['NAME'] . '"',
            'ICON'  => 'ib_settings',
            'TITLE' => Loc::getMessage('OASIS_IMPORTER_PARAMS_FOR') . ' "' . $ar_res['NAME'] . '"',
            'ID'    => $ar_res['ID'],
        ];

        $properties = CIBlockProperty::GetList(["name" => "asc"], ["ACTIVE" => "Y", "IBLOCK_ID" => $ar_res['ID']]);
        while ($row = $properties->Fetch()) {
            $optionsByIblockId[$ar_res['ID']][] = $row;
        }

        $offerIblock = CCatalog::GetList([], ['IBLOCK_TYPE_ID' => 'offers', '=%CODE' => $ar_res['CODE']]);

        while ($offerBlock = $offerIblock->Fetch()) {
            $offerParentIblock[$offerBlock['ID']] = $ar_res['ID'];
            $tabs[] = [
                'DIV'   => 'edit_iblock_' . $offerBlock['ID'],
                'TAB'   => Loc::getMessage('OASIS_IMPORTER_PARAMS_FOR') . ' "' . $offerBlock['NAME'] . '"',
                'ICON'  => 'ib_settings',
                'TITLE' => Loc::getMessage('OASIS_IMPORTER_PARAMS_FOR') . ' "' . $offerBlock['NAME'] . '"',
                'ID'    => $offerBlock['ID'],
            ];

            $properties = CIBlockProperty::GetList(["name" => "asc"],
                ["ACTIVE" => "Y", "IBLOCK_ID" => $offerBlock['ID']]);
            while ($row = $properties->Fetch()) {
                $optionsByIblockId[$offerBlock['ID']][] = $row;
            }
        }
    }

    $oasisAttributes = [];
    if ($apiKey) {
        $api = new Api($apiKey);

        foreach ($rubricsByIblock as $iblockId => $categories) {
            $data = $api->getProductByCategory($categories, true);

            foreach ($data as $row) {
                foreach ($row as $k => $v) {
                    if ($k == 'attributes') {
                        foreach ($v as $subV) {
                            $oasisAttributes[$iblockId][$subV['name']] = $k;
                        }
                    } else {
                        $oasisAttributes[$iblockId][$k] = $k;
                    }
                }
            }

            ksort($oasisAttributes[$iblockId]);
        }
    }

    $tabControl = new CAdminTabControl('tabControl', $tabs);

    $tabControl->Begin();
    ?>

    <form method="post"
          action="<?= $APPLICATION->GetCurPage() ?>?lang=<? echo LANGUAGE_ID ?>">
        <?= bitrix_sessid_post() ?>

        <?php foreach ($tabs as $tab) : ?>
            <? $tabControl->BeginNextTab() ?>

            <?php foreach ($optionsByIblockId[$tab['ID']] as $option) : ?>
                <tr>
                    <td width="40%">
                        <?= $option['NAME']; ?>:
                    </td>
                    <td width="60%">
                        <select name="OPTION_ID[<?= $option['ID']; ?>]" style="width: 100%">
                            <option value="0"><?= Loc::getMessage('OASIS_IMPORTER_SELECT_PARAM'); ?></option>
                            <?php
                            $attrs = isset($oasisAttributes[$tab['ID']]) ? $oasisAttributes[$tab['ID']] : (isset($oasisAttributes[$offerParentIblock[$tab['ID']]]) ? $oasisAttributes[$offerParentIblock[$tab['ID']]] : []);

                            foreach ($attrs as $attrKey => $attrValue) : ?>
                                <option value="<?= $attrKey; ?>" <?= (!empty($paramsMapping['option_' . $option['ID']]) && !empty($paramsMapping['option_' . $option['ID']]['attr']) && $paramsMapping['option_' . $option['ID']]['attr'] == $attrKey ? 'selected' : ''); ?>><?= $attrKey; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>

        <?php endforeach; ?>

        <? $tabControl->Buttons() ?>

        <input type="submit" name="Update" <? if ($MOD_RIGHT < 'W') {
            echo 'disabled';
        } ?> value="<?= GetMessage("MAIN_SAVE") ?>" title="<?= GetMessage("MAIN_OPT_SAVE_TITLE") ?>"
               class="adm-btn-save">

        <? $tabControl->End() ?>
    </form>
    <?
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
