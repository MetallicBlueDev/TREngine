<?php
if (!defined("TR_ENGINE_INDEX")) {
	exit();
}

/**
 * Make Style, moteur de template PHP
 * RAPIDE, SIMPLE ET EFFICACE !
 * 
 * @author Sébastien Villemain
 *
 */
class Libs_MakeStyle {
	
	/**
	 * Dossier contenant les templates
	 * 
	 * @var String
	 */ 
	private static $templatesDir = "templates";
	
	/**
	 * Nom du dossier du template utilisé
	 * 
	 * @var String
	 */ 
	private static $templateUsedDir = "default";
	
	/**
	 * Nom du fichier template
	 * 
	 * @var String
	 */ 
	private $templateName = "";
	
	/**
	 * Variables assignées
	 * 
	 * @var array
	 */ 
	private $templateVars = array();
	
	/**
	 * Indique si l'instance courante est en mode debug
	 * 
	 * @var boolean
	 */
	private $debugMode = false;
	
	public function __construct($templateName = "") {
		// Mode normale
		$this->debugMode = false;
		
		// Si le nom du template est précisé a la construction
		if ($templateName != "") {
			$this->templateName = $templateName;
		}
		
		if (empty(self::$templatesDir) || empty(self::$templateUsedDir)) {
			Core_Secure::getInstance()->debug("makeStyleConfig");
		}
	}
	
	/**
	 * Assigne le nom du template et vérifie ca validité
	 * Affiche une erreur si détecté
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
		echo $this->render($templateName);
	}
	
	/**
	 * Execute et affiche le template en mode debug
	 * Si le fichier de template debug n'est pas trouvé, le fichier debug par défaut est utilisé
	 * 
	 * @param $templateName String
	 */
	public function displayDebug($templateName = "") {
		echo $this->renderDebug($templateName);
	}
	
	/**
	 * Retourne le rendu du template
	 * 
	 * @param $templateName String
	 * @return String
	 */
	public function render($templateName = "") {
		// Vérifie le template
		$this->checkTemplate($templateName);
		
		// Extrait les variables en local
		extract($this->templateVars);
		
		// Traitement du template
		ob_start();
		include($this->getTemplatePath());
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	/**
	 * Active le mode debug si besoin et retourne le rendu du template
	 * 
	 * @param $templateName String
	 * @return String
	 */
	public function renderDebug($templateName = "") {
		if ($templateName != "") $this->templateName = $templateName;
		
		// Si le template ne contient pas le fichier debug
		if (!$this->isTemplate()) {
			// Activation du mode debug
			$this->debugMode = true;
		}
		return $this->render($templateName);
	}
	
	/**
	 * Retourne le chemin jusqu'au template
	 * 
	 * @return path String
	 */
	private function getTemplatePath() {
		// Si le mode debug est activé, on utilise le fichier par défaut
		if ($this->debugMode) return TR_ENGINE_DIR . "/engine/libs/makestyle.debug.tpl";
		else return TR_ENGINE_DIR . "/" . self::$templatesDir . "/" . self::$templateUsedDir . "/" . $this->templateName;
	}
	
	/**
	 * Vérifie la validité du template
	 * 
	 * @return boolean true si le chemin du template est valide
	 */
	private function isTemplate() {
		return is_file($this->getTemplatePath());
	}
	
	/**
	 * Configure le dossier contenant les templates
	 * 
	 * @param $templatesDir String
	 */
	public static function setTemplatesDir($templatesDir) {
		if (is_dir(TR_ENGINE_DIR . "/" . $templatesDir)) {
			self::$templatesDir = $templatesDir;
		}
	}
	
	/**
	 * Configure le dossier du template courament utilisé
	 * 
	 * @param $templateUsedDir String
	 */
	public static function setTemplateUsedDir($templateUsedDir) {
		if (is_dir(TR_ENGINE_DIR . "/" . self::$templatesDir . "/" . $templateUsedDir)) {
			self::$templateUsedDir = $templateUsedDir;
		}		
	}
	
	/**
	 * Retourne le dossier vers les templates
	 * 
	 * @return String
	 */
	public static function getTemplatesDir() {
		return self::$templatesDir;
	}
	
	/**
	 * Retourne le dossier du template utilisé
	 * 
	 * @return String
	 */
	public static function getTemplateUsedDir() {
		return self::$templateUsedDir;
	}
}
?>