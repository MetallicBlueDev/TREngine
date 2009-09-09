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
	
	public function __construct() {
		$defaultModule = Core_Main::$coreConfig['defaultMod'];
		$defaultPage = "index";
		$defaultView = "display";
		
		if (!empty(self::$module) && empty(self::$page)) {
			self::$page = $defaultPage;
		}
		
		// Erreur dans la configuration
		if (!$this->isModule()) {
			if (!empty(self::$module) || !empty(self::$page)) {
				// Afficher une erreur 404
				Core_Exception::addInfoError(ERROR_404);
			}
			self::$module = $defaultModule;
			self::$page = $defaultPage;
			self::$view = $defaultView;
		}
		if (empty(self::$view)) {
			self::$view = $defaultView;
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
	public function &getInfoModule($moduleName = "") {
		// Nom du module cible 
		$moduleName = ((empty($moduleName)) ? self::$module : $moduleName);
		
		// Retourne le buffer
		if (isset($this->modules[$moduleName])) {
			if (is_array($this->modules[$moduleName])) {
				return $this->modules[$moduleName];
			} else {
				return false;
			}
		}
		
		$moduleInfo = array();
		
		// Recherche dans le cache
		Core_CacheBuffer::setSectionName("modules");
		if (!Core_CacheBuffer::cached($moduleName . ".php")) {
			Core_Sql::select(
				Core_Table::$MODULES_TABLE,
				array("mod_id", "rang", "configs"),
				array("name =  '" . $moduleName . "'")			
			);
		
			if (Core_Sql::affectedRows() > 0) {
				$moduleInfo = Core_Sql::fetchArray();
				$moduleInfo['configs'] = explode("|", $moduleInfo['configs']);
				$configs = array();
				foreach($moduleInfo['configs'] as $value) {
					$value = explode("=", $value);
					$configs[$value[0]] = urldecode($value[1]); // Chaine encodé en urlencode
				}
				$moduleInfo['configs'] = $configs;
				
				// Mise en cache
				$content = Core_CacheBuffer::linearizeCache($moduleInfo);
				Core_CacheBuffer::writingCache($moduleName . ".php", $content);
			}
		} else {
			$moduleInfo = Core_CacheBuffer::getCache($moduleName . ".php");
		}
		
		// Vérification des informations
		if (count($moduleInfo) >= 3) {
			// Injection des informations du module
			if (self::$module == $moduleName) {
				self::$modId = $moduleInfo['mod_id'];
				self::$rang = $moduleInfo['rang'];
				self::$configs = $moduleInfo['configs'];
			}
			$this->modules[$moduleName] =& $moduleInfo;
			return $moduleInfo;
		} else {
			// Insert la variable vide car aucune donnée
			$this->modules[$moduleName] = "";
			return false;
		}
	}
	
	/**
	 * Retourne le rang du module
	 * 
	 * @param $mod String
	 * @return int
	 */
	public function getRang($moduleName) {
		$moduleName = (empty($moduleName)) ? self::$module : $moduleName;
		// Recherche des infos du module
		$moduleInfo = $this->getInfoModule($moduleName);
		return $moduleInfo['rang'];
	}
	
	/**
	 * Charge le module courant
	 */
	public function launch() {
		// Vérification du niveau d'acces 
		if (($this->installed() && Core_Acces::autorize(self::$module)) || (!$this->installed() && Core_Session::$userRang > 1)) {
			if (empty($this->moduleCompiled) && $this->isModule()) {		
				$this->get();
				if (Core_Loader::isCallable("Libs_Breadcrumb")) {
					Libs_Breadcrumb::getInstance()->addTrail(self::$module, "?mod=" . self::$module);
					Libs_Breadcrumb::getInstance()->addTrail(self::$view, "?mod=" . self::$module . "&view=" . Libs_Module::$view);
				}
			}
		} else {
			Core_Exception::addAlertError(ERROR_ACCES_ZONE . " " . Core_Acces::getModuleAccesError(self::$module));
		}
	}
	
	/**
	 * Retourne un view valide sinon une chaine vide
	 * 
	 * @param $pageInfo array
	 * @param $setAlternative boolean
	 * @return String
	 */
	private function viewPage($pageInfo, $setAlternative = true) {
		$default = "display";
		if (Core_Loader::isCallable($pageInfo[0], $pageInfo[1])) {
			if ($pageInfo[1] == "install" && ($this->installed() || Core_Session::$userRang < 2)) {
				return $this->viewPage(array($pageInfo[0], $default), false);
			}
			if ($pageInfo[1] == "uninstall" && (!$this->installed() || !Core_Acces::moderate(self::$module))) {
				return $this->viewPage(array($pageInfo[0], $default), false);
			}
			if ($pageInfo[1] == "setting" && (!$this->installed() || !Core_Acces::moderate(self::$module))) {
				return $this->viewPage(array($pageInfo[0], $default), false);
			}
			return $pageInfo[1];
		} else if ($setAlternative && $pageInfo[1] != $default) {
			return $this->viewPage(array($pageInfo[0], $default), false);
		}
		return "";
	}
	
	/**
	 * Récupère le module
	 * 
	 * @param $viewPage String
	 */
	private function get() {
		$moduleClassName = "Module_" . ucfirst(self::$module) . "_" . ucfirst(self::$page);
		$loaded = Core_Loader::classLoader($moduleClassName);
		
		if ($loaded) {
			self::$view = $this->viewPage(array($moduleClassName, ($this->installed()) ? self::$view : "install"), false);
			// Affichage du module si possible
			if (!empty(self::$view)) {
				$this->updateCount();
				$ModuleClass = new $moduleClassName();
				$ModuleClass->configs = self::$configs;
				
				// Capture des données d'affichage
				ob_start();
				$ModuleClass->{self::$view}();
				$this->moduleCompiled = ob_get_contents();
				ob_end_clean();
			} else {
				Core_Exception::addAlertError(ERROR_MODULE_CODE . " (" . self::$module . ")");
			}
		}
	}
	
	/**
	 * Vérifie que le module est installé
	 * 
	 * @param $moduleName String
	 * @return boolean
	 */
	private function installed($moduleName = "") {
		$moduleName = (empty($moduleName)) ? self::$module : $moduleName;
		// Recherche des infos du module
		$moduleInfo = $this->getInfoModule($moduleName);
		return (isset($moduleInfo['mod_id']));
	}
	
	/**
	 * Mise à jour du compteur de visite du module courant
	 */
	private function updateCount() {
		Core_Sql::update(
			Core_Table::$MODULES_TABLE,
			array("count" => "count + 1"),
			array("mod_id = '" . self::$modId . "'")
		);
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
	public function &getModule($rewriteBuffer = false) {
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
 * Module de base, hérité par tous les autres modules
 * Modèle pour le contenu d'un module
 * 
 * @author Sebastien Villemain
 *
 */
abstract class Module_Model {
	
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
	 * Fonction d'affichage par défaut
	 */
	public function display() {
		Core_Exception::addAlertError(ERROR_MODULE_IMPLEMENT . ((!empty($this->module)) ? " (" . $this->module . ")" : ""));
	}
	
	/**
	 * Installation du module courant
	 */
	public function install() {
		Core_Sql::insert(
			Core_Table::$MODULES_TABLE,
			array("name", "rang", "configs"),
			array(Libs_Module::$module, 0, "")
		);
	}
	
	/**
	 * Désinstallation du module courant
	 */
	public function uninstall() {
		Core_Sql::delete(
			Core_Table::$MODULES_TABLE,
			array("mod_id = '" . $this->modId . "'")
		);
		Core_CacheBuffer::setSectionName("modules");
		Core_CacheBuffer::removeCache(Libs_Module::$module . ".php");
	}
	
	/**
	 * Configuration du module courant
	 */
	public function setting() {
		// TODO mettre un forumlaire basique pour changer quelque configuration
	}
}


?>