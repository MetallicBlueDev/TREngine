<?php
if (preg_match("/makestyle.class.php/ie", $_SERVER['PHP_SELF'])) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Make Style, moteur de template PHP
 * RAPIDE, SIMPLE ET EFFICACE !
 * 
 * @author S�bastien Villemain
 *
 */
class Libs_MakeStyle {
	
	/**
	 * Dossier contenant le cache
	 */
	private static $cacheDir = "tmp/templates";
	
	/**
	 * Dossier contenant les templates
	 */ 
	private static $templatesDir = "templates";
	
	/**
	 * Nom du dossier du template utilis�
	 */ 
	private static $templateUsedDir = "default";
	
	/**
	 * Nom du fichier template
	 */ 
	private $templateName;
	
	/**
	 * Variables assign�es
	 */ 
	private $templateVars = array();
	
	/**
	 * Indique si l'instance courante est en mode debug
	 */
	private $debugMode = false;
	
	public function Libs_MakeStyle() {
		$this->__construct();
	}
	
	public function __construct($templateName = "") {
		// Mode normale
		$this->debugMode = false;
		
		// Si le nom du template est d�j� pr�cis�
		if ($templateName != "") $this->templateName = $templateName;
		
		if (!self::$cacheDir 
				|| self::$cacheDir == "/"
				|| !self::$templatesDir
				|| self::$templatesDir == "/") {
			Core_Secure::getInstance()->debug("makeStyleConfig");
		}
	}
	
	/**
	 * Assigne le nom du template et v�rifie ca validit�
	 * Affiche une erreur si d�tect�
	 * 
	 * @param $templateName String
	 */
	private function checkTemplate($templateName = "") {
		if ($templateName != "") $this->templateName = $templateName;
		
		if (!$this->isTemplate()) {
			Core_Secure::getInstance()->debug("makeStyle", $this->getTemplatePath());
		}		
	}
	
	/**
	 * Assigne une valeur au template
	 * 
	 * @param $key Nome de la variable
	 * @param $value Valeur de la variable
	 * @return Libs_MakeStyle
	 */
	public function assign($key, $value) {
		$this->templateVars[$key] = is_object($value) ? $value->display() : $value;
		return $this;
	}
	
	/**
	 * Execute et affiche le template
	 * 
	 * @param $templateName String
	 * @return $output L'affichage finale du template
	 */
	public function display($templateName = "") {
		
		// V�rifie le template
		$this->checkTemplate($templateName);
		
		// Extrait les variables en local
		extract($this->templateVars);
		
		// Traitement du template
		ob_start();
		include($this->getTemplatePath());
		$output = ob_get_contents();
		ob_end_clean();
		
		echo $output;
	}
	
	/**
	 * Execute et affiche le template en mode debug
	 * Si le fichier de template debug n'est pas trouv�, le fichier debug par d�faut est utilis�
	 * 
	 * @param $templateName String
	 */
	public function displayDebug($templateName = "") {
		if ($templateName != "") $this->templateName = $templateName;
		
		// Si le template ne contient pas le fichier debug
		if (!$this->isTemplate()) {
			// Activation du mode debug
			$this->debugMode = true;
		}
		
		// Affichage du template avec le mode debug si besoin
		$this->display();
	}
	
	/**
	 * Retourne le chemin jusqu'au template
	 * @return path String
	 */
	private function getTemplatePath() {
		// Si le mode debug est activ�, on utilise le fichier par d�faut
		if ($this->debugMode) return TR_ENGINE_DIR . "/engine/libs/debug.tpl";
		else return TR_ENGINE_DIR . "/" . self::$templatesDir . "/" . self::$templateUsedDir . "/" . $this->templateName;
	}
	
	/**
	 * V�rifie la validit� du template
	 * @return boolean true si le chemin du template est valide
	 */
	private function isTemplate() {
		return @is_file($this->getTemplatePath());
	}
	
	/**
	 * Configure le dossier cache du template
	 * 
	 * @param $cacheDir String
	 */
	public static function setCacheDir($cacheDir) {
		self::$cacheDir = $cacheDir;
	}
	
	/**
	 * Configure le dossier contenant les templates
	 * 
	 * @param $templatesDir String
	 */
	public static function setTemplatesDir($templatesDir) {
		self::$templatesDir = $templatesDir;
	}
	
	/**
	 * Configure le dossier du template courament utilis�
	 * 
	 * @param $templateUsedDir String
	 */
	public static function setTemplateUsedDir($templateUsedDir) {
		self::$templateUsedDir = $templateUsedDir;
	}
	
	/**
	 * Retourne le dossier du cache configur�
	 * @return String
	 */
	public static function getCacheDir() {
		return self::$cacheDir;
	}
	
	/**
	 * Retourne le dossier vers les templates
	 * @return String
	 */
	public static function getTemplatesDir() {
		return self::$templatesDir;
	}
	
	/**
	 * Retourne le dossier du template utilis�
	 * @return String
	 */
	public static function getTemplateUsedDir() {
		return self::$templateUsedDir;
	}
}
?>