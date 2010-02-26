<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Management_Setting extends Module_Model {
	public function setting() {
		Core_Loader::classLoader("Libs_Form");
		Core_Loader::classLoader("Libs_Tabs");
		
		$accountTabs = new Libs_Tabs("accounttabs");
		$accountTabs->addTab(SETTING_GENERAL_TAB, $this->tabGeneral());
		$accountTabs->addTab(SETTING_SYSTEM_TAB, $this->tabSystem());
		
		return $accountTabs->render();
	}
	
	private function tabGeneral() {
		$form = new Libs_Form("generalblock");
		$form->setTitle(SETTING_GENERAL_SITE_SETTING_TITLE);
		$form->setDescription(SETTING_GENERAL_SITE_SETTING_DESCRIPTION);
		$form->addSpace();
		
		$online = (Core_Main::$coreConfig['defaultSiteStatut'] == "open") ? true : false;
		$form->addHtmlInFieldset(SETTING_GENERAL_SITE_SETTING_SITE_STATUT);
		$form->addInputRadio("defaultSiteStatut1", "defaultSiteStatut", SETTING_GENERAL_SITE_SETTING_SITE_ON, $online, "", "value=\"open\"");
		$form->addInputRadio("defaultSiteStatut2", "defaultSiteStatut", SETTING_GENERAL_SITE_SETTING_SITE_OFF, !$online, "", "value=\"close\"");
		$form->addSpace();
		
		$form->addTextarea("defaultSiteCloseReason", SETTING_GENERAL_SITE_SETTING_SITE_OFF_REASON, "", "style=\"display: block;\" cols=\"50\"", Core_Main::$coreConfig['defaultSiteCloseReason']);
		$form->addSpace();
		
		$form->addInputText("defaultSiteName", SETTING_GENERAL_DEFAULT_SITE_NAME, "", "value=\"" . Core_Main::$coreConfig['defaultSiteName'] . "\"");
		$form->addInputText("defaultSiteSlogan", SETTING_GENERAL_DEFAULT_SITE_SLOGAN, "", "value=\"" . Core_Main::$coreConfig['defaultSiteSlogan'] . "\"");
		$form->addInputText("defaultAdministratorMail", SETTING_GENERAL_DEFAULT_ADMIN_MAIL, "", "value=\"" . Core_Main::$coreConfig['defaultAdministratorMail'] . "\"");
		$form->addSpace();
		
		$form->addSelectOpenTag("defaultLanguage", SETTING_GENERAL_DEFAULT_LANGUAGE);
		$langues = Core_Translate::listLanguages();
		$currentLanguage = Core_Main::$coreConfig['defaultLanguage'];
		$form->addSelectItemTag($currentLanguage, "", true);
		foreach($langues as $langue) {
			if ($langue == $currentLanguage) continue;
			$form->addSelectItemTag($langue);
		}
		$form->addSelectCloseTag();
		
		$form->addSelectOpenTag("defaultTemplate", SETTING_GENERAL_DEFAULT_TEMPLATE);
		$templates = Libs_MakeStyle::listTemplates();
		$currentTemplate = Core_Main::$coreConfig['defaultTemplate'];
		$form->addSelectItemTag($currentTemplate, "", true);
		foreach($templates as $template) {
			if ($template == $currentTemplate) continue;
			$form->addSelectItemTag($template);
		}
		$form->addSelectCloseTag();
		
		$form->addSelectOpenTag("defaultMod", SETTING_GENERAL_DEFAULT_MODULE);
		$moduleName = array();
		$modules = Module_Management_Index::listModules($moduleName);
		$currentModule = Core_Main::$coreConfig['defaultMod'];
		$currentModuleKey = 0;
		foreach($modules as $key => $module) {
			if (module == $currentModule) {
				$currentModuleKey = $key;
				continue;
			}
			$form->addSelectItemTag($module, $moduleName[$key]);
		}
		$form->addSelectItemTag($currentModule, $moduleName[$currentModuleKey], true);
		$form->addSelectCloseTag();
		$form->addSpace();
		
		$form->addFieldset(SETTING_GENERAL_METADATA_TITLE, SETTING_GENERAL_METADATA_DESCRIPTION);
		$form->addSpace();
		
		$form->addTextarea("defaultDescription", SETTING_GENERAL_METADATA_DEFAULT_DESCRIPTION, "", "style=\"display: block;\" cols=\"50\"", Core_Main::$coreConfig['defaultDescription']);
		$form->addSpace();
		$form->addTextarea("defaultKeyWords", SETTING_GENERAL_METADATA_DEFAULT_KEYWORDS, "", "style=\"display: block;\" cols=\"50\"", Core_Main::$coreConfig['defaultKeyWords']);
		$form->addSpace();
		
		$rewriting = (Core_Main::$coreConfig['urlRewriting'] == 1) ? true : false;
		$form->addHtmlInFieldset(SETTING_GENERAL_METADATA_URLREWRITING);
		$form->addInputRadio("urlRewriting1", "urlRewriting", SETTING_GENERAL_METADATA_URLREWRITING_ON, $rewriting, "", "value=\"1\"");
		$form->addInputRadio("urlRewriting2", "urlRewriting", SETTING_GENERAL_METADATA_URLREWRITING_OFF, !$rewriting, "", "value=\"0\"");
		$form->addSpace();
		$form->addInputSubmit("submit", "", "value=\"" . VALID . "\"");
		return $form->render();
	}
	
	private function tabSystem() {
		$form = new Libs_Form("systemblock");
		$form->setTitle(SETTING_SYSTEM_CACHE_SETTING_TITLE);
		$form->setDescription(SETTING_SYSTEM_CACHE_SETTING_DESCRIPTION);
		$form->addInputText("cacheTimeLimit", SETTING_SYSTEM_CACHE_SETTING_CACHE_LIMIT, "", "value=\"" . Core_Main::$coreConfig['cacheTimeLimit'] . "\"");
		$form->addInputText("cryptKey", SETTING_SYSTEM_CACHE_SETTING_CRYPT_KEY, "", "value=\"" . Core_Main::$coreConfig['cryptKey'] . "\"");
		$form->addSpace();
		
		$form->addFieldset(SETTING_SYSTEM_SESSION_SETTING_TITLE, SETTING_SYSTEM_SESSION_SETTING_DESCRIPTION);
		$form->addInputText("cookiePrefix", SETTING_SYSTEM_SESSION_SETTING_COOKIE_PREFIX, "", "value=\"" . Core_Main::$coreConfig['cookiePrefix'] . "\"");
		$form->addSpace();
		// TODO aller chercher les vrai valeur du ftp et de la base de donnée
		$form->addFieldset(SETTING_SYSTEM_FTP_SETTING_TITLE, SETTING_SYSTEM_FTP_SETTING_DESCRIPTION);
		$form->addInputText("ftpHost", SETTING_SYSTEM_FTP_SETTING_HOST, "", "value=\"" . Core_Main::$coreConfig['ftpHost'] . "\"");
		$form->addInputText("ftpPort", SETTING_SYSTEM_FTP_SETTING_PORT, "", "value=\"" . Core_Main::$coreConfig['ftpPort'] . "\"");
		$form->addInputText("ftpUser", SETTING_SYSTEM_FTP_SETTING_USER, "", "value=\"" . Core_Main::$coreConfig['ftpPort'] . "\"");
		$form->addInputPassword("ftpPass", SETTING_SYSTEM_FTP_SETTING_PASSWORD, "", "value=\"" . Core_Main::$coreConfig['ftpPort'] . "\"");
		$form->addInputText("ftpRoot", SETTING_SYSTEM_FTP_SETTING_ROOT, "", "value=\"" . Core_Main::$coreConfig['ftpPort'] . "\"");
		$form->addSpace();
		
		$form->addFieldset(SETTING_SYSTEM_DATABASE_SETTING_TITLE, SETTING_SYSTEM_DATABASE_SETTING_DESCRIPTION);
		$form->addInputText("dbHost", SETTING_SYSTEM_DATABASE_SETTING_HOST, "", "value=\"" . Core_Main::$coreConfig['ftpHost'] . "\"");
		$form->addInputText("dbName", SETTING_SYSTEM_DATABASE_SETTING_NAME, "", "value=\"" . Core_Main::$coreConfig['ftpHost'] . "\"");
		$form->addInputText("dbPrefix", SETTING_SYSTEM_DATABASE_SETTING_PREFIX, "", "value=\"" . Core_Main::$coreConfig['ftpHost'] . "\"");
		$form->addInputText("dbUser", SETTING_SYSTEM_DATABASE_SETTING_USER, "", "value=\"" . Core_Main::$coreConfig['ftpHost'] . "\"");
		$form->addInputText("dbPass", SETTING_SYSTEM_DATABASE_SETTING_PASSWORD, "", "value=\"" . Core_Main::$coreConfig['ftpHost'] . "\"");
		$form->addInputText("dbType", SETTING_SYSTEM_DATABASE_SETTING_TYPE, "", "value=\"" . Core_Main::$coreConfig['ftpHost'] . "\"");
		
		$form->addSelectOpenTag("dbType", SETTING_SYSTEM_DATABASE_SETTING_TYPE);
		$bases = Core_Sql::listBase();
		foreach($bases as $base) {
			$form->addSelectItemTag($base);
		}
		$form->addSelectCloseTag();
		
		$form->addSpace();
		return $form->render();
	}
}

?>