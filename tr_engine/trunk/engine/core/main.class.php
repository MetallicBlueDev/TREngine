<?php
if (preg_match("/main.class.php/ie", $_SERVER['PHP_SELF'])) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Classe principal du moteur
 * 
 * @author Sébastien Villemain
 *
 */
class Core_Main {
	
	/**
	 * Instance du gestionnaire SQL
	 */ 
	public static $coreSql;
	
	/**
	 * Tableau d'information de configuration
	 */ 
	public static $coreConfig = array();
	
	/**
	 * Gestionnaire des sessions
	 */
	private $coreSession;
	
	public function __construct() {
		$this->starter();
	}
	
	/**
	 * Capture du fichier de configuration
	 */
	private function starter() {
		// Vérification de la version PHP
		if (TR_ENGINE_PHP_VERSION < "5.0.0") {
			Core_Secure::getInstance()->debug("phpVersion");
		}
		
		// Charge le gestionnaire d'exception
		Core_Loader::classLoader("Core_Exception");
		
		// Charge les constantes de table
		Core_Loader::classLoader("Core_Table");
		
		// Chargement du gestionnaire de cache
		Core_Loader::classLoader("Core_CacheBuffer");
		
		// Chargement de la configuration
		Core_Loader::classLoader("Core_ConfigsLoader");
		$coreConfigLoader = new Core_ConfigsLoader();
		
		// Récuperation de la configuration
		$this->setCoreConfig($coreConfigLoader->getConfig());
		
		// Connexion à la base de donnée
		$this->setCoreSql($coreConfigLoader->getDatabase());
		
		// Destruction du chargeur de configs
		unset($coreConfigLoader);
		
		// TODO isoler l'installation
		$installPath = TR_ENGINE_DIR . "/install/index.php";
		if (is_file($installPath)) {
			require($installPath);
		}
	}
	
	/**
	 * Capture et instancie le gestionnaire Sql
	 */
	private function setCoreSql($db) {
		Core_Loader::classLoader("Core_Sql");
		self::$coreSql = Core_Sql::getInstance($db);
		Core_Table::setPrefix($db['prefix']);
	}
	
	/**
	 * Charge la configuration a partir de la base
	 */
	private function getConfigDb() {
		self::$coreSql->select(Core_Table::$CONFIG_TABLE, array("name", "value"));
		while ($row = self::$coreSql->fetchArray()) {
			self::$coreConfig[$row['name']] = stripslashes(htmlentities($row['value'], ENT_NOQUOTES));
		}
	}
	
	/**
	 * Ajoute l'objet a la configuration
	 * 
	 * @param $object array
	 */
	private function setCoreConfig($config) {
		// Configuration via la base donnée
		if (is_array($config)) {
			foreach($config as $key => $value) {
				self::$coreConfig[$key] = $value;
			}
		}
		
		// Configuration via le fichier temporaire
		Core_CacheBuffer::setSectionName("tmp");
		$configsFile = Core_CacheBuffer::getCache("configs.php");
		self::$coreConfig['urlRewriting'] = ((isset($configsFile['urlRewriting'])) ? $configsFile['urlRewriting'] : false);
	}
	
	public function start() {
		// Recherche de la configuration dans la base de donnée
		$this->getConfigDb();
		
		// Chargement des sessions
		Core_Loader::classLoader("Core_Session");
		$this->coreSession = new Core_Session();
		
		// Analyse pour les statistiques
		Core_Loader::classLoader("Exec_Agent");
		Exec_Agent::getVisitorsStats();
		
		// Configure la page demandé
		$this->getUrl();
		
		$this->openCompression();
		
		// Routine du cache
		Core_CacheBuffer::valideCacheBuffer();
		
		// AFFICHAGE -- A SUPPRIMER
		Core_Loader::classLoader("Libs_MakeStyle");
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("test", "Test template : succes !");
		$libsMakeStyle->displayDebug("index.tpl");
		// AFFICHAGE -- A SUPPRIMER
		
		// Affichage des exceptions
		Core_Exception::displayException();
		
		$this->closeCompression();
				
		// Assemble tous les messages d'erreurs dans un fichier log
		Core_Exception::logException();
		// Validation du cache
		Core_CacheBuffer::valideCacheBuffer();
	}
	
	/**
	 * Lance le tampon de sortie
	 */
	private function openCompression() {
		// Entête & tamporisation de sortie
		@header("Vary: Cookie, Accept-Encoding");
		if (@extension_loaded('zlib') 
				&& !@ini_get('zlib.output_compression') 
				&& @function_exists("ob_gzhandler") 
				&& !$GLOBALS['core']['url_rewriting']) {
			ob_start("ob_gzhandler");
		} else {
			ob_start();
		}
	}
	
	/**
	 * Relachement des tampons de sortie
	 */
	private function closeCompression() {
		// Tamporisation de sortie
		if (self::$coreConfig['urlRewriting']) {
			$buffer = ob_get_contents();
			ob_end_clean();
			Core_UrlRewriting::displayAll($buffer);
		}
		// Relachement des tampon
		while (@ob_end_flush());
	}
	
	/**
	 * Récupere les informations de l'url relatif a la page ciblé
	 */
	private function getUrl() {
	}
	
	/**
	 * Déconnexion de la base
	 */
	public function __destruct() {
		unset($this->coreSql);
		unset($coreConfig);
	}
}
?>