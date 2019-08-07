<?php
$module_id = 'oasis_importer';

use \Bitrix\Main\Localization\Loc;
use Bitrix\OasisImporter\Bootstrap;
use Bitrix\Main\Loader;

if (!\Bitrix\Main\Loader::includeModule('oasis_importer')) {
    return;
}

// vars
$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();

// lang
IncludeModuleLangFile($docRoot . '/bitrix/modules/main/options.php');
Loc::loadMessages(__FILE__);


CModule::IncludeModule($module_id);
$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);

if (strlen($_POST['Update']) > 0 && check_bitrix_sessid() && $MOD_RIGHT >= 'W') {
    if (isset($_POST['api_key'])) {
        Bootstrap::setApiKey($_POST['api_key']);
    }
    if (isset($_POST['price_factor'])) {
        Bootstrap::setPriceFactor($_POST['price_factor']);
    }

    if (isset($_POST['warehouseId'])) {
        Bootstrap::setWarehouse($_POST['warehouseId']);
    }
}

$apiKey = Bootstrap::getApiKey();
$priceFactor = Bootstrap::getPriceFactor();
$warehouse = Bootstrap::getWarehouse();

// VIEW

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV'   => 'edit_main',
        'TAB'   => Loc::getMessage('MAIN_TAB_SET'),
        'ICON'  => 'ib_settings',
        'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_SET'),
    ],
]);

$tabControl->Begin();

?>
<form method="post"
      action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($mid) ?>&amp;lang=<? echo LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>

    <? $tabControl->BeginNextTab() ?>

    <tr>
        <td width="40%">
            <nobr><?= GetMessage("OASIS_IMPORTER_API_KEY") ?></nobr>
            :
        </td>
        <td width="60%"><input id="api_key" type="text" size="40" value="<?= $apiKey; ?>" name="api_key"></td>
    </tr>


    <tr>
        <td width="40%">
            <nobr><?= GetMessage("OASIS_IMPORTER_PRICE_FACTOR") ?></nobr>
            :
        </td>
        <td width="60%"><input id="price_factor" type="number" step="0.01" size="40" value="<?= $priceFactor; ?>"
                               name="price_factor" style="background:#fff; border:1px solid; border-color:#87919c #959ea9 #9ea7b1 #959ea9; border-radius:4px; color:#000; -webkit-box-shadow:0 1px 0 0 rgba(255,255,255,0.3), inset 0 2px 2px -1px rgba(180,188,191,0.7); box-shadow:0 1px 0 0 rgba(255,255,255,0.3), inset 0 2px 2px -1px rgba(180,188,191,0.7); display:inline-block; outline:none; vertical-align:middle; -webkit-font-smoothing: antialiased; font-size: 13px; height: 25px; padding: 0 5px; margin: 0;"></td>
    </tr>

    <tr>
        <td width="40%">
            <nobr><?= GetMessage("OASIS_IMPORTER_WAREHOUSE") ?></nobr>
            :
        </td>
        <td width="60%">
            <select name="warehouse">
                <option value="all" <?= (empty($warehouse) || (!empty($warehouse) && $warehouse == 'all') ? 'selected' : ''); ?>><?= GetMessage("OASIS_IMPORTER_WAREHOUSE_ALL") ?></option>
                <option value="moscow" <?= (empty($warehouse) && $warehouse == 'moscow' ? 'selected' : ''); ?>><?= GetMessage("OASIS_IMPORTER_WAREHOUSE_MSK") ?></option>
                <option value="remote" <?= (empty($warehouse) && $warehouse == 'remote' ? 'selected' : ''); ?>><?= GetMessage("OASIS_IMPORTER_WAREHOUSE_REMOTE") ?></option>
            </select>
        </td>
    </tr>

    <? $tabControl->Buttons() ?>

    <input type="submit" name="Update" <? if ($MOD_RIGHT < 'W') {
        echo 'disabled';
    } ?> value="<?= GetMessage("MAIN_SAVE") ?>" title="<?= GetMessage("MAIN_OPT_SAVE_TITLE") ?>" class="adm-btn-save">

    <? $tabControl->End() ?>
</form>
