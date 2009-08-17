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
	
	public function __construct() {
		if (!empty(self::$module) && empty(self::$page)) {
			self::$page = self::$module;
		}
		
		// Erreur dans la configuration
		if (!$this->isModule()) {
			if (self::$module != "" || self::$page != "") {
				// Afficher une erreur 404
				Core_Exception::setMinorError(ERROR_404);
			}
			self::$module = Core_Main::$coreConfig['defaultMod'];
			self::$page = Core_Main::$coreConfig['defaultMod'];
			self::$view = "";
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
				Core_Translate::translate("modules/" . self::$module);
				
				ob_start();
				Core_Loader::moduleLoader(self::$module . "_" . self::$page);
				$this->moduleCompiled = ob_get_contents();
				ob_end_clean();
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
		return is_file(TR_ENGINE_DIR . "/modules/" . $module . "/" . $page . ".mod.php");
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