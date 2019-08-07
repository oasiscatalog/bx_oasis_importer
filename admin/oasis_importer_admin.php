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

$catalogMapping = Categories::getList();

$totalProductBySectionId = [];
$rubrics = ['tree' => [], 'plain' => []];
if ($apiKey) {
    $api = new Api($apiKey);

    $data = $api->getCategories();
    foreach ($data as $row) {
        $rubrics['tree'][(int)$row['parent_id']][$row['id']] = $row['name'];
        $rubrics['plain'][$row['id']] = $row['name'];
    }

    foreach ($catalogMapping as $sectionId => $sectionData) {
        $res = $api->getProductCountByCategory($sectionData['CATEGORY_ID']);
        $totalProductBySectionId[$sectionId][] = $res['products'];
    }
}

// VIEW

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

if (!$apiKey) {
    CAdminMessage::ShowMessage(Loc::getMessage('OASIS_IMPORTER_NOT_FOUND_KEY'));
}

$APPLICATION->SetTitle(Loc::getMessage('OASIS_IMPORTER_ADMIN_TITLE'));

if ($_GET['action'] == 'import' && $MOD_RIGHT >= 'W' && !empty($_GET['id'])) {
    $products = [];

    $step = isset($_GET['step']) ? (int)$_GET['step'] : 0;

    if ($apiKey) {
        $api = new Api($apiKey);

        if (!empty($_SESSION['oasis_import_tmp_' . $_GET['id']])) {
            $filename = $_SESSION['oasis_import_tmp_' . $_GET['id']];
            if (file_exists($filename) && filemtime($filename) > time() - 7200) {
                $products = json_decode(file_get_contents($_SESSION['oasis_import_tmp_' . $_GET['id']]), true);
            } else {
                @unlink($filename);
            }
        }

        if (!$products) {
            $productsRaw = $api->getProductByCategory([$catalogMapping[$_GET['id']]['CATEGORY_ID']], true);

            $products = [];
            foreach ($productsRaw as $product) {
                $products[$product['group_id']][] = $product;
            }

            $tmpFilename = tempnam(sys_get_temp_dir(), $_GET['id']);

            file_put_contents($tmpFilename, json_encode($products));
            $_SESSION['oasis_import_tmp_' . $_GET['id']] = $tmpFilename;
        }

    }

    if (isset($_GET['step'])) {
        $APPLICATION->RestartBuffer();
    }

    if ($products) {
        $products = array_values($products);

        if (isset($products[$step])) {
            $res = Products::upsertProduct($catalogMapping[$_GET['id']], $products[$step]);
        }

        $progress = 0;
        $percent = ($step / count($products)) * 100;

        echo '<div class="progress_counter">';
        CAdminMessage::ShowMessage([
            "MESSAGE"        => Loc::getMessage('OASIS_IMPORTER_IMPORT_PRODUCTS_PROGRESS'),
            "DETAILS"        => "#PROGRESS_BAR#",
            "HTML"           => true,
            "TYPE"           => "PROGRESS",
            "PROGRESS_TOTAL" => 100,
            "PROGRESS_VALUE" => $percent,
        ]);
        echo '</div>';

        if ($step == (count($products[$step]) - 1)) {
            @unlink($_SESSION['oasis_import_tmp_' . $_GET['id']]);
            unset($_SESSION['oasis_import_tmp_' . $_GET['id']]);
        }

        if (isset($_GET['step'])) {
            die();
        }
        ?>
        <script>
            var allSteps = <?=count($products);?>;

            function progressRequest(step) {
                BX.ajax({
                    url: '<?= $APPLICATION->GetCurPage() ?>?lang=<? echo LANGUAGE_ID;?>&action=import&id=<?=$_GET['id'];?>&step=' + step,
                    method: 'get',
                    dataType: 'html',
                    async: true,
                    processData: false,
                    emulateOnload: false,
                    start: true,
                    data: {},
                    //cache: true,
                    onsuccess: function (result) {
                        document.getElementsByClassName("progress_counter")[0].innerHTML = result;
                        if (step < allSteps) {
                            progressRequest((step + 1));
                        }
                    },
                    onfailure: function (type, e) {
                        // on error do nothing
                    }
                });
            }

            progressRequest(<?=($step + 1);?>);

        </script>
        <?php
    }
}

$catalog = Categories::getBxCatalog();
$productMapping = Products::getCountBySectionId();
?>

    <table class="adm-list-table" id="tbl_yandex_direct_campaign">
        <thead>
        <tr class="adm-list-table-header">
            <td class="adm-list-table-cell">
                <div class="adm-list-table-cell-inner"><?= Loc::getMessage('OASIS_IMPORTER_IMPORT_TABLE_CATALOG'); ?></div>
            </td>
            <td class="adm-list-table-cell">
                <div class="adm-list-table-cell-inner"><?= Loc::getMessage('OASIS_IMPORTER_IMPORT_TABLE_RUBRIC'); ?></div>
            </td>
            <td class="adm-list-table-cell">
                <div class="adm-list-table-cell-inner"><?= Loc::getMessage('OASIS_IMPORTER_IMPORT_TABLE_QUANTITY'); ?></div>
            </td>
            <td class="adm-list-table-cell">
                <div class="adm-list-table-cell-inner"><?= Loc::getMessage('OASIS_IMPORTER_IMPORT_TABLE_DATE_MODIFY'); ?></div>
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
                            <?php if (isset($productMapping[$sectionId])) : ?>
                                Bitrix: <?= $productMapping[$sectionId]['CNT']; ?>
                            <?php else: ?>
                                Bitrix: 0
                            <?php endif; ?>
                            /
                            <?php if (isset($totalProductBySectionId[$sectionId])) : ?>
                                API: <?= array_sum($totalProductBySectionId[$sectionId]); ?>
                            <?php else: ?>
                                API: 0
                            <?php endif; ?>
                        </td>
                        <td class="adm-list-table-cell">
                            <?php if (isset($productMapping[$sectionId])) : ?>
                                <?= date('H:i d.m.Y', strtotime($productMapping[$sectionId]['DATE_MODIFY'])); ?>
                            <?php else: ?>
                                <?= Loc::getMessage('OASIS_IMPORTER_IMPORT_TABLE_DATE_MODIFY_NO'); ?>
                            <?php endif; ?>
                        </td>
                        <td class="adm-list-table-cell">
                            <a href="<?= $APPLICATION->GetCurPage() ?>?lang=<? echo LANGUAGE_ID ?>&action=import&id=<?= $sectionId; ?>">
                                <?= Loc::getMessage('OASIS_IMPORTER_IMPORT_TABLE_START_IMPORT'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="adm-list-table-cell adm-list-table-empty">
                    <?= Loc::getMessage('OASIS_IMPORTER_IMPORT_TABLE_EMPTY'); ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
