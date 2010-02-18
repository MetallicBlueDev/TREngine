<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
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
		// Nom de la page administable
		$managePage = Core_Request::getString("manage", "", "GET");
		
		// Liste de pages de configuration
		$pageName = array();
		$pageList = self::listManagementPages($pageName);
		
		// Préparation de la mise en page
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("pageList", $pageList);
		$libsMakeStyle->assign("pageName", $pageName);
		
		// Affichage d'une page de configuration spécial
		if (!empty($managePage) && in_array($managePage, $pageList)) {
			$libsMakeStyle->assign("pageSelected", $managePage);
			require(TR_ENGINE_DIR . "/modules/management/" . $managePage . ".setting.module.php");
			//$libsMakeStyle->display("management_setting.tpl");
		} else {
			// Affichage du panel d'administration complet
			$libsMakeStyle->assign("pageSelected", "");
			$libsMakeStyle->display("management_index.tpl");
		}
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