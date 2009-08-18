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
	 * Compteur de visites du module
	 * 
	 * @var int
	 */
	public static $count;
	
	/**
	 * Module compilé
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
				Core_Exception::setMinorError(ERROR_404);
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
	 * Création et récuperation de l'instance du module
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
	 * @param $module String le nom du module, par défaut le module courant
	 * @return mixed tableau array d'informations ou boolean false si echec
	 */
	public function getInfoModule($module = "") {
		// Nom du module cible 
		$modName = ((empty($module)) ? self::$module : $module);
		
		// Retourne le buffer
		if (isset($this->modules[$modName])) return $this->modules[$modName];
		
		Core_Sql::select(
			Core_Table::$MODULES_TABLE,
			array("mod_id", "rang", "configs", "count"),
			array("name =  '" . $modName . "'")			
		);
		
		if (Core_Sql::affectedRows() > 0) {
			list($modId, $rang, $configs, $count) = Core_Sql::fetchArray();
			
			$configs = explode("|", $configs);
			
			if (self::$module == $modName) {
				self::$modId = $modId;
				self::$rang = $rang;
				self::$configs = $configs;
				self::$count = $count;
			}
			$this->modules[$modName] = array(
				"modId" => $modId,
				"rang" => $rang,
				"configs" => $configs,
				"count" => $count
			);
			return $this->modules[$modName];
		}
		// Insert la variable vide car aucune donnée
		$this->modules[$modName] = "";
		return false;
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
		// Vérification du niveau d'acces 
		if (Core_Acces::autorize(self::$module)) {
			if (empty($this->moduleCompiled) && $this->isModule()) {		
				// Execution du module
				$moduleClassName = "Module_" . ucfirst(self::$module) . "_" . ucfirst(self::$page);
				$loaded = Core_Loader::classLoader($moduleClassName);
				
				if ($loaded) {
					// Configuration du view demandé
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
						Core_Translate::translate("modules/" . self::$module);
						$ModuleClass = new $moduleClassName();
						
						// Capture des données d'affichage
						ob_start();
						$ModuleClass->$viewPage();
						$this->moduleCompiled = ob_get_contents();
						ob_end_clean();
					} else {echo $viewPage;
						Core_Exception::setMinorError(ERROR_MODULE_CODE . " (" . self::$module . ")");
					}
				}
			}
		} else {
			Core_Exception::setMinorError(ERROR_ACCES_ZONE . " " . Core_Acces::getModuleAccesError(self::$module));
		}
	}
	
	/**
	 * Vérifie si le module existe
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
	 * Retourne le module compilé
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


?>