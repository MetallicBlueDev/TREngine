<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

/**
 * Module d'interface entre le site et le client
 * 
 * @author Sebastien Villemain
 *
 */
class Module_Management_Index extends Module_Model {
	
	public function display() {
		// Ajout du CSS du template et du fichier javascript par défaut
		Core_HTML::getInstance()->addCssTemplateFile("style_management.css");
		Core_HTML::getInstance()->addJavascriptFile("management.js");
		
		// Nom de la page administable
		$managePage = Core_Request::getString("manage");
		
		$pageList = self::listManagementPages(); // Liste de pages de configuration
		$moduleList = Libs_Module::listModules(); // Liste des modules
		
		// Préparation de la mise en page
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("pageList", $pageList);
		$libsMakeStyle->assign("moduleList", $moduleList);
		
		// Affichage de la page d'administration
		$managementScreen = "management_index.tpl";
		$pageSelected = "";
		if (!empty($managePage)) { // Affichage d'une page de configuration spécial
			$settingPage = Exec_Utils::inMultiArray($managePage, $pageList);
			$moduleSettingPage = Exec_Utils::inMultiArray($managePage, $moduleList);
			
			// Si c'est une page valide
			if ($settingPage || $moduleSettingPage) {
				$pageSelected = $managePage; // Page selectionnée
				$managementScreen = "management_setting.tpl"; // Nom du template
				
				// Récuperation du module
				$moduleClassPage = "";
				$moduleClassName = "";
				if ($settingPage) {
					$moduleClassPage = "Module_Management_" . ucfirst($managePage) . ".setting";
					$moduleClassName = "Module_Management_" . ucfirst($managePage);
				} else {
					$moduleClassPage = $moduleClassName = "Module_" . ucfirst($managePage) . "_Index";
				}
				
				// Si aucune erreur, on lance le module
				if (Core_Loader::classLoader($moduleClassPage)) {
					$ModuleClass = new $moduleClassName();
					
					$content = "";
					if (Core_Loader::isCallable($moduleClassName, "setting")) {
						$content = $ModuleClass->setting();
					}
					$libsMakeStyle->assign("content", $content);
				}
			}
		}
		$libsMakeStyle->assign("pageSelected", $pageSelected);
		$libsMakeStyle->display($managementScreen);
	}
	
	/**
	 * Retourne un tableau contenant les pages d'administration disponibles
	 * 
	 * @return array => array("value" => valeur de la page, "name" => nom de la page)
	 */
	public static function &listManagementPages() {
		$page = "";
		$pageList = array();
		$files = Core_CacheBuffer::listNames("modules/management");
		foreach($files as $key => $fileName) {
			// Nettoyage du nom de la page
			$pos = strpos($fileName, ".setting.module");
			// Si c'est une page administrable
			if ($pos !== false && $pos > 0) {
				$page = substr($fileName, 0, $pos);
				// Vérification des droits de l'utilisateur
				if (Core_Access::moderate("management/" . $page . ".setting")) {
					$name = defined("SETTING_TITLE_" . strtoupper($page)) ? constant("SETTING_TITLE_" . strtoupper($page)) : ucfirst($page);
					$pageList[] = array("value" => $page, "name" => $name);
				}
			}
		}
		return $pageList;
	}
}

?>