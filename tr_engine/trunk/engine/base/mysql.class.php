<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de la communication SQL
 * 
 * @author Sébastien Villemain
 * 
 */
class Base_MySql extends Core_Sql {
	
	public function Base_MySql($db) {
		$this->__construct($db);
	}
	
	/**
	 * Surcharge du constructeur de base pour n'avoir aucun parametre
	 */
	public function __construct($db) {
		// Parametrage
		$this->dbHost = $db['host'];
		$this->dbUser = $db['user'];
		$this->dbPass = $db['pass'];
		$this->dbName = $db['name'];
		$this->dbType = $db['type'];
		
		// Connexion au serveur
		$this->dbConnect();
		
		// Selection d'une base de donnée
		if (!$this->dbSelect()) {
			throw new Exception("sqlConnect");
		}
	}
	
	public function __destruct() {
		$this->connId = $this->dbDeconnect();
	}
	
	/**
	 * Etablie une connexion à la base de donnée
	 */
	private function dbConnect() {
		$this->connId = @mysql_connect($this->dbHost, $this->dbUser, $this->dbPass);
		
		if ($this->connId == false) {
			throw new Exception("sqlConnect");
		}
	}
	
	/**
	 * Selectionne une base de donnée
	 * @return boolean true succes
	 */
	private function dbSelect() {
		if ($this->connId) {
			return @mysql_select_db($this->dbName, $this->connId);
		}
		return false;
	}
	
	/**
	 * Déconnexion à la base de donnée
	 * 
	 * @return boolean true succes
	 */
	private function dbDeconnect() {
		if ($this->connId) {
			return @mysql_close($this->connId);
		}
		return false;
	}
	
	/**
	 * Envoie une requête Sql
	 * 
	 * @param $Sql
	 */
	public function query($sql) {
		$this->queries = @mysql_query($sql, $this->connId);
		
		if (!$this->queries) throw new Exception("sqlReq");
	}
	
	/**
	 * Retourne un tableau qui contient la ligne demandée
	 */
	public function fetchArray() {
		return mysql_fetch_array($this->queries);
	}
	
	/**
	 * Get number of affected rows 
	 * 
	 * @return int
	 */
	public function affectedRows() {
		return mysql_affected_rows($this->connId);
	}
	
	/**
	 * Get number of LAST affected rows 
	 * 
	 * @return int
	 */
	public function numRows() {
		return mysql_num_rows($this->queries);
	}
	
	/**
	 * Get the ID generated from the previous INSERT operation
	 * 
	 * @return int
	 */
	public function insertId() {
		return mysql_insert_id($this->connId);
	}
	
	/**
	 * Mise à jour d'une table
	 * 
	 * @param $table Nom de la table
	 * @param $values array() $value[$key]
	 * @param $where array
	 * @param $orderby array
	 * @param $limit
	 * @return ressource ID ou false
	 */
	public function update($table, $values, $where, $orderby = array(), $limit = false) {
		// Affectation des clès a leurs valeurs
		foreach($values as $key => $value) {
			$valuesString[] = $this->converKey($key) ." = " . $this->converValue($value);
		}
		
		// Mise en place du where
		if (!is_array($where)) $where = array($where);
		// Mise en place de la limite
		$limit = (!$limit) ? "" : " LIMIT " . $limit;
		// Mise en place de l'ordre
		$orderby = (count($orderby) >= 1)? " ORDER BY " . implode(", ", $orderby): "";
		
		// Mise en forme de la requête finale
		$sql = "UPDATE " . $table . " SET " . implode(", ", $valuesString);
		$sql .= ($where != "" && count($where) >= 1) ? " WHERE " . implode(" ", $where) : "";
		$sql .= $orderby . $limit;
		
		try {
			$this->query($sql);
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}
	}
	
	/**
	 * Insere une ou des valeurs dans une table
	 * 
	 * @param $table Nom de la table
	 * @param $keys
	 * @param $values
	 * @return ressource ID ou false
	 */
	public function insert($table, $keys, $values) {
		if (!is_array($keys)) $keys = array($keys);
		if (!is_array($values)) $values = array($values);
		
		$sql = "INSERT INTO " . $table . " ("
		. implode(", ", $this->converKey($keys)) . ") VALUES ("
		. implode(", ", $this->converValue($values)) . ")";
		
		try {
			$this->query($sql);
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}	
	}
	
	/**
	 * Supprime des informations
	 * 
	 * @param $table Nom de la table
	 * @param $where
	 * @param $like
	 * @param $limit
	 * @return ressource ID ou false
	 */
	public function delete($table, $where = array(), $like = array(), $limit = false) {
		// Mise en place du WHERE
		if (!is_array($where)) $where = array($where);
		$where = (count($where) >= 1) ? " WHERE " . implode(" ", $where) : "";
		
		// Mise en place du LIKE
		$like = (!is_array($like)) ? array($like) : $like;
		$like = (count($like) >= 1) ? " LIKE " . implode(" ", $like) : "";
		
		// Fonction ET entre WHERE et LIKE
		if ($where != "" && $like != "") {
			$where .= "AND";
		}

		$limit = (!$limit) ? "" : " LIMIT " . $limit;
		$sql = "DELETE FROM " . $table . $where . $like . $limit;
		
		try {
			$this->query($sql);
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}	
	}
	
	/**
	 * Selection d'information
	 * 
	 * @param $table String
	 * @param $values array
	 * @param $where array
	 * @param $orderby array
	 * @param $limit
	 * @return ressource ID ou false
	 */
	public function select($table, $values, $where = array(), $orderby = array(), $limit = false) {
		// Mise en place des valeurs selectionnées
		if (!is_array($values)) $values = array($values);
		$values = implode(", ", $values);
		
		// Mise en place du where
		if (!is_array($where)) $where = array($where);
		$where = (count($where) >= 1) ? " WHERE " . implode(" ", $where) : "";
		// Mise en place de la limite
		$limit = (!$limit) ? "" : " LIMIT " . $limit;
		// Mise en place de l'ordre
		$orderby = (count($orderby) >= 1)? " ORDER BY " . implode(", ", $orderby): "";
		
		// Mise en forme de la requête finale
		$sql = "SELECT " . $values . " FROM " . $table . $where . $orderby . $limit;
		
		try {
			$this->query($sql);
		} catch (Exception $ie) {
			Core_Secure::getInstance()->debug($ie);
		}				
	}
	
	/**
	 * Convertie les valeurs dite PHP en valeurs semblable SQL
	 * 
	 * @param $value mixed type
	 * @return $value
	 */
	private function converValue($value) {
		if (is_array($value)) {
			foreach($value as $key => $realValue) {
				$value[$key] = $this->converValue($realValue);
			}
		}
		
		if (is_bool($value)) {
			$value = ($value == false) ? 0 : 1;
		} else if (is_null($value)) {
			$value = "NULL";
		} else if (is_string($value)) {
			$value = $this->converEscapeString($value);
		}
		return $value;
	}
	
	/**
	 * Convertie les clès
	 * 
	 * @param $key
	 * @return $key
	 */
	private function converKey($key) {
		if (is_array($key)) {
			foreach($key as $realKey => $keyValue) {
				$key[$realKey] = $this->converKey($keyValue);
			}
		}
		
		// Convertie les multiples espaces (tabulation, espace en trop) en espace simple
		$key = preg_replace("/[\t ]+/", " ", $key);
		
		return $key;
	}
	
	/**
	 * Retourne le bon espacement dans une string
	 * 
	 * @param $str
	 * @return String
	 */
	private function converEscapeString($str) {
		if (function_exists("mysql_real_escape_string") && is_resource($this->conn_id)) {
			return mysql_real_escape_string($str, $this->conn_id);
		} elseif (function_exists("mysql_escape_string")) {
			return mysql_escape_string($str);
		} else {
			return addslashes($str);
		}
	}
}
?>