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
		// Ajout du CSS
		Core_HTML::getInstance()->addCssTemplateFile("management.css");
		
		// Nom de la page administable
		$managePage = Core_Request::getString("manage", "", "GET");
		
		// Liste de pages de configuration
		$pageName = array();
		$pageList = self::listManagementPages($pageName);
		
		// Liste des modules
		$moduleName = array();
		$moduleList = self::listModules($moduleName);
		
		// Préparation de la mise en page
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("pageList", $pageList);
		$libsMakeStyle->assign("pageName", $pageName);
		$libsMakeStyle->assign("moduleList", $moduleList);
		$libsMakeStyle->assign("moduleName", $moduleName);
		
		// Affichage de la page d'administration
		$managementScreen = "management_index.tpl";
		$pageSelected = "";
		if (!empty($managePage)) { // Affichage d'une page de configuration spécial
			$settingPage = in_array($managePage, $pageList);
			$modulePage = in_array($managePage, $moduleList);
			
			// Si c'est une page valide
			if ($settingPage || $modulePage) {
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
	 * Retourne un tableau contenant les modules disponibles
	 * 
	 * @param $moduleName array le nom des modules
	 * @return array
	 */
	private static function &listModules(&$moduleName) {
		$moduleList = array();
		Core_Sql::select(
			Core_Table::$MODULES_TABLE,
			array("name")
		);
		
		if (Core_Sql::affectedRows() > 0) {
			while($module = Core_Sql::fetchArray()) {
				if (Core_Access::moderate($module['name'])) {
					$moduleList[] = $module['name'];
					$moduleName[] = MODULE_MANAGEMENT . " " . $module['name'];
				}
			}
		}
		return $moduleList;
	}
	
	/**
	 * Retourne un tableau contenant les pages d'administration disponibles
	 * 
	 * @param $pageName array le nom des pages
	 * @return array
	 */
	private static function &listManagementPages(&$pageName) {
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
					$pageList[] = $page;
					
					if (defined("SETTING_TITLE_" . strtoupper($page))) {
						$pageName[] = constant("SETTING_TITLE_" . strtoupper($page));
					} else {
						$pageName[] = ucfirst($page);
					}
				}
			}
		}
		return $pageList;
	}
}

?>