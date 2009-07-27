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
abstract class Core_Sql {
	
	/**
	 * Instance de la base
	 * 
	 * @var Base_xxxx
	 */ 
	protected static $base;
	
	/**
	 * Nom d'host de la base
	 * 
	 * @var String
	 */
	protected $dbHost;
	
	/**
	 * Nom d'utilisateur de la base
	 * 
	 * @var String
	 */
	protected $dbUser;
	
	/**
	 * Mot de passe de la base
	 * 
	 * @var String
	 */
	protected $dbPass;
	
	/**
	 * Nom de la base
	 * 
	 * @var String
	 */
	protected $dbName;
	
	/**
	 * Type de base de donnée
	 * 
	 * @var String
	 */
	protected $dbType;
	
	/**
	 * ID de la connexion
	 * 
	 * @var String
	 */
	protected $connId = false;
	
	/**
	 * Dernier resultat de la derniere requête SQL
	 * 
	 * @var String ressources ID
	 */
	protected $queries = "";
	
	/**
	 * Derniere requête SQL
	 * 
	 * @var String
	 */
	protected $sql = "";
	
	public function __construct($db) {
		$this->dbHost = $db['host'];
		$this->dbUser = $db['user'];
		$this->dbPass = $db['pass'];
		$this->dbName = $db['name'];
		$this->dbType = $db['type'];
		echo "oui";
		// Connexion au serveur
		self::dbConnect();
		
		// Selection d'une base de donnée
		self::dbSelect();
	}
	
	/**
	 * Démarre une instance de communication avec la base
	 * 
	 * @param $db array
	 * @return Base_Type
	 */
	public static function getInstance($db = array()) {
		if (!self::$base && count($db) >= 5) {
			// Base par défaut
			if (!$db['type']) $db['type'] = "mysql";
			
			// Vérification du type de base de donnée
			if (!is_file(TR_ENGINE_DIR . "/engine/base/" . $db['type'] . ".class.php")) {
				Core_Secure::getInstance()->debug("sqlType");
			}
			
			// Chargement des drivers pour la base
			$BaseClass = "Base_" . ucfirst($db['type']);
			Core_Loader::classLoader($BaseClass);
			
			try {echo "oui";
				self::$base = new $BaseClass($db);
			} catch (Exception $ie) {echo "non";
				Core_Secure::getInstance()->debug($ie);
			}
		}
		return self::$base;
	}
	
	/**
	 * Etablie une connexion à la base de donnée
	 */
	public static function dbConnect() {
		self::$base->dbConnect();
		
		if (self::$base->connId == false) {
			throw new Exception("sqlConnect");
		}
	}
	
	/**
	 * Selectionne une base de donnée
	 * 
	 * @return boolean true succes
	 */
	public static function dbSelect() {
		$rslt = self::$base->dbSelect();
		
		if (!$rslt) {
			throw new Exception("sqlConnect");
		}
		return $rslt;
	}
	
	/**
	 * Get number of LAST affected rows 
	 * 
	 * @return int
	 */
	public static function affectedRows() {
		return self::$base->affectedRows();
	}
	
	/**
	 * Supprime des informations
	 * 
	 * @param $table Nom de la table
	 * @param $where
	 * @param $like
	 * @param $limit
	 */
	public static function delete($table, $where = array(), $like = array(), $limit = false) {
		self::$base->delete($table, $where, $like, $limit);
		
		try {
			self::query();
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}
	}
	
	/**
	 * Retourne un tableau qui contient les lignes demandées
	 * 
	 * @return array
	 */
	public static function fetchArray() {
		return self::$base->fetchArray();
	}
	
	/**
	 * Insere de valeurs dans une table
	 * 
	 * @param $table Nom de la table
	 * @param $keys
	 * @param $values
	 */
	public static function insert($table, $keys, $values) {
		self::$base->insert($table, $keys, $values);
		
		try {
			self::query();
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}
	}
	
	/**
	 * Retourne l'id de la dernière ligne inserée
	 * 
	 * @return int
	 */
	public static function insertId() {
		return self::$base->insertId();
	}
	
	/**
	 * Get number of affected rows 
	 * 
	 * @param $queries
	 * @return int
	 */
	public static function numRows($queries = "") {
		$queries = ($queries != "") ? $queries : self::getQueries();
		return self::$base->numRow($queries);
	}
	
	/**
	 * Envoie une requête Sql
	 * 
	 * @param $Sql
	 */
	public static function query($sql = "") {
		$sql = ($sql != "") ? $sql : self::getSql();
		self::$base->query($sql);
		
		if (!self::getQueries()) throw new Exception("sqlReq");
	}
	
	/**
	 * Selection d'information
	 * 
	 * @param $table String
	 * @param $values array
	 * @param $where array
	 * @param $orderby array
	 * @param $limit
	 */
	public static function select($table, $values, $where = array(), $orderby = array(), $limit = false) {
		self::$base->select($table, $values, $where, $orderby, $limit);
		
		try {
			self::query();
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}
	}
	
	/**
	 * Mise à jour d'une table
	 * 
	 * @param $table Nom de la table
	 * @param $values array() $value[$key]
	 * @param $where array
	 * @param $orderby array
	 * @param $limit
	 */
	public static function update($table, $values, $where, $orderby = array(), $limit = false) {
		self::$base->update($table, $values, $where, $orderby, $limit);
		
		try {
			self::query();
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}
	}
	
	/**
	 * Destruction de la communication
	 */
	public function __destruct() {
		self::$base = false;
		self::dbDeconnect();
	}
	
	/**
	 * Retourne dernier résultat de la dernière requête executée
	 *
	 * @return mixed Ressource ID ou boolean false
	 */
	public static function getQueries() {
		return self::$base->queries;
	}
	
	/**
	 * Retourne la derniere requête sql
	 * 
	 * @return String
	 */
	public static function getSql() {
		return self::$base->sql;
	}
}
?>