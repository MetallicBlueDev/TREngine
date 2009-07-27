<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Classe principal du moteur
 * 
 * @author S�bastien Villemain
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
	public static $layout = "default";
	
	/**
	 * Pr�paration TR ENGINE
	 */
	public function __construct() {
		$this->starter();
	}
	
	/**
	 * Proc�dure de pr�paration du moteur
	 * Une �tape avant le d�marrage r�el
	 */
	private function starter() {
		Exec_Marker::startTimer("core");
		
		// V�rification de la version PHP
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
		
		// Connexion � la base de donn�e
		$this->setCoreSql($coreConfigLoader->getDatabase());
		
		// R�cuperation de la configuration
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
		Core_CacheBuffer::setSectionName("tmp");
		$content = "";
		
		// Requ�te vers la base de donn�e de configs
		Core_Sql::select(Core_Table::$CONFIG_TABLE, array("name", "value"));
		while ($row = Core_Sql::fetchArray()) {
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
	 * Utilisation du cache ou sinon de la base de donn�e
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
			// Recherche de la configuration dans la base de donn�e
			$configDb = $this->getConfigDb();
			// Ajout a la configuration courante
			$this->addToConfig($configDb);
		}
	}
	
	/**
	 * D�marrage TR ENGINE
	 */
	public function start() {
		Exec_Marker::startTimer("launcher");
		
		// Chargement du gestionnaire d'acc�s url
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
		
		// Configure les informations de page demand�es
		$this->launchUrl();
		
		// Chargement du convertiseur d'entities
		Core_Loader::classLoader("Exec_Entities");
		
		// Chargement du traitement HTML
		Core_Loader::classLoader("Core_TextEditor");
		
		// V�rification des bannissements
		Core_Loader::classLoader("Core_BlackBan");
		Core_BlackBan::checkBlackBan();
		
		// Chargement du gestionnaire HTML
		Core_Loader::classLoader("Core_Html");
		Core_Html::getInstance();
		
		// TODO a d�commenter
		//$this->openCompression();
		
		// Comportement different en fonction du type de client
		if (!Core_BlackBan::isBlackUser()) {
			// Chargement du gestionnaire d'autorisation
			Core_Loader::classLoader("Core_Acces");
			
			// Chargement du syst�me de validation par code
			Core_Loader::classLoader("Libs_Captcha");
			
			// Chargement des blocks
			Core_Loader::classLoader("Libs_Block");
			
			if (self::isFullScreen()) {
				Libs_Block::getInstance()->launch();
				Libs_Module::getInstance()->launch();
				Exec_Marker::stopTimer("main");
				$libsMakeStyle = new Libs_MakeStyle();
				$libsMakeStyle->display("index.tpl");
			} else if (self::isModuleScreen()) {
				Libs_Module::getInstance()->launch();
				echo Libs_Module::getInstance()->getModule();
			} else if (self::isBlockScreen()) {
				Libs_Block::getInstance()->launch();
				echo Libs_Block::getInstance()->getBlock();
			}
			
			// Validation du cache / Routine du cache
			Core_CacheBuffer::valideCacheBuffer();
			// Assemble tous les messages d'erreurs dans un fichier log
			Core_Exception::logException();
		} else {
			Core_BlackBan::displayBlackPage();
		}
		
		// TODO a d�commenter
		//$this->closeCompression();
		
		Exec_Marker::stopTimer("launcher");
	}
	
	/**
	 * Lance le tampon de sortie
	 * Ent�te & tamporisation de sortie
	 */
	private function openCompression() {
		header("Vary: Cookie, Accept-Encoding");
		if (extension_loaded('zlib') 
				&& !ini_get('zlib.output_compression') 
				&& function_exists("ob_gzhandler") 
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
		while (ob_end_flush());
	}
	
	/**
	 * R�cupere les informations de l'url relatif a la page cibl�
	 */
	private function launchUrl() {
		// Assignation et v�rification de fonction layout
		$layout = strtolower(Core_Request::getWord("layout"));
		
		// Assignation et v�rification du module
		$module = Core_Request::getWord("mod");
		
		// Assignation et v�rification de la page
		$page = Core_Request::getWord("page");
		
		// Assignation et v�rification de fonction view
		$view = Core_Request::getWord("view");
		
		// Configuration du layout
		if ($layout != "default" 
				&& $layout != "block" 
				&& $layout != "module") {
			$layout = "default";
		}
		self::$layout = $layout;
		
		// Cr�ation de l'instance du module
		// M�me si ce n'est pas utilis�, il vaut mieux le laisser
		Core_Loader::classLoader("Libs_Module");	
		Libs_Module::getInstance($module, $page, $view);
		
		// Assignation et v�rification du template
		$template = (!Core_Session::$userTemplate) ? self::$coreConfig['defaultTemplate'] : Core_Session::$userTemplate;
		Core_Loader::classLoader("Libs_MakeStyle");		
		Libs_MakeStyle::getTemplateUsedDir($template);
	}
	
	/**
	 * V�rifie si l'affichage se fait en �cran complet
	 * 
	 * @return boolean true c'est en plein �cran
	 */
	public static function isFullScreen() {
		return ((self::$layout == "default") ? true : false);
	}
	
	/**
	 * V�rifie si l'affichage se fait en �cran minimal cibl� module
	 * 
	 * @return boolean true c'est un affichage de module uniquement
	 */
	public static function isModuleScreen() {
		return ((self::$layout == "module") ? true : false);
	}
	
	/**
	 * V�rifie si l'affichage se fait en �cran minimal cibl� block
	 * 
	 * @return boolean true c'est un affichage de block uniquement
	 */
	public static function isBlockScreen() {
		return ((self::$layout == "block") ? true : false);
	}
	
	/**
	 * D�connexion de la base
	 */
	public function __destruct() {
		unset($this->coreSql);
		unset($coreConfig);
	}
}
?>