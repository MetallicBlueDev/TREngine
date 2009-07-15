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
	 * Mode de mise en page courante
	 * default : affichage normale et complet
	 * module : affichage du module uniquement
	 * block : affichage du block uniquement
	 * 
	 * @var String
	 */
	public static $layout = "";
	
	/**
	 * Préparation TR ENGINE
	 */
	public function __construct() {
		$this->starter();
	}
	
	/**
	 * Procédure de préparation du moteur
	 * Une étape avant le démarrage réel
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
	
	/**
	 * Démarrage TR ENGINE
	 */
	public function start() {
		// Gestionnaire des cookie
		Core_Loader::classLoader("Exec_Cookie");
		
		// Analyse pour les statistiques
		Core_Loader::classLoader("Exec_Agent");
		Exec_Agent::getVisitorsStats();
		
		// Chargement des sessions
		Core_Loader::classLoader("Core_Session");
		Core_Session::getInstance();
		
		// Configure les informations de page demandées
		$this->launchUrl();
		
		// Chargement du convertiseur d'entities
		Core_Loader::classLoader("Exec_Entities");
		
		// Chargement du traitement HTML
		Core_Loader::classLoader("Core_TextEditor");
		
		// Chargement du moteur de traduction
		Core_Loader::classLoader("Core_Translate");
		Core_Translate::setLanguage();
		Core_Translate::translate();
		
		// Vérification des bannissements
		Core_Loader::classLoader("Core_BlackBan");
		Core_BlackBan::checkBlackBan();
		
		// TODO a décommenter
		//$this->openCompression();
		
		// Comportement different en fonction du type de client
		if (!Core_BlackBan::isBlackUser()) {
			// Chargement du gestionnaire d'autorisation
			Core_Loader::classLoader("Core_Acces");
			
			// Chargement du système de validation par code
			Core_Loader::classLoader("Libs_Captcha");
			
			// Chargement des blocks
			Core_Loader::classLoader("Libs_Block");
			
			if (self::isFullScreen()) {
				Libs_Block::getInstance()->launch();
				Libs_Module::getInstance()->launch();
				$libsMakeStyle = new Libs_MakeStyle();
				$libsMakeStyle->display("index.tpl");
			} else if (self::isModuleScreen()) {
				Libs_Module::getInstance()->launch();
				echo Libs_Module::getInstance()->getModule();
			} else if (self::isBlockScreen()) {
				Libs_Block::getInstance()->launch();
				echo Libs_Block::getInstance()->getBlock();
			}
					
			// Assemble tous les messages d'erreurs dans un fichier log
			Core_Exception::logException();
			// Validation du cache / Routine du cache
			Core_CacheBuffer::valideCacheBuffer();
		} else {
			Core_BlackBan::displayBlackPage();
		}
		
		// Affichage des exceptions
		Core_Exception::displayException();
		
		// TODO a décommenter
		//$this->closeCompression();
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
				&& !self::$coreConfig['urlRewriting']) {
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
	private function launchUrl() {
		// Assignation et vérification de fonction layout
		$layout = strtolower(Core_Secure::checkVariable("layout"));
		
		// Assignation et vérification du module
		$module = Core_Secure::checkVariable("mod");
		
		// Assignation et vérification de la page
		$page = Core_Secure::checkVariable("page");
		
		// Assignation et vérification de fonction view
		$view = Core_Secure::checkVariable("view");
		
		// Configuration du layout
		if ($layout != "default" 
				&& $layout != "block" 
				&& $layout != "module") {
			$layout = "default";
		}
		self::$layout = $layout;
		
		// Création de l'instance du module
		// Même si ce n'est pas utilisé, il vaut mieux le laisser
		Core_Loader::classLoader("Libs_Module");	
		Libs_Module::getInstance($module, $page, $view, $layout);
		
		// Vérification de la langue du client
		Core_Session::$userLanguage = Core_Secure::checkVariable(Core_Session::$userLanguage, false);
		
		// Vérification du template du client
		Core_Session::$userTemplate = Core_Secure::checkVariable(Core_Session::$userTemplate, false);
		
		// Vérification des infos IP BAN pour Core_BlackBan
		Core_Session::$userIpBan = Core_Secure::checkVariable(Core_Session::$userIpBan, false);
		
		// Assignation et vérification du template
		$template = (!Core_Session::$userTemplate) ? self::$coreConfig['defaultTemplate'] : Core_Session::$userTemplate;
		Core_Loader::classLoader("Libs_MakeStyle");		
		Libs_MakeStyle::getTemplateUsedDir($template);
	}
	
	/**
	 * Vérifie si l'affichage se fait en écran complet
	 * 
	 * @return boolean true c'est en plein écran
	 */
	public static function isFullScreen() {
		return ((self::$layout == "default") ? true : false);
	}
	
	/**
	 * Vérifie si l'affichage se fait en écran minimal ciblé module
	 * 
	 * @return boolean true c'est un affichage de module uniquement
	 */
	public static function isModuleScreen() {
		return ((self::$layout == "module") ? true : false);
	}
	
	/**
	 * Vérifie si l'affichage se fait en écran minimal ciblé block
	 * 
	 * @return boolean true c'est un affichage de block uniquement
	 */
	public static function isBlockScreen() {
		return ((self::$layout == "block") ? true : false);
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