<?php
if (preg_match("/phpversion.inc.php/ie", $_SERVER['PHP_SELF'])) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Retourne la version de PHP executée
 * 
 * @return $phpversion String
 */
function getPhpVersion() {
	return preg_replace("/[^0-9.]/", "", (preg_replace("/(_|-|[+])/", ".", @phpversion())));
}

define("TR_ENGINE_PHP_VERSION", getPhpVersion());

// Si une version PHP OO moderne est détecté
if (TR_ENGINE_PHP_VERSION < "5.0.0") {
	echo"<b>Sorry, but the PHP version currently running is too old to understand TR ENGINE.</b>"
	. "<br /><br />MINIMAL PHP VERSION : 5.0.0"
	. "<br />YOUR PHP VERSION : " . TR_ENGINE_PHP_VERSION;
	exit();
}


?>