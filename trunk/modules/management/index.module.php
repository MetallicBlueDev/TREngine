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
		// Nom de la page a administrer
		$manage = Core_Request::getString("manage", "", "GET");
		
		// Liste de pages de configuration
		$pageName = array();
		$pageList = self::listManagementPages($pageName);
		
		if (!empty($manage) && in_array($manage, $pageList)) {
			
			//Libs_Module::getInstance()->getInfoModule()
			echo "ok " . $manage;
		} else {
			$libsMakeStyle = new Libs_MakeStyle();
			$libsMakeStyle->assign("pageList", $pageList);
			$libsMakeStyle->assign("pageName", $pageName);
			
			$libsMakeStyle->display("management_index.tpl");
		}
	}
	
	/**
	 * Retourne un tableau contenant les pages d'administration disponibles
	 * 
	 * @return array
	 */
	private static function &listManagementPages(&$pageName = array()) {
		$page = "";
		$pageList = array();
		$files = Core_CacheBuffer::listNames("modules/management");
		foreach($files as $key => $fileName) {
			$pos = strpos($fileName, ".page");
			if ($pos !== false && $pos > 0) {
				$page = substr($fileName, 0, $pos);
				$pageList[] = $page;
				
				if (defined("PAGE_TITLE_" . strtoupper($page))) {
					$pageName[] = constant("PAGE_TITLE_" . strtoupper($page));
				} else {
					$pageName[] = ucfirst($page);
				}
			}
		}
		return $pageList;
	}
}

?>