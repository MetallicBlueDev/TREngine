<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de la communication SQL
 * 
 * @author Sébastien Villemain
 * 
 */
class Core_Sql {
	
	/**
	 * Instance de la base
	 */ 
	protected static $base;
	
	/**
	 * Nom d'host de la base
	 */
	protected $dbHost;
	
	/**
	 * Nom d'utilisateur de la base
	 */
	protected $dbUser;
	
	/**
	 * Mot de passe de la base
	 */
	protected $dbPass;
	
	/**
	 * Nom de la base
	 */
	protected $dbName;
	
	/**
	 * Type de base de donnée
	 */
	protected $dbType;
	
	/**
	 * ID de la connexion
	 */
	protected $connId = false;
	
	/**
	 * Derniere requête SQL, ressources ID
	 */
	protected $queries;
	
	public function __construct() {
		
	}
	
	/**
	 * Démarre une instance de communication avec la base
	 * 
	 * @param $db array
	 * @return Base_Type
	 */
	public static function getInstance($db = array()) {
		if (!self::$base && count($db) >= 5) {
			// Choix du type de base de donneé
			if ($db['type'] == "mysql") {
				Core_Loader::classLoader("Base_MySql");
				$sqlClassName = "Base_MySql";
			} else {
				Core_Secure::getInstance()->debug("sqlType");
			}
			
			try {
				self::$base = new $sqlClassName($db);
			} catch (Exception $ie) {
				Core_Secure::getInstance()->debug($ie);
			}
		}
		return self::$base;
	}
	
	/**
	 * Destruction de la communication
	 */
	public function __destruct() {
		self::$base = false;
	}
	
	/**
	 * Retourne le résultat de la dernière requête executé
	 * @return mixed Ressource ID ou boolean false
	 */
	public function getQueries() {
		return $this->queries;
	}
}
?>