<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

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
	private $javaScript = "";
	
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
		if (class_exists("Core_Main")) $prefix = Core_Main::$coreConfig['cookiePrefix'];
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
	public static function getInstance() {
		if (!self::$html) {
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
	 * Retourne le script de detection javascript
	 * 
	 * @return String
	 */
	private function includeJavaScript() {
		$script = "";
		if ($_SERVER['REQUEST_METHOD'] != "POST") {
			$script .= "<script type=\"text/javascript\" src=\"includes/js/javascriptactived.js\"></script>\n";
			
			if (class_exists("Core_Main")) $fullScreen = Core_Main::isFullScreen();
			else $fullScreen = true;
			
			if ($fullScreen && $this->isJavaScriptActived()) {
				$script .= "<script type=\"text/javascript\" src=\"includes/js/tr_engine.js\"></script>\n"
				. "<script type=\"text/javascript\" src=\"includes/js/jquery.js\"></script>\n";
			}
		}
		return $script;
	}
	
	/**
	 * Execute les fonctions javascript demandées
	 * 
	 * @return String
	 */
	private function executeJavaScript() {
		$script = "";
		$script .= "<script type=\"text/javascript\">\n"
		. "javaScriptActived('" . $this->cookieTestName . "');\n";
		
		if (class_exists("Core_Main")) $fullScreen = Core_Main::isFullScreen();
		else $fullScreen = true;		
		
		if ($fullScreen && $this->isJavaScriptActived() && $this->javaScript != "") {
			$script .= $this->javaScript . "\n";
		}
		
		$script .= "</script>\n";
		
		return $script;
	}
	
	/**
	 * Ajoute un code javaScript à executer
	 * 
	 * @param $javaScript String
	 */
	public function addJavaScript($javaScript) {
		if ($this->javaScript != "") $this->javaScript .= "\n";
		$this->javaScript .= $javaScript;
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
		if (class_exists("Core_Main") && Core_Main::$coreConfig['defaultSiteName'] != "") {
			if (!$this->title) $title = Core_Main::$coreConfig['defaultSiteName'] . " - " . Core_Main::$coreConfig['defaultSiteSlogan'];
			else $title = Core_Main::$coreConfig['defaultSiteName'] . " - " . $this->title;
		} else {
			if (!$this->title) $title = $_SERVER['SERVER_NAME'];
			else $title = $_SERVER['SERVER_NAME'] . " - " . $this->title;
		}
		
		Core_Loader::classLoader("Exec_Entities");
		
		return "<title>" . Exec_Entities::textDisplay($title) . "</title>\n"
		. $this->getMetaKeywords()
		. "<meta name=\"generator\" content=\"TR ENGINE\" />\n"
		. "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\n"
		. "<meta http-equiv=\"content-script-type\" content=\"text/javascript\" />\n"
		. "<meta http-equiv=\"content-style-type\" content=\"text/css\" />\n"
		. "<link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"" . Libs_MakeStyle::getTemplatesDir() . "/" . Libs_MakeStyle::getTemplateUsedDir() . "/favicon.ico\" />\n"
		. $this->includeJavaScript();
		
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
		
		if (class_exists("Core_Main")) {
			if (!$this->description) $this->description = Core_Main::$coreConfig['defaultDescription'];
			if (!$keywords) $keywords = Core_Main::$coreConfig['defaultKeyWords'];
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
				if ($keyword != "") { // Filtre les entrées vides
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
		if (class_exists("Core_Main")) $key = Core_Main::$coreConfig['cryptKey'];
		else $key = "A4bT9D4V";
		return $key;
	}
}


?>