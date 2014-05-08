<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire d'adresse URL
 * 
 * @author Sébastien Villemain
 */
class Exec_Url {
	
	/**
	 * Nettoie l'adresse web (URL) du protocole
	 * 
	 * @param string $url
	 * @return string
	 */
	public static function &cleanUrl($url) {
		if (substr($url, 0, 7) == "http://") {
			$url = substr($url, 7, strlen($url));
		}
		return $url;
	}
	
}


?>