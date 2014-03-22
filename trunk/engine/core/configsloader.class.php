<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Chargeur de configuration
 * 
 * @author Sébastien Villemain
 */
class Core_ConfigsLoader {
	
	/**
	 * Lance le chargeur de configs
	 */
	public function __construct() {
		$this->loadVersion();
		
		if (Core_Loader::isCallable("Core_Main")) {
			if ($this->loadCacheBuffer() && $this->loadSql()) {
				$this->loadConfig();
			} else {
				Core_Secure::getInstance()->debug("Config error: Core_CacheBuffer and Core_Sql aren't loaded");
			}
		} else {
			Core_Secure::getInstance()->debug("Config error: Core_Main isn't loaded");
		}
	}
	
	/**
	 * Charge et vérifie la configuration
	 */
	private function loadConfig() {
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
			Core_Main::addToConfiguration($configuration);
			
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
					$content .= Core_CacheBuffer::serializeData(array($row['name'] => $row['value']));
					$configuration[$row['name']] = Exec_Entities::stripSlashes($row['value']);
				}
				
				// Mise en cache
				Core_CacheBuffer::writingCache("configs.php", $content, true);
			}
			
			// Ajout a la configuration courante
			Core_Main::addToConfiguration($configuration);
			
		} else {
			Core_Secure::getInstance()->debug("configPath", $configPath);
		}
	}
	
	/**
	 * Charge le gestionnaire Sql
	 * 
	 * @return boolean true chargé
	 */
	private function loadSql() {
		if (!Core_Loader::isCallable("Core_Sql")) {
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
			return Core_Loader::isCallable("Core_Sql");
		}
		return true;
	}
	
	/**
	 * Charge le gestionnaire de cache et les données FTP
	 * 
	 * @return boolean true chargé
	 */
	private function loadCacheBuffer() {
		if (!Core_Loader::isCallable("Core_CacheBuffer")) {
			// Chargement du gestionnaire de cache
			Core_Loader::classLoader("Core_CacheBuffer");
			
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
			return Core_Loader::isCallable("Core_CacheBuffer");
		}
		return true;
	}
	
	/**
	 * Charge le numéro de version du moteur
	 * 
	 * @return boolean true chargé
	 */
	private function loadVersion() {
		if (!defined(TR_ENGINE_VERSION)) {
			// Chemin vers le fichier de version
			$versionPath = TR_ENGINE_DIR . "/engine/version.inc.php";
			
			// Configuration du numéro de version
			$TR_ENGINE_VERSION = "0.0.0.0";
			if (is_file($versionPath)) {
				require($versionPath);
			}
			define("TR_ENGINE_VERSION", $TR_ENGINE_VERSION);
		}
		return true;
	}
}

?>