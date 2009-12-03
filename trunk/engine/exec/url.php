<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire d'adresse URL
 * 
 * @author Sébastien Villemain
 *
 */
class Exec_Url {
	
	public static function &cleanUrl($url) {
		// TODO coder une détection de l'url avec http dedans etc..
		return $url;
	}
	
}


?>