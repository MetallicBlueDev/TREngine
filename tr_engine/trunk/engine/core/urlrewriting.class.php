<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire URL REWRITING
 * 
 * @author S�bastien Villemain
 *
 */
class Core_UrlRewriting {
	
	/**
	 * V�rifie si l'url rewriting a �t� activ�
	 * 
	 * @return boolean true c'est activ�
	 */
	public static function isActived() {
		return false;
	}
	
	public static function displayAll($buffer) {
		echo "";
	}
}
?>