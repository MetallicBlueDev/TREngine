<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Outil destinée au cookie
 * 
 * @author Sébastien Villemain
 */
class Exec_Cookie {
	
	/**
	 * Création d'un cookie
	 * 
	 * @param $cookieName
	 * @param $cookieContent
	 * @param $cookieTimeLimit
	 * @return boolean true succès
	 */
	public static function &createCookie($cookieName, $cookieContent, $cookieTimeLimit = "") {
		$cookieName = urlencode($cookieName);
		$cookieContent = urlencode($cookieContent);
		if (empty($cookieTimeLimit)) $rslt = setcookie($cookieName, $cookieContent);
		else if ($cookieTimeLimit == "-1" && isset($_COOKIE[$cookieName])) $rslt = setcookie($cookieName, "");
		else $rslt = setcookie($cookieName, $cookieContent, $cookieTimeLimit);
		return $rslt;
	}
	
	/**
	 * Destruction d'un cookie
	 * 
	 * @param $cookieName
	 * @return boolean true succès
	 */
	public static function &destroyCookie($cookieName) {
		return self::createCookie($cookieName, "", "-1");
	}
	
	/**
	 * Retourne le contenu du cookie
	 * 
	 * @param $cookieName String
	 * @return String
	 */
	public static function &getCookie($cookieName) {
		$cookieName = urlencode($cookieName);
		$cookieContent = Core_Request::getString($cookieName, "", "COOKIE");
		$cookieContent = urldecode($cookieContent);
		return $cookieContent;
	}
}
?>