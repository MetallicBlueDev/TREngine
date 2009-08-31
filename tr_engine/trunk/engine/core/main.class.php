<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
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
	public static $coreConfig;
	
	/**
	 * Mode de mise en page courante
	 * default : affichage normale et complet
	 * module : affichage uniquement du module si javascript activé
	 * block : affichage uniquement du block si javascript activé
	 * modulepage : affichage uniquement du module forcé 
	 * blockpage : affichage uniquement du block forcé
	 * 
	 * @var String
	 */
	public static $layout = "default";
	
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
		Exec_Marker::startTimer("core");
		
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
		
		// Chargement du convertiseur d'entities
		Core_Loader::classLoader("Exec_Entities");
		
		// Récuperation de la configuration
		$this->setCoreConfig($coreConfigLoader->getConfig());
		
		// Destruction du chargeur de configs
		unset($coreConfigLoader);
		
		Exec_Marker::stopTimer("core");
		
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
		Core_Sql::makeInstance($db);
		Core_Table::setPrefix($db['prefix']);
	}
	
	/**
	 * Charge la configuration a partir de la base
	 * 
	 * @return array
	 */
	private function getConfigDb() {
		$config = array();
		Core_CacheBuffer::setSectionName("tmp");
		$content = "";
		
		// Requête vers la base de donnée de configs
		Core_Sql::select(Core_Table::$CONFIG_TABLE, array("name", "value"));
		while ($row = Core_Sql::fetchArray()) {
			$config[$row['name']] = stripslashes(htmlentities($row['value'], ENT_NOQUOTES));
			$content .= "$" . Core_CacheBuffer::getSectionName() . "['" . $row['name'] . "'] = \"" . Exec_Entities::addSlashes($config[$row['name']]) . "\"; ";
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
				self::$coreConfig[$key] = Exec_Entities::stripSlashes($value);
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
		Exec_Marker::startTimer("launcher");
		
		// Chargement du gestionnaire d'accès url
		Core_Loader::classLoader("Core_Request");
		
		// Gestionnaire des cookie
		Core_Loader::classLoader("Exec_Cookie");
		
		// Chargement de l'outil de cryptage
		Core_Loader::classLoader("Exec_Crypt");
		
		// Analyse pour les statistiques
		Core_Loader::classLoader("Exec_Agent");
		Exec_Agent::getVisitorsStats();
		
		// Chargement des sessions
		Core_Loader::classLoader("Core_Session");
		Core_Session::getInstance();
		
		// Chargement du moteur de traduction
		Core_Loader::classLoader("Core_Translate");
		Core_Translate::makeInstance();
		
		// Chargement du traitement HTML
		Core_Loader::classLoader("Core_TextEditor");
		
		// Vérification des bannissements
		Core_Loader::classLoader("Core_BlackBan");
		Core_BlackBan::checkBlackBan();
		
		// Chargement du gestionnaire HTML
		Core_Loader::classLoader("Core_Html");
		Core_Html::getInstance();
		
		// Configure les informations de page demandées
		$this->loadLayout();
		$this->loadModule();
		$this->loadMakeStyle();
		// TODO a décommenter
		//$this->compressionOpen();
		
		// Comportement different en fonction du type de client
		if (!Core_BlackBan::isBlackUser()) {
			// Chargement du gestionnaire d'autorisation
			Core_Loader::classLoader("Core_Acces");
			
			// Chargement du système de validation par code
			Core_Loader::classLoader("Libs_Captcha");
			
			// Chargement des blocks
			Core_Loader::classLoader("Libs_Block");
			
			// Chargement de la réécriture d'URL
			if (self::doUrlRewriting()) {
				Core_Loader::classLoader("Core_UrlRewriting");
				Core_UrlRewriting::test();
			}
			
			if (self::isFullScreen() && Core_Loader::isCallable("Libs_Block") && Core_Loader::isCallable("Libs_Module")) {
				// Traduction du module
				Core_Translate::translate("modules/" . Libs_Module::$module);
				
				// Chargement et construction du fil d'ariane
				Core_Loader::classLoader("Libs_Breadcrumb");
				Libs_Breadcrumb::getInstance();
				
				Libs_Block::getInstance()->launch();
				Libs_Module::getInstance()->launch();
				
				Exec_Marker::stopTimer("main");
				$libsMakeStyle = new Libs_MakeStyle();
				$libsMakeStyle->display("index.tpl");
			} else {
				// Affichage autonome des modules et blocks
				if (self::isModuleScreen() && Core_Loader::isCallable("Libs_Module")) {
					Core_Translate::translate("modules/" . Libs_Module::$module);
					Libs_Module::getInstance()->launch();
					echo Libs_Module::getInstance()->getModule();
				} else if (self::isBlockScreen() && Core_Loader::isCallable("Libs_Block")) {
					Libs_Block::getInstance()->launch();
					echo Libs_Block::getInstance()->getBlock();
				}
				// Execute la commande de récupération d'erreur
				Core_Exception::getMinorError();
				// Javascript autonome
				Core_Html::getInstance()->selfJavascript();
			}
			
			// Validation du cache / Routine du cache
			Core_CacheBuffer::valideCacheBuffer();
			// Assemble tous les messages d'erreurs dans un fichier log
			Core_Exception::logException();
		} else {
			Core_BlackBan::displayBlackPage();
		}
		
		// TODO a décommenter
		//$this->compressionClose();
		
		Exec_Marker::stopTimer("launcher");
	}
	
	/**
	 * Lance le tampon de sortie
	 * Entête & tamporisation de sortie
	 */
	private function compressionOpen() {
		header("Vary: Cookie, Accept-Encoding");
		if (extension_loaded('zlib') 
				&& !ini_get('zlib.output_compression') 
				&& function_exists("ob_gzhandler") 
				&& !self::doUrlRewriting()) {
			ob_start("ob_gzhandler");
		} else {
			ob_start();
		}
	}
	
	/**
	 * Relachement des tampons de sortie
	 */
	private function compressionClose() {
		while (ob_end_flush());
	}
	
	/**
	 * Assignation et vérification de fonction layout
	 */
	private function loadLayout() {
		// Assignation et vérification de fonction layout
		$layout = strtolower(Core_Request::getWord("layout"));
		
		// Configuration du layout
		if ($layout != "default" && $layout != "modulepage"	&& $layout != "blockpage" 
				&& (($layout != "block" && $layout != "module") || (!Core_Html::getInstance()->isJavascriptEnabled()))) {
			$layout = "default";
		}
		self::$layout = $layout;
	}
	
	/**
	 * Création de l'instance du module
	 */
	private function loadModule() {
		Core_Loader::classLoader("Libs_Module");	
		Libs_Module::getInstance(
			Core_Request::getWord("mod"), 
			Core_Request::getWord("page"), 
			Core_Request::getWord("view")
		);
	}
	
	/**
	 * Assignation et vérification du template
	 */
	private function loadMakeStyle() {		
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
		return ((self::$layout == "module" || self::$layout == "modulepage") ? true : false);
	}
	
	/**
	 * Vérifie si l'affichage se fait en écran minimal ciblé block
	 * 
	 * @return boolean true c'est un affichage de block uniquement
	 */
	public static function isBlockScreen() {
		return ((self::$layout == "block" || self::$layout == "blockpage") ? true : false);
	}
	
	/**
	 * Vérifie si l'url rewriting est activé
	 * 
	 * @return boolean
	 */
	public static function doUrlRewriting() {
		return (self::$coreConfig['urlRewriting'] == 1) ? true : false;
	}
	
	/**
	 * Etat des inscriptions au site
	 * 
	 * @return boolean
	 */
	public static function isRegistrationAllowed() {
		return (self::$coreConfig['registrationAllowed'] == 1) ? true : false;
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