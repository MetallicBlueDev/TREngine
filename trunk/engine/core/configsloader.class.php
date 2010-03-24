<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Chargeur de configuration
 * 
 * @author Sébastien Villemain
 *
 */
class Core_ConfigsLoader {
	
	/**
	 * Lance le chargeur de configs
	 */
	public function __construct() {
		$this->loadConfigFile();
		$this->loadDatabaseFile();
		$this->loadFtpFile();
		$this->loadVersion();
	}
	
	/**
	 * Charge et vérifie la configuration
	 */
	private function loadConfigFile() {
		// Chemin vers le fichier de configuration générale
		$configPath = TR_ENGINE_DIR . "/configs/config.inc.php";
		
		if (is_file($configPath)) {
			require($configPath);
			
			$configuration = array();
			
			// Vérification de l'adresse email du webmaster
			Core_Loader::classLoader("Exec_Mailer");
			if (Exec_Mailer::validMail($config["TR_ENGINE_MAIL"])) {
				define("TR_ENGINE_MAIL", $config["TR_ENGINE_MAIL"]);
			} else {
				Core_Exception::setException("Default mail isn't valide");
			}
			
			// Vérification du statut
			$config["TR_ENGINE_STATUT"] = strtolower($config["TR_ENGINE_STATUT"]);
			if ($config["TR_ENGINE_STATUT"] == "close") define("TR_ENGINE_STATUT", "close");
			else define("TR_ENGINE_STATUT", "open");
			
			// Recherche du statut du site
			if (TR_ENGINE_STATUT == "close") {
				Core_Secure::getInstance()->debug("close");
			}
			
			if (is_int($config['cacheTimeLimit']) && $config['cacheTimeLimit'] >= 1) $configuration['cacheTimeLimit'] = $config['cacheTimeLimit'];
			else $configuration['cacheTimeLimit'] = 7;
			
			if (!empty($config['cookiePrefix'])) $configuration['cookiePrefix'] = $config['cookiePrefix'];
			else $configuration['cookiePrefix'] = "tr";
			
			if (!empty($config['cryptKey'])) $configuration['cryptKey'] = $config['cryptKey'];
			
			// Ajout a la configuration courante
			Core_Main::addToConfig($configuration);
			
			// Chargement de la configuration via la cache
			$configuration = array();
			Core_CacheBuffer::setSectionName("tmp");
			
			// Si le cache est disponible
			if (Core_CacheBuffer::cached("configs.php")) {
				$configuration = Core_CacheBuffer::getCache("configs.php");
			} else {
				$content = "";
				// Requête vers la base de donnée de configs
				Core_Sql::select(Core_Table::$CONFIG_TABLE, array("name", "value"));
				while ($row = Core_Sql::fetchArray()) {
					$configuration[$row['name']] = stripslashes($row['value']);
					$content .= "$" . Core_CacheBuffer::getSectionName() . "['" . $row['name'] . "'] = \"" . Exec_Entities::addSlashes($config[$row['name']]) . "\"; ";
				}
				
				// Mise en cache
				Core_CacheBuffer::writingCache("configs.php", $content, true);
			}
			
			// Ajout a la configuration courante
			Core_Main::addToConfig($configuration);
			
		} else {
			Core_Secure::getInstance()->debug("configPath", $configPath);
		}
	}
	
	/**
	 * Charge et vérifie les informations de base de donnée
	 */
	private function loadDatabaseFile() {
		// Chemin vers le fichier de configuration de la base de donnée
		$databasePath = TR_ENGINE_DIR . "/configs/database.inc.php";
		
		if (is_file($databasePath)) {
			require($databasePath);
			
			// Vérification du type de base de donnée
			if (!is_file(TR_ENGINE_DIR . "/engine/base/" . $db['type'] . ".class.php")) {
				Core_Secure::getInstance()->debug("sqlType", $db['type']);
			}
			
			// Démarrage de l'instance Core_Sql
			Core_Loader::classLoader("Core_Sql");
			Core_Sql::setDatabase($db);
			Core_Sql::makeInstance();
		} else {
			Core_Secure::getInstance()->debug("sqlPath", $databasePath);
		}
	}
	
	/**
	 * Charge les données FTP
	 */
	private function loadFtpFile() {
		// Mode natif PHP actif
		$modes = array("php");
		
		// Chemin du fichier de configuration ftp
		$ftpPath = TR_ENGINE_DIR . "/configs/ftp.inc.php";
		
		if (is_file($ftpPath)) {
			require($ftpPath);
			
			// Préparation de l'activation du mode
			$mode = strtolower($ftp['type']);
			if ($mode != "php") $modes[] = $mode;
			
			// Ajout des données FTP
			Core_CacheBuffer::setFtp($ftp);
		}		
		// Activation des modes
		Core_CacheBuffer::setModeActived($modes);
	}
	
	/**
	 * Charge le numéro de version du moteur
	 */
	private function loadVersion() {
		// Chemin vers le fichier de version
		$versionPath = TR_ENGINE_DIR . "/engine/version.inc.php";
		
		// Configuration du numéro de version
		$TR_ENGINE_VERSION = "0.0.0.0";
		if (is_file($versionPath)) {
			require($versionPath);
		}
		define("TR_ENGINE_VERSION", $TR_ENGINE_VERSION);
	}
}

?>