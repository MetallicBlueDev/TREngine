<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Utilitaire d'entête et de contenu HTML
 * 
 * @author Sebastien Villemain
 *
 */
class Core_Html {
	
	/**
	 * Instance de la classe Core_Html
	 * 
	 * @var Core_Html
	 */
	private static $html = false;
	
	/**
	 * Nom du cookie de test
	 * 
	 * @var String
	 */
	private $cookieTestName = "test";
	
	/**
	 * Etat du javaScript chez le client
	 * 
	 * @var boolean
	 */
	private $javaScriptActived = false;
	
	/**
	 * Fonctions et codes javascript demandées
	 * 
	 * @var String
	 */
	private $javaScriptCode = "";
	
	/**
	 * Codes javascript JQUERY demandées
	 * 
	 * @var String
	 */
	private $javaScriptJquery = "";
	
	/**
	 * Fichiers de javascript demandées
	 * 
	 * @var array
	 */
	private $javaScriptFile = array();
	
	/**
	 * Fichier de style CSS demandées
	 * 
	 * @var array
	 */
	private $cssFile = array();
	
	/**
	 * Titre de la page courante
	 * 
	 * @var String
	 */
	private $title = "";
	
	/**
	 * Mots clès de la page courante
	 * 
	 * @var array
	 */
	private $keywords = array();
	
	/**
	 * Description de la page courante
	 * 
	 * @var String
	 */
	private $description = "";
	
	public function __construct() {
		// Configuration du préfixe accessible
		if (Core_Loader::isCallable("Core_Main")) $prefix = Core_Main::$coreConfig['cookiePrefix'];
		else $prefix = "tr";
		
		// Composition du nom du cookie de test
		$this->cookieTestName = Exec_Crypt::cryptData(
			$prefix . "_" . $this->cookieTestName, 
			$this->getSalt(), "md5"
		);
		// Vérification du javascript du client
		$this->checkJavaScriptActived();
	}
	
	/**
	 * Retourne et si besoin créé l'instance Core_Html
	 * 
	 * @return Core_Html
	 */
	public static function &getInstance() {
		if (self::$html === false) {
			self::$html = new self();
		}
		return self::$html;
	}
	
	/**
	 * Detection du javascript chez le client
	 */
	private function checkJavaScriptActived() {
		// Récuperation du cookie en php
		$cookieTest = Exec_Cookie::getCookie($this->cookieTestName);
		// Vérification de l'existance du cookie
		$this->javaScriptActived = ($cookieTest == 1) ? true : false;
	}
	
	/**
	 * Retourne les scripts à inclure
	 * 
	 * @return String
	 */
	private function includeJavaScript() {
		if (Core_Request::getRequest() != "POST") {			
			if (Core_Loader::isCallable("Core_Main")) $fullScreen = Core_Main::isFullScreen();
			else $fullScreen = true;
			
			if ($fullScreen && $this->isJavaScriptActived()) {
				if (!empty($this->javaScriptJquery)) $this->addJavaScriptFile("jquery.js");
				$this->addJavaScriptFile("tr_engine.js");
			} else {
				// Tous fichier inclus est superflue donc reset
				$this->resetJavaScript();
			}
			
			$this->addJavaScriptFile("javascriptactived.js");
			
			if (Core_Loader::isCallable("Exec_Agent") && Exec_Agent::$userBrowserName == "Internet Explorer" && Exec_Agent::$userBrowserVersion < "7") {
				$this->addJavaScriptFile("pngfix.js", "defer");
			}
		} else {
			$this->resetJavaScript();
		}
		
		// Conception de l'entête
		$script = "";
		foreach($this->javaScriptFile as $file => $options) {
			if (!empty($options)) $options = " " . $options;
			if ($file == "jquery.js") {
				$script = "<script" . $options . " type=\"text/javascript\" src=\"includes/js/" . $file . "\"></script>\n" . $script;
			} else {
				$script .= "<script" . $options . " type=\"text/javascript\" src=\"includes/js/" . $file . "\"></script>\n";
			}
		}
		return $script;
	}
	
	/**
	 * Retourne les fichiers de css à inclure
	 * 
	 * @return String
	 */
	private function includeCss() {
		// Conception de l'entête
		$script = "";
		foreach($this->cssFile as $file => $options) {
			if (!empty($options)) $options = " " . $options;
			$script .= "<link rel=\"stylesheet\" href=\"includes/css/" . $file . "\" type=\"text/css\" />\n";			
		}
		return $script;
	}
	
	/**
	 * Execute les fonctions javascript demandées
	 * 
	 * @return String
	 */
	private function executeJavaScript() {
		$script .= "<script type=\"text/javascript\">\n"
		. "javaScriptActived('" . $this->cookieTestName . "');\n";
		
		if (!empty($this->javaScriptCode)) {
			$script .= $this->javaScriptCode;
		}
		
		if (!empty($this->javaScriptJquery)) {
			$script .= "$(document).ready(function(){";
			$script .= $this->javaScriptJquery;
			$script .= "});";
		}
		
		$script .= "</script>\n";
		return $script;
	}
	
	/**
	 * Ajoute un code javaScript JQUERY à executer
	 * 
	 * @param $javaScript String
	 */
	public function addJavaScriptJquery($javaScript) {
		if (!empty($this->javaScriptJquery)) $this->javaScriptJquery .= "\n";
		$this->javaScriptJquery .= $javaScript;
	}
	
	/**
	 * Ajoute un code javaScript à executer
	 * 
	 * @param $javaScript String
	 */
	public function addJavaScriptCode($javaScript) {
		if (!empty($this->javaScriptCode)) $this->javaScriptCode .= "\n";
		$this->javaScriptCode .= $javaScript;
	}
	
	/**
	 * Ajoute un fichier javascript a l'entête
	 * 
	 * @param $fileName String
	 * @param $options String
	 */
	public function addJavaScriptFile($fileName, $options = "") {
		if (!array_key_exists($fileName, $this->javaScriptFile)) {
			$this->javaScriptFile[$fileName] = $options;
		}
	}
	
	/**
	 * Ajoute un fichier de style CSS a l'entête
	 * 
	 * @param $fileName String
	 * @param $options String
	 */
	public function addCssFile($fileName, $options = "") {
		if (!array_key_exists($fileName, $this->cssFile)) {
			$this->cssFile[$fileName] = $options;
		}
	}
	
	/**
	 * Reset des codes et fichier inclus javascript
	 */
	private function resetJavaScript() {
		$this->javaScriptCode = "";
		$this->javaScriptFile = array();
	}
	
	/**
	 * Retourne l'état du javascript du client
	 * 
	 * @return boolean
	 */
	public function isJavaScriptActived() {
		return $this->javaScriptActived;
	}
	
	/**
	 * Retourne l'entête HTML
	 * 
	 * @return String
	 */
	public function getMetaHeaders() {
		$title = "";
		if (Core_Loader::isCallable("Core_Main") && !empty(Core_Main::$coreConfig['defaultSiteName'])) {
			if (empty($this->title)) $title = Core_Main::$coreConfig['defaultSiteName'] . " - " . Core_Main::$coreConfig['defaultSiteSlogan'];
			else $title = Core_Main::$coreConfig['defaultSiteName'] . " - " . $this->title;
		} else {
			if (empty($this->title)) $title = Core_Request::getString("SERVER_NAME", "", "SERVER");
			else $title = Core_Request::getString("SERVER_NAME", "", "SERVER") . " - " . $this->title;
		}
		
		Core_Loader::classLoader("Exec_Entities");
		
		return "<title>" . Exec_Entities::textDisplay($title) . "</title>\n"
		. $this->getMetaKeywords()
		. "<meta name=\"generator\" content=\"TR ENGINE\" />\n"
		. "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\n"
		. "<meta http-equiv=\"content-script-type\" content=\"text/javascript\" />\n"
		. "<meta http-equiv=\"content-style-type\" content=\"text/css\" />\n"
		. "<link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"" . Libs_MakeStyle::getTemplatesDir() . "/" . Libs_MakeStyle::getTemplateUsedDir() . "/favicon.ico\" />\n"
		. $this->includeJavaScript()
		. $this->includeCss();
		
		// TODO ajouter un support RSS XML
	}
	
	public function getMetaFooters() {
		return $this->executeJavaScript();
	}
	
	/**
	 * Retourne les mots clès et la description de la page
	 * 
	 * @return String
	 */
	private function getMetaKeywords() {
		$keywords = "";
		if (is_array($this->keywords) && count($this->keywords) > 0) {
			$keywords = implode(", ", $this->keywords);
		}
		
		$keywords = strip_tags($keywords);
		// 500 caractères maximum
		$keywords = (strlen($keywords) > 500) ? substr($keywords, 0, 500) : $keywords;
		
		if (Core_Loader::isCallable("Core_Main")) {
			if (empty($this->description)) $this->description = Core_Main::$coreConfig['defaultDescription'];
			if (empty($keywords)) $keywords = Core_Main::$coreConfig['defaultKeyWords'];
		}
		
		Core_Loader::classLoader("Exec_Entities");
		
		return "<meta name=\"description\" content=\"" . Exec_Entities::textDisplay($this->description) . "\" />\n"
		. "<meta name=\"keywords\" content=\"" . Exec_Entities::textDisplay($keywords) . "\" />\n";
	}
	
	/**
	 * Affecte le titre a la page courante
	 * 
	 * @param $title String
	 */
	public function setTitle($title) {
		$this->title = strip_tags($title);
	}
	
	/**
	 * Affecte les mots clès de la page courante
	 * 
	 * @param $keywords array or String : un tableau de mots clès prets ou une phrase
	 */
	public function setKeywords($keywords) {
		if (is_array($keywords)) {
			// Les mots cles sont déjà tous prets
			if (count($this->keywords) > 0) {
				array_push($this->keywords, $keywords);
			} else {
				$this->keywords = $keywords;
			}
		} else {
			// Une chaine de caratères (phrase ou simple mots clès)
			$keywords = str_replace(",", " ", $keywords);
			$keywords = explode(" ", $keywords);
			foreach($keywords as $keyword) {
				if (!empty($keyword)) { // Filtre les entrées vides
					$this->keywords[] = trim($keyword);
				}
			}
		}
	}
	
	/**
	 * Affecte la description de la page courante
	 * 
	 * @param $description String
	 */
	public function setDescription($description) {
		$this->description = strip_tags($description);
	}
	
	/**
	 * Retourne la combinaison de cles pour le salt
	 * 
	 * @return String
	 */
	private function getSalt() {
		// Configuration de la clès accessible
		if (Core_Loader::isCallable("Core_Main")) $key = Core_Main::$coreConfig['cryptKey'];
		else $key = "A4bT9D4V";
		return $key;
	}
	
	/**
	 * Réécriture d'une URL
	 * 
	 * @param $link String or array adresse URL a réécrire
	 * @param $layout boolean true ajouter le layout
	 * @return String or array
	 */
	public static function getLink($link, $layout = false) {
		if (is_array($link)) {
			foreach ($link as $key => $value) {
				$link[$key] = self::getLink($value, $layout);
			}
		} else {
			if ($layout) {
				// Configuration du layout
				$layout = "&amp;layout=";
				if (strpos($link, "block=") !== false) {
					$layout .= "block";
				} else if (strpos($link, "mod=") !== false) {
					$layout .= "module";
				} else {
					$layout .= "default";
				}
				$link .= $layout;
			}
			if (strpos($link, "index.php?") === false) {
				if ($link[0] == "?") {
					$link = "index.php" . $link;
				} else {
					$link = "index.php?" . $link;
				}
			}
			if (Core_Main::doUrlRewriting()) {
				$link = Core_UrlRewriting::rewriteLink($link);
			}
		}
		return $link;
	}
}
?>