<?php

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (class_exists('Oasis_importer')) {
    return;
}

/**
 * Class Oasis_importer
 */
class Oasis_importer extends \CModule
{

    public $MODULE_ID = 'oasis_importer';
    public $MODULE_GROUP_RIGHTS = 'Y';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public $agentInterval = 120;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $arModuleVersion = [];

        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $server = $context->getServer();
        $this->docRoot = $server->getDocumentRoot();

        $path = str_replace('\\', '/', __FILE__);
        $path = substr($path, 0, strlen($path) - strlen('/index.php'));
        include($path . '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('OASIS_IMPORTER_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('OASIS_IMPORTER_MODULE_DESCRIPTION');
    }


    /**
     * Call all install methods.
     * @returm void
     */
    public function doInstall()
    {
        $this->installFiles();
        $this->installDB();

        \CAgent::AddAgent( "COasisModule::agent();", $this->MODULE_ID, "Y", $this->agentInterval, "", "Y");
    }

    /**
     * Call all uninstall methods, include several steps.
     * @returm void
     */
    public function doUninstall()
    {
        $this->uninstallDB();
        $this->uninstallFiles();

        \CAgent::RemoveModuleAgents($this->MODULE_ID);

        COption::RemoveOption($this->MODULE_ID, "API_KEY");
        COption::RemoveOption($this->MODULE_ID, "PRICE_FACTOR");
        COption::RemoveOption($this->MODULE_ID, "WAREHOUSE");
    }

    /**
     * Install files.
     * @return boolean
     */
    public function installFiles()
    {
        copyDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin',
            true, true
        );

        return true;
    }

    /**
     * Install DB, events, etc.
     * @return boolean
     */
    public function installDB()
    {
        global $DB, $APPLICATION;

        // db
        $errors = $DB->runSQLBatch(
            $this->docRoot . '/bitrix/modules/oasis_importer/install/db/' .
            strtolower($DB->type) . '/install.sql'
        );
        if ($errors !== false) {
            $APPLICATION->throwException(implode('', $errors));
            return false;
        }

        // module
        registerModule($this->MODULE_ID);

        return true;
    }

    /**
     * Uninstall DB, events, etc.
     * @param array $arParams Some params.
     * @return boolean
     */
    public function uninstallDB()
    {
        global $APPLICATION, $DB;

        $errors = $DB->runSQLBatch(
            $this->docRoot . '/bitrix/modules/oasis_importer/install/db/' .
            strtolower($DB->type) . '/uninstall.sql'
        );
        if ($errors !== false) {
            $APPLICATION->throwException(implode('', $errors));
            return false;
        }

        // module
        unregisterModule($this->MODULE_ID);

        return true;
    }

    /**
     * Uninstall files.
     * @return boolean
     */
    public function uninstallFiles()
    {
        deleteDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin'
        );

        return true;
    }
}
