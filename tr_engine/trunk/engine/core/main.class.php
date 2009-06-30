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
		
		// Connexion à la base de donnée
		$this->setCoreSql($coreConfigLoader->getDatabase());
		
		// Récuperation de la configuration
		$this->setCoreConfig($coreConfigLoader->getConfig());
		
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
		Core_Sql::getInstance($db);
		Core_Table::setPrefix($db['prefix']);
	}
	
	/**
	 * Charge la configuration a partir de la base
	 * 
	 * @return array
	 */
	private function getConfigDb() {
		$config = array();
		$sql = Core_Sql::getInstance();
		Core_CacheBuffer::setSectionName("tmp");
		$content = "";
		
		// Requête vers la base de donnée de configs
		$sql->select(Core_Table::$CONFIG_TABLE, array("name", "value"));
		while ($row = $sql->fetchArray()) {
			$config[$row['name']] = stripslashes(htmlentities($row['value'], ENT_NOQUOTES));
			$content .= "$" . Core_CacheBuffer::getSectionName() . "['" . $row['name'] . "'] = \"" . Core_CacheBuffer::preparingCaching($config[$row['name']]) . "\"; ";
		}
		// Mise en cache
		Core_CacheBuffer::writingCache("configs.php", $content, true);
		// Retourne le configuration pour l'ajout
		return $config;
	}
	
	/**
	 * Ajoute l'objet a la configuration
	 * 
	 * @param $config array
	 */
	private function addToConfig($config) {
		if (is_array($config)) {
			foreach($config as $key => $value) {
				self::$coreConfig[$key] = $value;
			}
		}
	}
	
	/**
	 * Recupere les variables de configuration
	 * Utilisation du cache ou sinon de la base de donnée
	 * 
	 * @param $configIncFile
	 */
	private function setCoreConfig($configIncFile) {
		// Ajout a la configuration courante
		$this->addToConfig($configIncFile);
		
		// Configuration via le fichier temporaire
		Core_CacheBuffer::setSectionName("tmp");
		if (Core_CacheBuffer::cached("configs.php")) {
			$configCached = Core_CacheBuffer::getCache("configs.php");
			$this->addToConfig($configCached);
		} else {
			// Recherche de la configuration dans la base de donnée
			$configDb = $this->getConfigDb();
			// Ajout a la configuration courante
			$this->addToConfig($configDb);
		}
	}
	
	public function start() {
		// Gestionnaire des cookie
		Core_Loader::classLoader("Exec_Cookie");
		
		// Analyse pour les statistiques
		Core_Loader::classLoader("Exec_Agent");
		Exec_Agent::getVisitorsStats();
		
		// Chargement des sessions
		Core_Loader::classLoader("Core_Session");
		$this->coreSession = new Core_Session();
		
		// Configure la page demandé
		$this->getUrl();
		
		//$this->openCompression();
		
		// Routine du cache
		Core_CacheBuffer::valideCacheBuffer();
		
		// AFFICHAGE -- A SUPPRIMER
		Core_Loader::classLoader("Libs_MakeStyle");
		$libsMakeStyle = new Libs_MakeStyle();
		$libsMakeStyle->assign("test", "Template d'essai activé");
		$libsMakeStyle->displayDebug("index.tpl");
		// AFFICHAGE -- A SUPPRIMER
		
		// Affichage des exceptions
		Core_Exception::displayException();
		
		//$this->closeCompression();
				
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