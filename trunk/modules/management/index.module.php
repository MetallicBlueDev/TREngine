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
		$pages = self::listManagementPages();
		foreach($pages as $page) {
			echo "<a href=\"?mod=management&page=" . $page . "\">";
		}
	}
	
	/**
	 * Retourne un tableau contenant les pages d'administration disponibles
	 * 
	 * @return array
	 */
	private static function &listManagementPages() {
		$pages = array();
		$files = Core_CacheBuffer::listNames("modules/management/pages");
		foreach($files as $key => $fileName) {
			$pos = strpos($fileName, ".page");
			if ($pos !== false && $pos > 0) {
				$pages[] = substr($fileName, 0, $pos);
			}
		}
		return $pages;
	}
}

?>