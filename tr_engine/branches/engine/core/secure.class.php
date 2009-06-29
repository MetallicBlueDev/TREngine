<?php
if (preg_match("/secure.class.php/ie", $_SERVER['PHP_SELF'])) {
	new Core_Secure();
}

/**
 * Système de sécurité
 * Analyse rapidement les données recues
 * Configure les erreurs
 * Capture la configuration
 * 
 * @author Sébastien Villemain
 *
 */
class Core_Secure {
	
	/**
	 * Adresse IP du client courant
	 */ 
	private static $user_ip = "";
	
	/**
	 * Instance de cette classe
	 */ 
	private static $secure = false;
	
	public function __construct() {
		$this->checkError();
		$this->checkQueryString();
		$this->checkRequestReferer();
		$this->checkGPC();
		$this->checkUserIp();
		$this->getConfig();
		
		// Si nous ne sommes pas passé par l'index
		if (!defined("TR_ENGINE_INDEX")) $this->debug("badUrl");
	}
	
	/**
	 * Créé une instance de la classe si elle n'existe pas
	 * Retourne l'instance de la classe
	 * 
	 * @return Core_Secure
	 */
	public static function getInstance() {
		if (!self::$secure) {
			self::$secure = new self();
		}
		return self::$secure;
	}
	
	/**
	 * Réglages des sorties d'erreurs
	 */
	private function checkError() {
		// Réglages des sorties d'erreur
		error_reporting(E_ERROR | E_WARNING | E_PARSE);
	}
	
	/**
	 * Vérification des données recus (Query String)
	 */
	private function checkQueryString() {
		$queryString = strtolower(rawurldecode($_SERVER['QUERY_STRING']));
		$badString = array("SELECT", "UNION", 
			"INSERT", "UPDATE", "AND", 
			"%20union%20", "/*", "*/union/*", 
			"+union+", "load_file", "outfile", 
			"document.cookie", "onmouse", "<script", 
			"<iframe", "<applet", "<meta", "<style", 
			"<form", "<img", "<body", "<link");
		
		foreach ($badString as $stringValue) {
			if (strpos($queryString, $stringValue)) $this->debug();
		}
	}
	
	/**
	 * Vérification des envoies POST
	 */
	private function checkRequestReferer() {
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			if ($_SERVER['HTTP_REFERER'] != "") { 
				if (!preg_match("/" . $_SERVER['HTTP_HOST'] . "/", $_SERVER['HTTP_REFERER'])) $this->debug();
			}
		}
	}
	
	/**
	 * Renseigne l'adresse IP (v4) correcte du client
	 */
	private function checkUserIp() {
		// Recherche de l'IP
		if ($_SERVER['HTTP_CLIENT_IP']) $user_ip = $_SERVER['HTTP_CLIENT_IP'];
		else if ($_SERVER['HTTP_X_FORWARDED_FOR']) $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if ($_SERVER['REMOTE_ADDR']) $user_ip = $_SERVER['REMOTE_ADDR'];
		
		if (isset($user_ip) && $user_ip != "" && preg_match("/([0-9]{1,3}\.){3}[0-9]{1,3}/", $user_ip)) self::$user_ip = $user_ip; 
		else self::$user_ip = "";
	}
	
	/**
	 * Fonction de substitution pour MAGIC_QUOTES_GPC
	 */
	private function checkGPC() {
		if (function_exists("magic_quotes_runtime") 
				&& function_exists("set_magic_quotes_runtime")) {
			set_magic_quotes_runtime(0);
		}
		
		$GPC = array("_GET", "_POST", "_COOKIE");
		foreach ($GPC as $KEY) $this->addSlashesForQuotes($$KEY);
	}
	
	/**
	 * Ajoute un antislash pour chaque quote
	 * 
	 * @param $key objet sans antislash
	 */
	private function addSlashesForQuotes($key) {
		if (is_array($key)) {
			while (list($k, $v) = each($key)) {
				// Ajout 
				if (is_array($key[$k])) $this->addSlashesForQuotes($key[$k]);
				else $key[$k] = addslashes($v);
			}
			reset($key);
		} else {
			$key = addslashes($key);
		}
	}
	
	/**
	 * Capture de la configuration courante
	 */
	private function getConfig() {
		
		/**
		 * Chemin jusqu'a la racine
		 */
		define("TR_ENGINE_DIR", $this->getBaseDir());
		
		/**
		 * Adresse URL complete jusqu'a TR ENGINE
		 */
		define("TR_ENGINE_URL", $this->getUrlAddress());
		
		/**
		 * Version php sous forme 5.2.9.2
		 */
		// TR_ENGINE_PHP_VERSION est définie dans le fichier "phpversion.inc.php"
	}
	
	
	/**
	 * Retourne le chemin jusqu'a la racine
	 * 
	 * @return $baseDir String
	 */
	private function getBaseDir() {
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
			
			if ($path != "") { // Recherche du chemin complet
				// Vérification en se reperant sur l'emplacement du fichier config
				while (!@is_file($baseName . "/" . $path . "/configs/config.inc.php")) {
					// On remonte d'un cran
					$path = dirname($path);
					// La recherche n'aboutira pas
					if ($path == ".") break;
				}
			}
			
			// Verification du résultat
			if ($path != "" && is_file($baseName . "/" . $path . "/configs/config.inc.php")) $baseDir = $baseName . "/" . $path;
			else if (is_file($baseName . "/configs/config.inc.php")) $baseDir = $baseName;
			else $baseDir = $baseName;
		}
		return $baseDir;
	}
	
	/**
	 * Retourne l'adresse URL complete jusqu'a TR ENGINE
	 * 
	 * @return $urlAddress String
	 */
	private function getUrlAddress() {
		$url = explode("/", $_SERVER["REQUEST_URI"]);
		$url_tmp = $url;
		$nb_url = count($url);
		$baseUrl = explode("/", TR_ENGINE_DIR);
		$nb_baseUrl = count($baseUrl);
		for ($i = $nb_url, $j = $nb_baseUrl; $i > 0; $i--, $j--) {
			if ($url[$i] == $baseUrl[$j]) array_splice($url_tmp, 0, $i); // Suppression des clés inutile
			else break;
		}
		return $_SERVER["SERVER_NAME"] . (($url_tmp[0] != "") ? "/" . implode("/", $url_tmp) : "");
	}
	
	/**
	 * Retourne l'adresse IP du client
	 * 
	 * @return String
	 */
	public static function getUserIp() {
		return self::$user_ip;
	}
	
	/**
	 * La fonction debug affiche un message d'erreur
	 * Cette fonction est activé si une erreur est détecté
	 * 
	 * @param $ie L'exception interne levée
	 */
	public function debug($ie = "") {
		// Charge le loader si il faut
		if (!class_exists("Core_Loader")) {
			require(TR_ENGINE_DIR . "/engine/core/loader.class.php");
		}
		
		// Charge Make Style si besoin
		Core_Loader::classLoader("Libs_MakeStyle");
		// Préparation du template debug
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("errorMessageTitle", $this->getErrorMessageTitle($ie));
		$libsMakeStyle->assign("errorMessage", $this->getDebugMessage($ie));
		// Affichage du template en debug si problème
		$libsMakeStyle->displayDebug("debug.tpl");	
		
		exit();
	}
	
	/**
	 * Analyse l'erreur et pepare l'affichage de l'erreur
	 * 
	 * @param $ie L'exception interne levée
	 * @return String $errorMessage
	 */
	private function getDebugMessage($ie) {
		// Tableau avec les lignes d'erreurs
		$errorMessage = array();
		// Si l'exception est bien présente
		if (is_object($ie)) {
			$trace = $ie->getTrace();
			$nbTrace = count($trace);
			
			for ($i = $nbTrace; $i > 0; $i--) {
				// Erreur courante
				$errorLine = "";
				if (is_array($trace[$i])) {
					foreach($trace[$i] as $key => $value) {
						if ($key == "file") {
							$value = preg_replace("/([a-zA-Z0-9.]+).php/", "<b>\\1</b>.php", $value);
							$errorLine .= " <b>" . $key . "</b> " . $value;
						} else if ($key == "line" || $key == "class") {
							$errorLine .= " in <b>" . $key . "</b> " . $value;
						}					
					}
				}
				
				// Remplissage dans avec les autres erreurs
				if ($errorLine != "") {
					$errorMessage[] = $errorLine;
				}
			}
		}
		return $errorMessage;
	}
	
	/**
	 * Retourne le type d'erreur courant sous forme de message
	 * 
	 * @param $cmd
	 * @return String $errorMessageTitle
	 */
	private function getErrorMessageTitle($ie) {
		// En fonction du type d'erreur
		if (is_object($ie)) $cmd = $ie->getMessage();
		else $cmd = $ie;
		
		// Uniformise pour éviter les prises de tête
		$cmd = strtolower($cmd);
		
		/**
		 * List des messages d'erreurs
		 */
		$errorMessageTitle = array(
			"close" => "The site is currently closed.",
			"sqlconnect" => "Error connecting to the database.",
			"sqlreq" => "Error SQL query.",
			"sqltype" => "The type of database isn't supported.",
			"sqlpath" => "Database file not found.",
			"badurl" => "Thank you for going to the site from the index page.",
			"loader" => "Error loading file.",
			"configpath" => "Configuration file not found.",
			"makestyle" => "Error reading template.",
			"makestyleconfig" => "Error in the configuration templates.",
			"phpversion" => "Database file not found.",
			"sqlpath" => "Sorry, your version of php is too bad."
		);
		
		if (isset($errorMessageTitle[$cmd])) {
			return $errorMessageTitle[$cmd];
		} else {
			return "Stop loading.";
		}
	}
}

?>