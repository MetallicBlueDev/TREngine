<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire URL REWRITING
 * 
 * @author Sébastien Villemain
 *
 */
class Core_UrlRewriting {
	
	/**
	 * Vérifie si l'url rewriting a été activé
	 * 
	 * @return boolean true c'est activé
	 */
	public static function isActived() {
		return false;
	}
	
	public static function displayAll($buffer) {
		echo "";
	}
}
?>