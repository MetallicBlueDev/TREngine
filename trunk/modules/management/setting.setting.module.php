<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Management_Setting extends Module_Model {
	public function setting() {
		$localView = Core_Request::getWord("localView", "", "POST");
		
		if (!empty($localView)) {
			switch($localView) {
				case "sendGeneral":
					$this->sendGeneral();
					break;
				case "sendSystem":
					$this->sendSystem();
					break;
			}
		} else {
			Core_Loader::classLoader("Libs_Form");
			Core_Loader::classLoader("Libs_Tabs");
			
			$accountTabs = new Libs_Tabs("settingtab");
			$accountTabs->addTab(SETTING_GENERAL_TAB, $this->tabGeneral());
			$accountTabs->addTab(SETTING_SYSTEM_TAB, $this->tabSystem());
			
			return $accountTabs->render();
		}
		return "";
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
		Core_Sql::update(
			Core_Table::$CONFIG_TABLE,
			array("value" => $value),
			array("name = '" . $key . "'")
		);
	}
	
	private function tabGeneral() {
		$form = new Libs_Form("generalblock");
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
		$modules = Libs_Module::listModules();
		$currentModule = Core_Main::$coreConfig['defaultMod'];
		$currentModuleName = "";
		foreach($modules as $module) {
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
		Core_Html::getInstance()->addJavascript("validSetting('#form-generalblock', '#form-generalblock-defaultSiteName-input', '#form-generalblock-defaultAdministratorMail-input');");
		return $form->render();
	}
	
	private function sendGeneral() {
		$deleteCache = false;
		
		// Etat du site
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
				Core_Exception::addAlertError(SETTING_GENERAL_DEFAULT_SITE_NAME_INVALID);
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
			Core_Loader::classLoader("Exec_Mailer");
			if (!empty($defaultAdministratorMail) && Exec_Mailer::validMail($defaultAdministratorMail)) {
				$this->updateTable("defaultAdministratorMail", $defaultAdministratorMail);
				$deleteCache = true;
			} else {
				Core_Exception::addAlertError(SETTING_GENERAL_DEFAULT_ADMIN_MAIL_INVALID);
			}
		}
		// Langue par défaut
		$defaultLanguage = Core_Request::getString("defaultLanguage", "", "POST");
		if (Core_Main::$coreConfig['defaultLanguage'] != $defaultLanguage) {
			$langues = Core_Translate::listLanguages();
			if (!empty($defaultLanguage) && Core_Utils::inArray($defaultLanguage, $langues)) {
				$this->updateTable("defaultLanguage", $defaultLanguage);
				$deleteCache = true;
			} else {
				Core_Exception::addAlertError(SETTING_GENERAL_DEFAULT_LANGUAGE_INVALID);
			}
		}
		// Template par défaut
		$defaultTemplate = Core_Request::getString("defaultTemplate", "", "POST");
		if (Core_Main::$coreConfig['defaultTemplate'] != $defaultTemplate) {
			$templates = Libs_Makestyle::listTemplates();
			if (!empty($defaultTemplate) && Core_Utils::inArray($defaultTemplate, $templates)) {
				$this->updateTable("defaultTemplate", $defaultTemplate);
				$deleteCache = true;
			} else {
				Core_Exception::addAlertError(SETTING_GENERAL_DEFAULT_TEMPLATE_INVALID);
			}
		}
		// Module par défaut
		$defaultMod = Core_Request::getString("defaultMod", "", "POST");
		if (Core_Main::$coreConfig['defaultMod'] != $defaultMod) {
			if (!empty($defaultMod)) {
				$this->updateTable("defaultMod", $defaultMod);
				$deleteCache = true;
			} else {
				Core_Exception::addAlertError(SETTING_GENERAL_DEFAULT_MODULE_INVALID);
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
		// Etat de la réécriture des URLs
		$urlRewriting = Core_Request::getString("urlRewriting", "", "POST");
		if (Core_Main::$coreConfig['urlRewriting'] != $urlRewriting) {
			$urlRewriting = ($urlRewriting == 1) ? 1 : 0;
			$this->updateTable("urlRewriting", $urlRewriting);
			$deleteCache = true;
		}
		
		// Suppression du cache
		if ($deleteCache) {
			$this->deleteCache();
			Core_Exception::addInfoError(DATA_SAVED);
		}
		
		if (Core_Main::isFullScreen()) {
			Core_Html::getInstance()->redirect("index.php?mod=management&manage=setting&selectedTab=settingtabidTab0", 1);
		}
	}
	
	private function tabSystem() {
		$form = new Libs_Form("systemblock");
		$form->setTitle(SETTING_SYSTEM_CACHE_SETTING_TITLE);
		$form->setDescription(SETTING_SYSTEM_CACHE_SETTING_DESCRIPTION);
		$form->addInputText("cacheTimeLimit", SETTING_SYSTEM_CACHE_SETTING_CACHE_LIMIT, Core_Main::$coreConfig['cacheTimeLimit']);
		$form->addInputText("cryptKey", SETTING_SYSTEM_CACHE_SETTING_CRYPT_KEY, Core_Main::$coreConfig['cryptKey']);
		$form->addSpace();
		
		$form->addFieldset(SETTING_SYSTEM_SESSION_SETTING_TITLE, SETTING_SYSTEM_SESSION_SETTING_DESCRIPTION);
		$form->addInputText("cookiePrefix", SETTING_SYSTEM_SESSION_SETTING_COOKIE_PREFIX, Core_Main::$coreConfig['cookiePrefix']);
		$form->addSpace();
		// TODO aller chercher les vrai valeur du ftp et de la base de donnée
		$form->addFieldset(SETTING_SYSTEM_FTP_SETTING_TITLE, SETTING_SYSTEM_FTP_SETTING_DESCRIPTION);
		$form->addInputText("ftpHost", SETTING_SYSTEM_FTP_SETTING_HOST, Core_Main::$coreConfig['ftpHost']);
		$form->addInputText("ftpPort", SETTING_SYSTEM_FTP_SETTING_PORT, Core_Main::$coreConfig['ftpPort']);
		$form->addInputText("ftpUser", SETTING_SYSTEM_FTP_SETTING_USER, Core_Main::$coreConfig['ftpPort']);
		$form->addInputPassword("ftpPass", SETTING_SYSTEM_FTP_SETTING_PASSWORD, Core_Main::$coreConfig['ftpPort']);
		$form->addInputText("ftpRoot", SETTING_SYSTEM_FTP_SETTING_ROOT, Core_Main::$coreConfig['ftpPort']);
		$form->addSpace();
		
		$form->addFieldset(SETTING_SYSTEM_DATABASE_SETTING_TITLE, SETTING_SYSTEM_DATABASE_SETTING_DESCRIPTION);
		$form->addInputText("dbHost", SETTING_SYSTEM_DATABASE_SETTING_HOST, Core_Main::$coreConfig['ftpHost']);
		$form->addInputText("dbName", SETTING_SYSTEM_DATABASE_SETTING_NAME, Core_Main::$coreConfig['ftpHost']);
		$form->addInputText("dbPrefix", SETTING_SYSTEM_DATABASE_SETTING_PREFIX, Core_Main::$coreConfig['ftpHost']);
		$form->addInputText("dbUser", SETTING_SYSTEM_DATABASE_SETTING_USER, Core_Main::$coreConfig['ftpHost']);
		$form->addInputText("dbPass", SETTING_SYSTEM_DATABASE_SETTING_PASSWORD, Core_Main::$coreConfig['ftpHost']);
		$form->addInputText("dbType", SETTING_SYSTEM_DATABASE_SETTING_TYPE, Core_Main::$coreConfig['ftpHost']);
		
		$form->addSelectOpenTag("dbType", SETTING_SYSTEM_DATABASE_SETTING_TYPE);
		$bases = Core_Sql::listBases();
		foreach($bases as $base) {
			$form->addSelectItemTag($base);
		}
		$form->addSelectCloseTag();
		
		$form->addSpace();
		return $form->render();
	}
	
	// TODO coder la reception des données sendSystem
	private function sendSystem() {
		if (Core_Main::isFullScreen()) {
			Core_Html::getInstance()->redirect("index.php?mod=management&manage=setting&selectedTab=settingtabidTab1", 1);
		}
	}
}

?>