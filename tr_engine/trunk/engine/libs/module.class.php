<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de module
 * 
 * @author Sebastien Villemain
 *
 */
class Libs_Module {
	
	/**
	 * Instance du gestionnaire de module
	 * 
	 * @var Libs_Module
	 */
	private static $libsModule = false;
	
	/**
	 * Nom du module courant
	 * 
	 * @var String
	 */
	public static $module = "";
	
	/**
	 * Id du module
	 * 
	 * @var int
	 */	
	public static $modId = "";
	
	/**
	 * Rang du module
	 * 
	 * @var int
	 */
	public static $rang;
	
	/**
	 * Configuration du module
	 * 
	 * @var array
	 */
	public static $configs = array();
	
	/**
	 * Module compil�
	 * 
	 * @var String
	 */
	private $moduleCompiled = "";
	
	/**
	 * Nom de la page courante
	 * 
	 * @var String
	 */
	public static $page = "";
	
	/**
	 * Nom du viewer courant
	 * 
	 * @var String
	 */
	public static $view = "";
	
	/**
	 * Tableau d'information sur les modules extrait
	 * 
	 * @var array
	 */
	private $modules = array();
	
	private $defaultModule = "";
	private $defaultPage = "";
	private $defaultView = "";
	
	public function __construct() {
		$this->defaultModule = Core_Main::$coreConfig['defaultMod'];
		$this->defaultPage = "index";
		$this->defaultView = "display";
		
		if (!empty(self::$module) && empty(self::$page)) {
			self::$page = $this->defaultPage;
		}
		
		// Erreur dans la configuration
		if (!$this->isModule()) {
			if (self::$module != "" || self::$page != "") {
				// Afficher une erreur 404
				Core_Exception::setInfoError(ERROR_404);
			}
			self::$module = $this->defaultModule;
			self::$page = $this->defaultPage;
			self::$view = $this->defaultView;
		}
		if (empty(self::$view)) {
			self::$view = $this->defaultView;
		}
	}
	
	/**
	 * Cr�ation et r�cuperation de l'instance du module
	 * 
	 * @return Libs_Module
	 */
	public static function &getInstance($module = "", $page = "", $view = "") {
		if (self::$libsModule === false) {
			// Injection des informations
			self::$module = $module;
			self::$page = $page;
			self::$view = $view;
			
			self::$libsModule = new self();
		}
		return self::$libsModule;
	}
	
	/**
	 * Retourne les informations du module cible
	 * 
	 * @param $module String le nom du module, par d�faut le module courant
	 * @return mixed tableau array d'informations ou boolean false si echec
	 */
	public function getInfoModule($module = "") {
		// Nom du module cible 
		$modName = ((empty($module)) ? self::$module : $module);
		
		// Retourne le buffer
		if (isset($this->modules[$modName])) {
			return $this->modules[$modName];
		}
		
		$moduleInfo = array();
		
		// Recherche dans le cache
		Core_CacheBuffer::setSectionName("modules");
		if (!Core_CacheBuffer::cached($modName . ".php")) {
			Core_Sql::select(
				Core_Table::$MODULES_TABLE,
				array("mod_id", "rang", "configs"),
				array("name =  '" . $modName . "'")			
			);
		
			if (Core_Sql::affectedRows() > 0) {
				$moduleInfo = Core_Sql::fetchArray();
				$moduleInfo['configs'] = explode("|", $moduleInfo['configs']);
				$configs = array();
				foreach($moduleInfo['configs'] as $value) {
					$value = explode("=", $value);
					$configs[$value[0]] = $value[1];
				}
				$moduleInfo['configs'] = $configs;
				
				// Mise en cache
				$content = Core_CacheBuffer::linearizeCache($moduleInfo);
				Core_CacheBuffer::writingCache($modName . ".php", $content);
			}
		} else {
			$moduleInfo = Core_CacheBuffer::getCache($modName . ".php");
		}
		
		// V�rification des informations
		if (count($moduleInfo) >= 3) {			
			// Injection des informations du module
			if (self::$module == $modName) {
				self::$modId = $moduleInfo['mod_id'];
				self::$rang = $moduleInfo['rang'];
				self::$configs = $moduleInfo['configs'];
			}
			$this->modules[$modName] = array(
				"modId" => $moduleInfo['mod_id'],
				"rang" => $moduleInfo['rang'],
				"configs" => $moduleInfo['configs']
			);
			return $this->modules[$modName];
		} else {
			// Insert la variable vide car aucune donn�e
			$this->modules[$modName] = "";
			return false;
		}
	}
	
	/**
	 * Retourne le rang du module
	 * 
	 * @param $mod String
	 * @return int
	 */
	public function getRang($mod) {
		// Recherche des infos du module
		$moduleInfo = $this->getInfoModule($mod);
		return $moduleInfo['rang'];
	}
	
	/**
	 * Charge le module courant
	 */
	public function launch() {
		// V�rification du niveau d'acces 
		if (Core_Acces::autorize(self::$module)) {
			if (empty($this->moduleCompiled) && $this->isModule()) {		
				// Execution du module
				$moduleClassName = "Module_" . ucfirst(self::$module) . "_" . ucfirst(self::$page);
				$loaded = Core_Loader::classLoader($moduleClassName);
				
				if ($loaded) {
					// Configuration du view demand�
					$viewPage = "";					
					if (Core_Loader::isCallable($moduleClassName, self::$view)) {
						$viewPage = self::$view;
					} else if (self::$view != $this->defaultView && Core_Loader::isCallable($moduleClassName, $this->defaultView)) {
						$viewPage = $this->defaultView;
					} else {
						$viewPage = "";
					}
					// Affichage du module si possible
					if (!empty($viewPage)) {
						$this->updateCount();
						Core_Translate::translate("modules/" . self::$module);
						$ModuleClass = new $moduleClassName();
						$ModuleClass->configs = self::$configs;
						
						// Capture des donn�es d'affichage
						ob_start();
						$ModuleClass->$viewPage();
						$this->moduleCompiled = ob_get_contents();
						ob_end_clean();
					} else {
						Core_Exception::setAlertError(ERROR_MODULE_CODE . " (" . self::$module . ")");
					}
				}
			}
		} else {
			Core_Exception::setAlertError(ERROR_ACCES_ZONE . " " . Core_Acces::getModuleAccesError(self::$module));
		}
	}
	
	/**
	 * Mise � jour du compteur de visite du module courant
	 */
	private function updateCount() {
		Core_Sql::update(
			Core_Table::$MODULES_TABLE,
			array("count" => "count + 1"),
			array("mod_id = '" . self::$modId . "'")
		);
	}
	
	/**
	 * V�rifie si le module existe
	 * 
	 * @param $module String
	 * @param $page String
	 * @return boolean true le module existe
	 */
	public function isModule($module = "", $page = "") {
		if (empty($module)) $module = self::$module;
		if (empty($page)) $page = self::$page;
		return is_file(TR_ENGINE_DIR . "/modules/" . $module . "/" . $page . ".module.php");
	}
	
	/**
	 * Retourne le module compil�
	 * 
	 * @return String
	 */
	public function getModule($rewriteBuffer = false) {
		$buffer = $this->moduleCompiled;
		// Tamporisation de sortie
		if (Core_Main::doUrlRewriting() && ($rewriteBuffer || in_array("rewriteBuffer", self::$configs))) {
			$buffer = Core_UrlRewriting::rewriteBuffer($buffer);
		}
		// Relachement des tampon
		return $buffer;
	}
}

/**
 * Module de base, h�rit� par tous les autres modules
 * Mod�le pour le contenu d'un module
 * 
 * @author Sebastien Villemain
 *
 */
class Module_Model {
	
	/**
	 * Configuration du module
	 * 
	 * @var array
	 */
	private $configs = array();
	
	/**
	 * Id du module
	 * 
	 * @var int
	 */	
	private $modId = 0;
	
	/**
	 * Nom du module courant
	 * 
	 * @var String
	 */
	private $module = "";
	
	/**
	 * Nom de la page courante
	 * 
	 * @var String
	 */
	private $page = "";
	
	/**
	 * Rang du module
	 * 
	 * @var int
	 */
	private $rang = "";
	
	/**
	 * Nom du viewer courant
	 * 
	 * @var String
	 */
	private $view = "";
	
	/**
	 * Fonction d'affichage par d�faut
	 */
	public function display() {
		Core_Exception::setAlertError(ERROR_MODULE_IMPLEMENT . ((!empty($this->module)) ? " (" . $this->module . ")" : ""));
	}
}


?>