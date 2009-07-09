<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

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
	 * Configuration du module
	 * 
	 * @var array
	 */
	public static $moduleConfig = array();
	
	
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
	
	public function __construct() {
		$this->configuration();
	}
	
	/**
	 * Création et récuperation de l'instance du module
	 * 
	 * @return Libs_Module
	 */
	public static function getInstance($module = "", $page = "", $view = "") {
		if (!self::$libsModule) {
			// Injection des informations
			self::$module = $module;
			self::$page = $page;
			self::$view = $view;
			
			self::$libsModule = new self();
		}
		return self::$libsModule;
	}
	
	/**
	 * Configure l'instance du module
	 */
	private function configuration() {
		
		// Vérification de la page courante
		if ((self::$module != "" && !self::$page && !is_dir(TR_ENGINE_DIR . "/modules/" . self::$module))
				|| (self::$module != "" && self::$page != "" && !is_file(TR_ENGINE_DIR . "/modules/" . self::$module . "/" . self::$page . ".php"))
				|| (!self::$module)) {
			// Afficher une erreur 404
			Core_Exception::setMinorError(ERROR_404);
			self::$module = Core_Main::$coreConfig['defaultMod'];
			self::$page = Core_Main::$coreConfig['defaultMod'];
			self::$view = "";
		}
	}
	
	/**
	 * Charge le module courant
	 */
	public function launch() {
		// Vérification du niveau d'acces 
		if (Core_Acces::mod(self::$module)) {
			if (!$this->moduleCompiled) {
				// Vérification du module
				if (is_file(TR_ENGINE_DIR . "/modules/" . self::$module . "/" . self::$page . ".php")) {
					Core_Translate::translate("modules/" . self::$module);
					
					ob_start();
					Core_Loader::moduleLoader(self::$module . "_" . self::$page);
					self::$moduleContent = ob_get_contents();
					ob_end_clean();
				} else {
					Core_Exception::setMinorError("404: module no found: unknown error!");
				}
			}
		} else {
			Core_Exception::setMinorError(ERROR_ACCES_ZONE . " " . Core_Acces::getError(self::$module));
		}
	}
	
	/**
	 * Retourne le module compilé
	 * 
	 * @return String
	 */
	public function getModule() {
		return $this->moduleCompiled;
	}
}


?>