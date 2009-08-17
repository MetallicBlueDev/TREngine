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
	 * 
	 * @var Base_xxxx
	 */ 
	protected static $base = false;
	
	/**
	 * Démarre une instance de communication avec la base
	 * 
	 * @param $db array
	 * @return Base_Type
	 */
	public static function &makeInstance($db = array()) {
		if (self::$base === false && count($db) >= 5) {			
			// Vérification du type de base de donnée
			if (!is_file(TR_ENGINE_DIR . "/engine/base/" . $db['type'] . ".class.php")) {
				Core_Secure::getInstance()->debug("sqlType");
			}
			
			// Chargement du modele de base de donnée
			Core_Loader::classLoader("Base_Model");
			
			// Chargement des drivers pour la base
			$BaseClass = "Base_" . ucfirst($db['type']);
			Core_Loader::classLoader($BaseClass);
			
			try {
				self::$base = new $BaseClass($db);
			} catch (Exception $ie) {
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
	}
	
	/**
	 * Selectionne une base de donnée
	 * 
	 * @return boolean true succes
	 */
	public static function dbSelect() {
		$rslt = self::$base->dbSelect();
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
	 * Retourne un objet qui contient les lignes demandées
	 * 
	 * @return object
	 */
	public static function fetchObject() {
		return self::$base->fetchObject();
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
		$queries = (!empty($queries)) ? $queries : self::getQueries();
		return self::$base->numRow($queries);
	}
	
	/**
	 * Envoie une requête Sql
	 * 
	 * @param $Sql
	 */
	public static function query($sql = "") {
		$sql = (!empty($sql)) ? $sql : self::getSql();
		self::$base->query($sql);
		
		// Création d'une exception si une réponse est négative (false)
		if (self::getQueries() === false) throw new Exception("sqlReq");
		
		// Incremente le nombre de requête effectuées
		Core_Exception::$numberOfRequest++;
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
	 * Retourne dernier résultat de la dernière requête executée
	 *
	 * @return mixed Ressource ID ou boolean false
	 */
	public static function getQueries() {
		return self::$base->getQueries();
	}
	
	/**
	 * Retourne la derniere requête sql
	 * 
	 * @return String
	 */
	public static function getSql() {
		return self::$base->getSql();
	}
	
	/**
	 * Libere la memoire du resultat
	 * 
	 * @param $querie Resource Id
	 * @return boolean
	 */
	public static function freeResult($querie = "") {
		$querie = (!empty($querie)) ? $querie : self::getQueries();
		return self::$base->freeResult($querie);
	}
	
	/**
	 * Ajoute un bout de donnée dans le buffer
	 * 
	 * @param $key String cles a utiliser
	 * @param $name String
	 */
	public static function addBuffer($name, $key = "") {
		self::$base->addBuffer($name, $key);
		self::freeResult();
	}
	
	/**
	 * Retourne le buffer courant puis l'incremente
	 * 
	 * @param $name String
	 * @return array - object
	 */
	public static function fetchBuffer($name) {
		return self::$base->fetchBuffer($name);
	}
	
	/**
	 * Retourne le buffer complet choisis
	 * 
	 * @param $name String
	 * @return array - object
	 */
	public static function getBuffer($name) {
		return self::$base->getBuffer($name);
	}
}
?>