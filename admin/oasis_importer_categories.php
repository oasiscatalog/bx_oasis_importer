<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

$module_id = 'oasis_importer';

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\OasisImporter\Api;
use Bitrix\OasisImporter\Bootstrap;
use Bitrix\OasisImporter\Categories;

Loc::loadMessages(__FILE__);

Loader::IncludeModule($module_id);

if ($APPLICATION->GetGroupRight($module_id) < 'R') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

CModule::IncludeModule($module_id);
$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);

$apiKey = Bootstrap::getApiKey();

$rubrics = ['tree' => [], 'plain' => []];
if ($apiKey) {
    $api = new Api($apiKey);

    $data = $api->getCategories();
    foreach ($data as $row) {
        $rubrics['tree'][(int)$row['parent_id']][$row['id']] = $row['name'];
        $rubrics['plain'][$row['id']] = $row['name'];
    }
}

if (strlen($_POST['Update']) > 0 && check_bitrix_sessid() && $MOD_RIGHT >= 'W') {
    if (!empty($_POST['SECTION_ID']) && !empty($_POST['CATEGORY_ID'])) {
        Categories::save($_POST['SECTION_ID'], $_POST['CATEGORY_ID']);
        LocalRedirect("oasis_importer_categories.php");
    }
}

if ($_GET['action'] == 'delete' && $MOD_RIGHT >= 'W' && !empty($_GET['id'])) {
    Categories::delete($_GET['id']);
    LocalRedirect("oasis_importer_categories.php");
}

$catalog = Categories::getBxCatalog();
$catalogMapping = Categories::getList();

function recursiveOptions($source, $parent_id = 0, $level = 0, $excluded = [])
{
    $out = '';
    if (isset($source[$parent_id])) {
        foreach ($source[$parent_id] as $id => $name) {
            $out .= '<option value="' . $id . '"' .
                (in_array($id, $excluded) ? ' disabled' : '') . '>' .
                str_repeat('&nbsp;', $level * 4) . $name . ' 
                ' . (in_array($id, $excluded) ? ' &#10004;' : '') . '
                </option>';
            $out .= recursiveOptions($source, $id, $level + 1, $excluded);
        }
    }
    return $out;
}

// VIEW

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

if (!$apiKey) {
    CAdminMessage::ShowMessage(Loc::getMessage('OASIS_IMPORTER_NOT_FOUND_KEY'));
}

$APPLICATION->SetTitle(Loc::getMessage('OASIS_IMPORTER_CATEGORY_TITLE'));
?>


    <table class="adm-list-table" id="tbl_yandex_direct_campaign">
        <thead>
        <tr class="adm-list-table-header">
            <td class="adm-list-table-cell">
                <div class="adm-list-table-cell-inner"><?= Loc::getMessage('OASIS_IMPORTER_TABLE_CATALOG'); ?></div>
            </td>
            <td class="adm-list-table-cell">
                <div class="adm-list-table-cell-inner"><?= Loc::getMessage('OASIS_IMPORTER_TABLE_RUBRIC'); ?></div>
            </td>
            <td class="adm-list-table-cell">
                <div class="adm-list-table-cell-inner"></div>
            </td>
        </tr>
        </thead>
        <tbody>
        <?php if ($catalogMapping) : ?>
            <?php foreach ($catalogMapping as $sectionId => $sectionData) : ?>
                <?php if (isset($catalog['plain'][$sectionId]) && isset($rubrics['plain'][$sectionData['CATEGORY_ID']])) : ?>
                    <tr>
                        <td class="adm-list-table-cell">
                            <?= $catalog['plain'][$sectionId]; ?> (#<?= $sectionId; ?>)
                        </td>
                        <td class="adm-list-table-cell">
                            <?= $rubrics['plain'][$sectionData['CATEGORY_ID']]; ?> (#<?= $sectionData['CATEGORY_ID']; ?>
                            )
                        </td>
                        <td class="adm-list-table-cell">
                            <a href="<?= $APPLICATION->GetCurPage() ?>?lang=<? echo LANGUAGE_ID ?>&action=delete&id=<?= $sectionId; ?>">
                                <?= Loc::getMessage('OASIS_IMPORTER_TABLE_DELETE'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="3" class="adm-list-table-cell adm-list-table-empty">
                    <?= Loc::getMessage('OASIS_IMPORTER_TABLE_EMPTY'); ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    <br/>
    <br/>
<?php

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV'   => 'edit_main',
        'TAB'   => Loc::getMessage('OASIS_IMPORTER_FORM_TAB'),
        'ICON'  => 'ib_settings',
        'TITLE' => Loc::getMessage('OASIS_IMPORTER_FORM_TITLE'),
    ],
]);

$tabControl->Begin();
?>

    <form method="post"
          action="<?= $APPLICATION->GetCurPage() ?>?lang=<? echo LANGUAGE_ID ?>">
        <?= bitrix_sessid_post() ?>

        <? $tabControl->BeginNextTab() ?>

        <tr>
            <td width="50%">
                <select name="SECTION_ID" required style="width: 100%">
                    <option value="0"><?= Loc::getMessage('OASIS_IMPORTER_FORM_SELECT_CATALOG'); ?></option>
                    <?php foreach ($catalog['tree'] as $iblockName => $tree) : ?>
                        <optgroup label="<?= $iblockName; ?>">
                            <?= recursiveOptions($tree, 0, 0, array_keys($catalogMapping)); ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </td>
            <td width="50%">
                <select name="CATEGORY_ID" required style="width: 100%">
                    <option value="0"><?= Loc::getMessage('OASIS_IMPORTER_FORM_SELECT_RUBRIC'); ?></option>
                    <?= recursiveOptions($rubrics['tree'], 0, 0, array_map(function ($v) {
                        return $v['CATEGORY_ID'];
                    }, $catalogMapping)); ?>
                </select>
            </td>
        </tr>

        <? $tabControl->Buttons() ?>

        <input type="submit" name="Update" <? if ($MOD_RIGHT < 'W') {
            echo 'disabled';
        } ?> value="<?= GetMessage("MAIN_SAVE") ?>" title="<?= GetMessage("MAIN_OPT_SAVE_TITLE") ?>"
               class="adm-btn-save">

        <? $tabControl->End() ?>
    </form>
<?

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
