<?php
/*
 * NOTE IMPORTANTE : tout ce fichier doit être compatible PHP 4
 * Il ne doit pas provoquer d'erreur a son chargement mais Core_Info ne doit pas être executé sous PHP 4.
 */
if (preg_match("/info.class.php/ie", $_SERVER['PHP_SELF'])) {
	exit();
}

/**
 * Version php sous forme x.x.x.x (exemple : 5.2.9.2)
 */
define("TR_ENGINE_PHP_VERSION", preg_replace("/[^0-9.]/", "", (preg_replace("/(_|-|[+])/", ".", phpversion()))));

// Si une version PHP OO moderne n'est pas détecté
if (TR_ENGINE_PHP_VERSION < "5.0.0") {
	echo"<b>Sorry, but the PHP version currently running is too old to understand TR ENGINE.</b>"
	. "<br /><br />MINIMAL PHP VERSION : 5.0.0"
	. "<br />YOUR PHP VERSION : " . TR_ENGINE_PHP_VERSION;
	exit();
}

new Core_Info();

/**
 * Configurateur rapide
 * NOTE IMPORTANTE : cette classe doit être compatible PHP 4
 * 
 * @author Sebastien Villemain
 *
 */
class Core_Info {
	
	/**
	 * Capture de la configuration courante
	 */
	function __construct() {
		
		/**
		 * Chemin jusqu'a la racine
		 */
		define("TR_ENGINE_DIR", $this->getBaseDir());
		
		/**
		 * Adresse URL complete jusqu'a TR ENGINE
		 */
		define("TR_ENGINE_URL", $this->getUrlAddress());
		
		/**
		 * Le système d'exploitation qui execute TR ENGINE
		 */
		define("TR_ENGINE_PHP_OS", $this->getPhpOs());
	}
	
	
	/**
	 * Retourne le chemin jusqu'a la racine
	 * 
	 * @return String
	 */
	function getBaseDir() {
		$baseDir = "";
		// Recherche du chemin absolu depuis n'importe quel fichier
		if (defined("TR_ENGINE_INDEX")) {
			// Nous sommes dans l'index
			$baseDir = str_replace('\\', '/', getcwd());
		} else {
			// Chemin de base
			$baseName = str_replace($_SERVER['SCRIPT_NAME'], "", $_SERVER['SCRIPT_FILENAME']);
			// Chemin jusqu'au fichier
			$currentPath = str_replace('\\', '/', getcwd());
			// On isole le chemin en plus jusqu'au fichier
			$path = str_replace($baseName, "", $currentPath);
			$path = substr($path, 1); // Suppression du slash
			
			if (!empty($path)) { // Recherche du chemin complet
				// Vérification en se reperant sur l'emplacement du fichier de configuration
				while (!is_file($baseName . "/" . $path . "/configs/config.inc.php")) {
					// On remonte d'un cran
					$path = dirname($path);
					// La recherche n'aboutira pas
					if ($path == ".") break;
				}
			}
			
			// Verification du résultat
			if (!empty($path) && is_file($baseName . "/" . $path . "/configs/config.inc.php")) $baseDir = $baseName . "/" . $path;
			else if (is_file($baseName . "/configs/config.inc.php")) $baseDir = $baseName;
			else $baseDir = $baseName;
		}
		return $baseDir;
	}
	
	/**
	 * Retourne l'adresse URL complete jusqu'a TR ENGINE
	 * 
	 * @return String
	 */
	function getUrlAddress() {
		// Recherche de l'URL courante
		$urlTmp = $_SERVER["REQUEST_URI"];
		if (substr($urlTmp, -1) == "/") $urlTmp = substr($urlTmp, 0, strlen($urlTmp)-1);
		if ($urlTmp[0] == "/") $urlTmp = substr($urlTmp, 1);
		$urlTmp = explode("/", $urlTmp);
		
		// Recherche du dossier courant
		$urlBase = explode("/", TR_ENGINE_DIR);
		$urlBase = $urlBase[count($urlBase)-1];
		
		// Construction du lien
		$urlFinal = "";
		for($i = count($urlTmp)-1; $i >= 0; $i--) {
			if ($urlTmp[$i] == $urlBase) {
				if (empty($urlFinal)) $urlFinal = $urlTmp[$i];
				else $urlFinal = $urlTmp[$i] . "/" . $urlFinal;
			}
		}		
		return ((empty($urlFinal)) ? $_SERVER["SERVER_NAME"] : $_SERVER["SERVER_NAME"] . "/" . $urlFinal);
	}
	
	/**
	 * Retourne la plateforme sur lequel est PHP
	 * 
	 * @return String
	 */
	function getPhpOs() {
		return strtoupper(substr(PHP_OS, 0, 3));
	}
}

?>