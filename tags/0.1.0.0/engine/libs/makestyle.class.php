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
	private static $currentTemplate = "default";
	
	/**
	 * Nom du fichier template
	 * 
	 * @var String
	 */ 
	private $fileName = "";
	
	/**
	 * Variables assignées
	 * 
	 * @var array
	 */ 
	private $fileVars = array();
	
	/**
	 * Indique si l'instance courante est en mode debug
	 * 
	 * @var boolean
	 */
	private $debugMode = false;
	
	public function __construct($fileName = "") {
		// Mode normale
		$this->debugMode = false;
		
		// Si le nom du template est précisé a la construction
		if (!empty($fileName)) {
			$this->fileName = $fileName;
		}
		
		if (empty(self::$templatesDir) || empty(self::$currentTemplate)) {
			Core_Secure::getInstance()->debug("makeStyleConfig");
		}
	}
	
	/**
	 * Assigne le nom du template et vérifie ca validité
	 * Affiche une erreur si détecté
	 * 
	 * @param $fileName String
	 */
	private function checkTemplate($fileName = "") {
		if (!empty($fileName)) $this->fileName = $fileName;
		
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
		$this->fileVars[$key] = is_object($value) ? $value->display() : $value;
	}
	
	/**
	 * Execute et affiche le template
	 * 
	 * @param $fileName String
	 * @return $output L'affichage finale du template
	 */
	public function display($fileName = "") {
		echo $this->render($fileName);
	}
	
	/**
	 * Execute et affiche le template en mode debug
	 * Si le fichier de template debug n'est pas trouvé, le fichier debug par défaut est utilisé
	 * 
	 * @param $fileName String
	 */
	public function displayDebug($fileName = "") {
		echo $this->renderDebug($fileName);
	}
	
	/**
	 * Retourne le rendu du template
	 * 
	 * @param $fileName String
	 * @return String
	 */
	public function &render($fileName = "") {
		// Vérifie le template
		$this->checkTemplate($fileName);
		
		// Extrait les variables en local
		extract($this->fileVars);
		
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
	 * @param $fileName String
	 * @return String
	 */
	public function &renderDebug($fileName = "") {
		if (!empty($fileName)) $this->fileName = $fileName;
		
		// Si le template ne contient pas le fichier debug
		if (!$this->isTemplate()) {
			// Activation du mode debug
			$this->debugMode = true;
		}
		return $this->render($fileName);
	}
	
	/**
	 * Retourne le chemin jusqu'au template
	 * 
	 * @return path String
	 */
	private function &getTemplatePath() {
		// Si le mode debug est activé, on utilise le fichier par défaut
		if ($this->debugMode) return TR_ENGINE_DIR . "/engine/libs/makestyle.debug.tpl";
		else return TR_ENGINE_DIR . "/" . self::$templatesDir . "/" . self::$currentTemplate . "/" . $this->fileName;
	}
	
	/**
	 * Vérifie la validité du template
	 * 
	 * @return boolean true si le chemin du template est valide
	 */
	private function &isTemplate() {
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
	 * @param $currentTemplate String
	 */
	public static function setCurrentTemplate($currentTemplate) {
		if (is_dir(TR_ENGINE_DIR . "/" . self::$templatesDir . "/" . $currentTemplate)) {
			self::$currentTemplate = $currentTemplate;
		}		
	}
	
	/**
	 * Retourne le dossier vers les templates
	 * 
	 * @return String
	 */
	public static function &getTemplatesDir() {
		return self::$templatesDir;
	}
	
	/**
	 * Retourne le dossier du template utilisé
	 * 
	 * @return String
	 */
	public static function &getCurrentTemplate() {
		return self::$currentTemplate;
	}
	
	/**
	 * Retourne la liste de templates disponibles
	 * 
	 * @return array
	 */
	public static function &listTemplates() {
		$templates = array();
		$dirs = Core_CacheBuffer::listNames(self::$templatesDir);
		return $dirs;
	}
}
?>