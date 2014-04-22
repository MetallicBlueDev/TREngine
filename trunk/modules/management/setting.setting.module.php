<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../../engine/core/secure.class.php");
    new Core_Secure();
}

class Module_Management_Setting extends Libs_ModuleModel {

    public function setting() {
        $localView = Core_Request::getWord("localView", "", "POST");

        switch ($localView) {
            case "sendGeneral":
                $this->sendGeneral();
                break;
            case "sendSystem":
                $this->sendSystem();
                break;
            default:
                return $this->tabHome();
        }
        return "";
    }

    private function tabHome() {
        $accountTabs = new Libs_Tabs("settingtab");
        $accountTabs->addTab(SETTING_GENERAL_TAB, $this->tabGeneral());
        $accountTabs->addTab(SETTING_SYSTEM_TAB, $this->tabSystem());
        return $accountTabs->render();
    }

    /**
     * Supprime le cache
     */
    private function deleteCache() {
        Core_CacheBuffer::setSectionName("tmp");
        Core_CacheBuffer::removeCache("configs.php");
    }

    /**
     * Mise à jour des valeurs de la table de configuration
     *
     * @param $values array
     * @param $where array
     */
    private function updateTable($key, $value) {
        Core_Sql::getInstance()->update(
        Core_Table::$CONFIG_TABLE, array(
            "value" => $value), array(
            "name = '" . $key . "'")
        );
    }

    private function tabGeneral() {
        $form = new Libs_Form("management-setting-general");
        $form->setTitle(SETTING_GENERAL_SITE_SETTING_TITLE);
        $form->setDescription(SETTING_GENERAL_SITE_SETTING_DESCRIPTION);
        $form->addSpace();

        $online = (Core_Main::$coreConfig['defaultSiteStatut'] == "open") ? true : false;
        $form->addHtmlInFieldset(SETTING_GENERAL_SITE_SETTING_SITE_STATUT);
        $form->addInputRadio("defaultSiteStatut1", "defaultSiteStatut", SETTING_GENERAL_SITE_SETTING_SITE_ON, $online, "open");
        $form->addInputRadio("defaultSiteStatut2", "defaultSiteStatut", SETTING_GENERAL_SITE_SETTING_SITE_OFF, !$online, "close");
        $form->addSpace();
        $form->addTextarea("defaultSiteCloseReason", SETTING_GENERAL_SITE_SETTING_SITE_OFF_REASON, Core_Main::$coreConfig['defaultSiteCloseReason'], "style=\"display: block;\" cols=\"50\"");
        $form->addSpace();

        $form->addInputText("defaultSiteName", SETTING_GENERAL_DEFAULT_SITE_NAME, Core_Main::$coreConfig['defaultSiteName']);
        $form->addInputText("defaultSiteSlogan", SETTING_GENERAL_DEFAULT_SITE_SLOGAN, Core_Main::$coreConfig['defaultSiteSlogan']);
        $form->addInputText("defaultAdministratorMail", SETTING_GENERAL_DEFAULT_ADMIN_MAIL, Core_Main::$coreConfig['defaultAdministratorMail']);
        $form->addSpace();

        $form->addSelectOpenTag("defaultLanguage", SETTING_GENERAL_DEFAULT_LANGUAGE);
        $langues = Core_Translate::listLanguages();
        $currentLanguage = Core_Main::$coreConfig['defaultLanguage'];
        $form->addSelectItemTag($currentLanguage, "", true);
        foreach ($langues as $langue) {
            if ($langue == $currentLanguage)
                continue;
            $form->addSelectItemTag($langue);
        }
        $form->addSelectCloseTag();

        $form->addSelectOpenTag("defaultTemplate", SETTING_GENERAL_DEFAULT_TEMPLATE);
        $templates = Libs_MakeStyle::listTemplates();
        $currentTemplate = Core_Main::$coreConfig['defaultTemplate'];
        $form->addSelectItemTag($currentTemplate, "", true);
        foreach ($templates as $template) {
            if ($template == $currentTemplate)
                continue;
            $form->addSelectItemTag($template);
        }
        $form->addSelectCloseTag();

        $form->addSelectOpenTag("defaultMod", SETTING_GENERAL_DEFAULT_MODULE);
        $modules = Libs_Module::listModules();
        $currentModule = Core_Main::$coreConfig['defaultMod'];
        $currentModuleName = "";
        foreach ($modules as $module) {
            if ($module['value'] == $currentModule) {
                $currentModuleName = $module['name'];
                continue;
            }
            $form->addSelectItemTag($module['value'], $module['name']);
        }
        $form->addSelectItemTag($currentModule, $currentModuleName, true);
        $form->addSelectCloseTag();
        $form->addSpace();

        $form->addFieldset(SETTING_GENERAL_METADATA_TITLE, SETTING_GENERAL_METADATA_DESCRIPTION);
        $form->addSpace();

        $form->addTextarea("defaultDescription", SETTING_GENERAL_METADATA_DEFAULT_DESCRIPTION, Core_Main::$coreConfig['defaultDescription'], "style=\"display: block;\" cols=\"50\"");
        $form->addSpace();
        $form->addTextarea("defaultKeyWords", SETTING_GENERAL_METADATA_DEFAULT_KEYWORDS, Core_Main::$coreConfig['defaultKeyWords'], "style=\"display: block;\" cols=\"50\"");
        $form->addSpace();

        $rewriting = (Core_Main::$coreConfig['urlRewriting'] == 1) ? true : false;
        $form->addHtmlInFieldset(SETTING_GENERAL_METADATA_URLREWRITING);
        $form->addInputRadio("urlRewriting1", "urlRewriting", SETTING_GENERAL_METADATA_URLREWRITING_ON, $rewriting, "1");
        $form->addInputRadio("urlRewriting2", "urlRewriting", SETTING_GENERAL_METADATA_URLREWRITING_OFF, !$rewriting, "0");
        $form->addSpace();

        $form->addInputHidden("mod", "management");
        $form->addInputHidden("layout", "module");
        $form->addInputHidden("manage", "setting");
        $form->addInputHidden("localView", "sendGeneral");
        $form->addInputSubmit("submit", VALID);
        Core_Html::getInstance()->addJavascript("validGeneralSetting('#form-management-setting-general', '#form-management-setting-general-defaultSiteName-input', '#form-management-setting-general-defaultAdministratorMail-input');");
        return $form->render();
    }

    private function sendGeneral() {
        $deleteCache = false;

        // état du site
        $defaultSiteStatut = Core_Request::getWord("defaultSiteStatut", "", "POST");
        if (Core_Main::$coreConfig['defaultSiteStatut'] != $defaultSiteStatut) {
            $defaultSiteStatut = ($defaultSiteStatut == "close") ? "close" : "open";
            $this->updateTable("defaultSiteStatut", $defaultSiteStatut);
            $deleteCache = true;
        }
        // Raison de femeture
        $defaultSiteCloseReason = Core_Request::getString("defaultSiteCloseReason", "", "POST");
        if (Core_Main::$coreConfig['defaultSiteCloseReason'] != $defaultSiteCloseReason) {
            $this->updateTable("defaultSiteCloseReason", $defaultSiteCloseReason);
            $deleteCache = true;
        }
        // Nom du site
        $defaultSiteName = Core_Request::getString("defaultSiteName", "", "POST");
        if (Core_Main::$coreConfig['defaultSiteName'] != $defaultSiteName) {
            if (!empty($defaultSiteName)) {
                $this->updateTable("defaultSiteName", $defaultSiteName);
                $deleteCache = true;
            } else {
                Core_Logger::addErrorMessage(SETTING_GENERAL_DEFAULT_SITE_NAME_INVALID);
            }
        }
        // Slogan du site
        $defaultSiteSlogan = Core_Request::getString("defaultSiteSlogan", "", "POST");
        if (Core_Main::$coreConfig['defaultSiteSlogan'] != $defaultSiteSlogan) {
            $this->updateTable("defaultSiteSlogan", $defaultSiteSlogan);
            $deleteCache = true;
        }
        // Email du site
        $defaultAdministratorMail = Core_Request::getString("defaultAdministratorMail", "", "POST");
        if (Core_Main::$coreConfig['defaultAdministratorMail'] != $defaultAdministratorMail) {
            if (!empty($defaultAdministratorMail) && Exec_Mailer::validMail($defaultAdministratorMail)) {
                $this->updateTable("defaultAdministratorMail", $defaultAdministratorMail);
                $deleteCache = true;
            } else {
                Core_Logger::addErrorMessage(SETTING_GENERAL_DEFAULT_ADMIN_MAIL_INVALID);
            }
        }
        // Langue par défaut
        $defaultLanguage = Core_Request::getString("defaultLanguage", "", "POST");
        if (Core_Main::$coreConfig['defaultLanguage'] != $defaultLanguage) {
            $langues = Core_Translate::listLanguages();
            if (!empty($defaultLanguage) && Exec_Utils::inArray($defaultLanguage, $langues)) {
                $this->updateTable("defaultLanguage", $defaultLanguage);
                $deleteCache = true;
            } else {
                Core_Logger::addErrorMessage(SETTING_GENERAL_DEFAULT_LANGUAGE_INVALID);
            }
        }
        // Template par défaut
        $defaultTemplate = Core_Request::getString("defaultTemplate", "", "POST");
        if (Core_Main::$coreConfig['defaultTemplate'] != $defaultTemplate) {
            $templates = Libs_MakeStyle::listTemplates();
            if (!empty($defaultTemplate) && Exec_Utils::inArray($defaultTemplate, $templates)) {
                $this->updateTable("defaultTemplate", $defaultTemplate);
                $deleteCache = true;
            } else {
                Core_Logger::addErrorMessage(SETTING_GENERAL_DEFAULT_TEMPLATE_INVALID);
            }
        }
        // Module par défaut
        $defaultMod = Core_Request::getString("defaultMod", "", "POST");
        if (Core_Main::$coreConfig['defaultMod'] != $defaultMod) {
            if (!empty($defaultMod)) {
                $this->updateTable("defaultMod", $defaultMod);
                $deleteCache = true;
            } else {
                Core_Logger::addErrorMessage(SETTING_GENERAL_DEFAULT_MODULE_INVALID);
            }
        }
        // Description du site
        $defaultDescription = Core_Request::getString("defaultDescription", "", "POST");
        if (Core_Main::$coreConfig['defaultDescription'] != $defaultDescription) {
            $this->updateTable("defaultDescription", $defaultDescription);
            $deleteCache = true;
        }
        // Mot clès du site
        $defaultKeyWords = Core_Request::getString("defaultKeyWords", "", "POST");
        if (Core_Main::$coreConfig['defaultKeyWords'] != $defaultKeyWords) {
            $this->updateTable("defaultKeyWords", $defaultKeyWords);
            $deleteCache = true;
        }
        // état de la réécriture des URLs
        $urlRewriting = Core_Request::getString("urlRewriting", "", "POST");
        if (Core_Main::$coreConfig['urlRewriting'] != $urlRewriting) {
            $urlRewriting = ($urlRewriting == 1) ? 1 : 0;
            $this->updateTable("urlRewriting", $urlRewriting);
            $deleteCache = true;
        }

        // Suppression du cache
        if ($deleteCache) {
            $this->deleteCache();
            Core_Logger::addInformationMessage(DATA_SAVED);
        }

        if (Core_Main::isFullScreen()) {
            Core_Html::getInstance()->redirect("index.php?mod=management&manage=setting&selectedTab=settingtabidTab0", 1);
        }
    }

    private function tabSystem() {// TODO a finir de coder
        $form = new Libs_Form("management-setting-system");
        $form->setTitle(SETTING_SYSTEM_CACHE_SETTING_TITLE);
        $form->setDescription(SETTING_SYSTEM_CACHE_SETTING_DESCRIPTION);
        $form->addInputText("cacheTimeLimit", SETTING_SYSTEM_CACHE_SETTING_CACHE_LIMIT, Core_Main::$coreConfig['cacheTimeLimit']);
        $form->addInputText("cryptKey", SETTING_SYSTEM_CACHE_SETTING_CRYPT_KEY, Core_Main::$coreConfig['cryptKey']);
        $form->addSpace();

        $form->addFieldset(SETTING_SYSTEM_SESSION_SETTING_TITLE, SETTING_SYSTEM_SESSION_SETTING_DESCRIPTION);
        $form->addInputText("cookiePrefix", SETTING_SYSTEM_SESSION_SETTING_COOKIE_PREFIX, Core_Main::$coreConfig['cookiePrefix']);
        $form->addSpace();

        // Configuration FTP
        $ftp = Core_Main::getInstance()->getConfigFtp();
        $form->addFieldset(SETTING_SYSTEM_FTP_SETTING_TITLE, SETTING_SYSTEM_FTP_SETTING_DESCRIPTION);

        $form->addSelectOpenTag("ftpType", SETTING_SYSTEM_FTP_SETTING_TYPE);
        $modeFtp = Core_CacheBuffer::getModeActived();
        foreach ($modeFtp as $mode => $actived) {
            $form->addSelectItemTag($mode, "", $actived);
        }
        $form->addSelectCloseTag();

        $form->addInputText("ftpHost", SETTING_SYSTEM_FTP_SETTING_HOST, $ftp['host']);
        $form->addInputText("ftpPort", SETTING_SYSTEM_FTP_SETTING_PORT, $ftp['port']);
        $form->addInputText("ftpUser", SETTING_SYSTEM_FTP_SETTING_USER, $ftp['user']);
        $form->addInputPassword("ftpPass", SETTING_SYSTEM_FTP_SETTING_PASSWORD);
        $form->addInputText("ftpRoot", SETTING_SYSTEM_FTP_SETTING_ROOT, $ftp['root']);
        $form->addSpace();

        $coreSql = Core_Sql::getInstance();
        $form->addFieldset(SETTING_SYSTEM_DATABASE_SETTING_TITLE, SETTING_SYSTEM_DATABASE_SETTING_DESCRIPTION);
        $form->addInputText("dbHost", SETTING_SYSTEM_DATABASE_SETTING_HOST, $coreSql->getDatabaseHost());
        $form->addInputText("dbName", SETTING_SYSTEM_DATABASE_SETTING_NAME, $coreSql->getDatabaseName());
        $form->addInputText("dbPrefix", SETTING_SYSTEM_DATABASE_SETTING_PREFIX, $coreSql->getDatabasePrefix());
        $form->addInputText("dbUser", SETTING_SYSTEM_DATABASE_SETTING_USER, $coreSql->getDatabaseUser());
        $form->addInputPassword("dbPass", SETTING_SYSTEM_DATABASE_SETTING_PASSWORD);

        $form->addSelectOpenTag("dbType", SETTING_SYSTEM_DATABASE_SETTING_TYPE);
        $bases = Core_Sql::listBases();
        foreach ($bases as $base) {
            if ($base == $coreSql->getDatabaseType())
                $form->addSelectItemTag($base, "", true);
            else
                $form->addSelectItemTag($base);
        }
        $form->addSelectCloseTag();

        $form->addSpace();
        $form->addInputHidden("mod", "management");
        $form->addInputHidden("layout", "module");
        $form->addInputHidden("manage", "setting");
        $form->addInputHidden("localView", "sendSystem");
        $form->addInputSubmit("submit", VALID);
        //Core_Html::getInstance()->addJavascript("validSystemSetting('#form-management-setting-system', '#form-management-setting-system-defaultSiteName-input', '#form-management-setting-system-defaultAdministratorMail-input');");
        return $form->render();
    }

    private function sendSystem() {
        $updateConfigFile = false;

        $cacheTimeLimit = Core_Request::getInt("cacheTimeLimit", 7, "POST");
        if ($cacheTimeLimit != Core_Main::$coreConfig['cacheTimeLimit']) {
            if ($cacheTimeLimit >= 1)
                $updateConfigFile = true;
            else
                $cacheTimeLimit = Core_Main::$coreConfig['cacheTimeLimit'];
        }

        $cryptKey = Core_Request::getString("cryptKey", Core_Main::$coreConfig['cryptKey'], "POST");
        if ($cryptKey != Core_Main::$coreConfig['cryptKey']) {
            if (!empty($cryptKey))
                $updateConfigFile = true;
            else
                $cryptKey = Core_Main::$coreConfig['cryptKey'];
        }

        $cookiePrefix = Core_Request::getString("cookiePrefix", Core_Main::$coreConfig['cookiePrefix'], "POST");
        if ($cookiePrefix != Core_Main::$coreConfig['cookiePrefix']) {
            if (!empty($cookiePrefix))
                $updateConfigFile = true;
            else
                $cookiePrefix = Core_Main::$coreConfig['cookiePrefix'];
        }

        if ($updateConfigFile) {
            Exec_FileBuilder::buildConfigFile(Core_Main::$coreConfig['defaultAdministratorMail'], TR_ENGINE_STATUT, $cacheTimeLimit, $cookiePrefix, $cryptKey);
        }

        $ftp = Core_Main::getInstance()->getConfigFtp();
        $updateFtpFile = false;

        $ftpType = Core_Request::getWord("ftpType", $ftp['type'], "POST");
        if ($ftpType != $ftp['type']) {
            if (!empty($ftpType))
                $updateFtpFile = true;
            else
                $ftpType = $ftp['type'];
        }

        $ftpHost = Core_Request::getString("ftpHost", $ftp['host'], "POST");
        if ($ftpHost != $ftp['host']) {
            if (!empty($ftpHost))
                $updateFtpFile = true;
            else
                $ftpHost = $ftp['host'];
        }

        $ftpPort = Core_Request::getInt("ftpPort", 21, "POST");
        if ($ftpPort != $ftp['port']) {
            if (is_int($ftpPort))
                $updateFtpFile = true;
            else
                $ftpPort = $ftp['port'];
        }

        $ftpUser = Core_Request::getString("ftpUser", $ftp['user'], "POST");
        if ($ftpUser != $ftp['user']) {
            if (!empty($ftpUser))
                $updateFtpFile = true;
            else
                $ftpUser = $ftp['user'];
        }

        $ftpPass = Core_Request::getString("ftpPass", $ftp['pass'], "POST");
        if ($ftpPass != $ftp['pass']) {
            if (!empty($ftpPass))
                $updateFtpFile = true;
            else
                $ftpPass = $ftp['pass'];
        }

        $ftpRoot = Core_Request::getString("ftpRoot", $ftp['root'], "POST");
        if ($ftpRoot != $ftp['root']) {
            if (!empty($ftpRoot))
                $updateFtpFile = true;
            else
                $ftpRoot = $ftp['root'];
        }

        if ($updateFtpFile) {
            Exec_FileBuilder::buildFtpFile($ftpHost, $ftpPort, $ftpUser, $ftpPass, $ftpRoot, $ftpType);
        }

        $coreSql = Core_Sql::getInstance();
        $updateDatabaseFile = false;

        $dbHost = Core_Request::getString("dbHost", $coreSql->getDatabaseHost(), "POST");
        if ($dbHost != $coreSql->getDatabaseHost()) {
            if (!empty($dbHost))
                $updateDatabaseFile = true;
            else
                $dbHost = $coreSql->getDatabaseHost();
        }

        $dbName = Core_Request::getString("dbName", $coreSql->getDatabaseName(), "POST");
        if ($dbName != $coreSql->getDatabaseName()) {
            if (!empty($dbName))
                $updateDatabaseFile = true;
            else
                $dbName = $coreSql->getDatabaseName();
        }

        $dbPrefix = Core_Request::getString("dbPrefix", $coreSql->getDatabasePrefix(), "POST");
        if ($dbPrefix != $coreSql->getDatabasePrefix()) {
            if (!empty($dbPrefix))
                $updateDatabaseFile = true;
            else
                $dbPrefix = $coreSql->getDatabasePrefix();
        }

        $dbUser = Core_Request::getString("dbUser", $coreSql->getDatabaseUser(), "POST");
        if ($dbUser != $coreSql->getDatabaseUser()) {
            if (!empty($dbUser))
                $updateDatabaseFile = true;
            else
                $dbUser = $coreSql->getDatabaseUser();
        }

        $dbPass = Core_Request::getString("dbPass", $coreSql->getDatabasePass(), "POST");
        if ($dbPass != $coreSql->getDatabasePass()) {
            if (!empty($dbPass))
                $updateDatabaseFile = true;
            else
                $dbPass = $coreSql->getDatabasePass();
        }

        $dbType = Core_Request::getWord("dbType", $coreSql->getDatabaseType(), "POST");
        if ($dbType != $coreSql->getDatabaseType()) {
            if (!empty($dbType))
                $updateDatabaseFile = true;
            else
                $dbType = $coreSql->getDatabaseType();
        }

        if ($updateDatabaseFile) {
            Exec_FileBuilder::buildDatabaseFile($dbHost, $dbUser, $dbPass, $dbName, $dbType, $dbPrefix);
        }

        if (Core_Main::isFullScreen()) {
            Core_Html::getInstance()->redirect("index.php?mod=management&manage=setting&selectedTab=settingtabidTab1", 1);
        }
    }

}

?>