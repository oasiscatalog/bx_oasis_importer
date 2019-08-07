<?php
IncludeModuleLangFile(__FILE__);
$aMenu = [];

if ($APPLICATION->getGroupRight('oasis_importer') >= 'R') {
    $aMenu[] = [
        'parent_menu' => 'global_menu_services',
        'section'     => 'oasis_importer',
        'sort'        => 3000,
        'text'        => GetMessage('OASIS_IMPORTER_MENU_TEXT'),
        'title'       => GetMessage('OASIS_IMPORTER_MENU_TITLE'),
        'url'         => 'oasis_importer_admin.php?lang=' . LANG,
        'icon'        => 'update_menu_icon',
        'page_icon'   => 'update_menu_icon',
        'items_id'    => 'menu_oasis_importer',
        'more_url'    => [],
        'items'       => [
            [
                'text'        => GetMessage('OASIS_IMPORTER_MENU_CATEGORY_TEXT'),
                'title'       => GetMessage('OASIS_IMPORTER_MENU_CATEGORY_TITLE'),
                'url'         => 'oasis_importer_categories.php?lang=' . LANG,
            ],
            [
                'text'        => GetMessage('OASIS_IMPORTER_MENU_PARAMS_TEXT'),
                'title'       => GetMessage('OASIS_IMPORTER_MENU_PARAMS_TITLE'),
                'url'         => 'oasis_importer_params.php?lang=' . LANG,
            ]
        ],
    ];
}

return !empty($aMenu) ? $aMenu : false;
