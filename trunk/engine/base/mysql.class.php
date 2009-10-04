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
class Base_Mysql extends Base_Model {
	
	/**
	 * Le type de la dernière commande
	 * SELECT, DELETE, INSERT, REPLACE, UPDATE
	 * 
	 * @var String
	 */
	private $lastSqlCommand = "";
	
	public function __construct($db) {
		parent::__construct($db);
	}
	
	public function __destruct() {
		parent::__destruct();
	}
	
	/**
	 * Etablie une connexion à la base de donnée
	 */
	public function dbConnect() {
		$this->connId = @mysql_connect($this->dbHost, $this->dbUser, $this->dbPass);
	}
	
	/**
	 * Selectionne une base de donnée
	 * 
	 * @return boolean true succes
	 */
	public function &dbSelect() {
		if ($this->connId) {
			return @mysql_select_db($this->dbName, $this->connId);
		}
		return false;
	}
	
	/**
	 * Déconnexion à la base de donnée
	 */
	public function dbDeconnect() {
		if ($this->connId) {
			$this->connId = @mysql_close($this->connId);
		}
		$this->connId = false;
	}
	
	/**
	 * Envoie une requête Sql
	 * 
	 * @param $Sql
	 */
	public function query($sql) {
		$this->queries = @mysql_query($sql, $this->connId);
	}
	
	/**
	 * Retourne un tableau qui contient la ligne demandée
	 * 
	 * @return array
	 */
	public function &fetchArray() {
		return mysql_fetch_array($this->queries, MYSQL_ASSOC);
	}
	
	/**
	 * Retourne un objet qui contient les ligne demandée
	 * 
	 * @return object
	 */
	public function &fetchObject() {
		return mysql_fetch_object($this->queries);
	}
	
	/**
	 * Libere la memoire du resultat
	 * 
	 * @param $querie Resource Id
	 * @return boolean
	 */
	public function &freeResult($querie) {
		if (is_resource($querie)) {
			return mysql_free_result($querie);
		}
		return false;
	}
	
	/**
	 * Get number of LAST affected rows
	 * 
	 * @return int
	 */
	public function &affectedRows() {
		if ($this->lastSqlCommand == "SELECT" || $this->lastSqlCommand == "SHOW") {
			return $this->mysqlNumRows($this->queries);
		}
		return $this->mysqlAffectedRows();
	}
	
	/**
	 * Get number of LAST affected rows (for DELETE, UPDATE, INSERT, REPLACE)
	 * 
	 * @return int
	 */
	private function &mysqlAffectedRows() {
		return mysql_affected_rows($this->connId);
	}
	
	/**
	 * Get number of affected rows (for SELECT only)
	 * 
	 * @param $queries
	 * @return int
	 */
	private function &mysqlNumRows($queries) {
		return mysql_num_rows($queries);
	}
	
	/**
	 * Get the ID generated from the previous INSERT operation
	 * 
	 * @return int
	 */
	public function &insertId() {
		return mysql_insert_id($this->connId);
	}
	
	/**
	 * Mise à jour d'une table
	 * 
	 * @param $table Nom de la table
	 * @param $values array) Sous la forme array("keyName" => "newValue")
	 * @param $where array
	 * @param $orderby array
	 * @param $limit String
	 */
	public function update($table, $values, $where, $orderby = array(), $limit = false) {
		$this->lastSqlCommand = "UPDATE";
		
		// Affectation des clès a leurs valeurs
		foreach($values as $key => $value) {
			$valuesString[] = $this->converKey($key) ." = " . $this->converValue($value, $key);
		}
		
		// Mise en place du where
		if (!is_array($where)) $where = array($where);
		// Mise en place de la limite
		$limit = (!$limit) ? "" : " LIMIT " . $limit;
		// Mise en place de l'ordre
		$orderby = (count($orderby) >= 1)? " ORDER BY " . implode(", ", $orderby): "";
		
		// Mise en forme de la requête finale
		$sql = "UPDATE " . $table . " SET " . implode(", ", $valuesString);
		$sql .= (count($where) >= 1) ? " WHERE " . implode(" ", $where) : "";
		$sql .= $orderby . $limit;
		$this->sql = $sql;
	}
	
	/**
	 * Insere une ou des valeurs dans une table
	 * 
	 * @param $table String Nom de la table
	 * @param $keys array
	 * @param $values array
	 */
	public function insert($table, $keys, $values) {
		$this->lastSqlCommand = "INSERT";
		
		if (!is_array($keys)) $keys = array($keys);
		if (!is_array($values)) $values = array($values);
		
		$sql = "INSERT INTO " . $table . " ("
		. implode(", ", $this->converKey($keys)) . ") VALUES ('"
		. implode("', '", $this->converValue($values)) . "')";
		$this->sql = $sql;
	}
	
	/**
	 * Supprime des informations
	 * 
	 * @param $table String Nom de la table
	 * @param $where array
	 * @param $like array
	 * @param $limit String
	 */
	public function delete($table, $where = array(), $like = array(), $limit = false) {
		$this->lastSqlCommand = "DELETE";
		
		// Mise en place du WHERE
		if (!is_array($where)) $where = array($where);
		$where = (count($where) >= 1) ? " WHERE " . implode(" ", $where) : "";
		
		// Mise en place du LIKE
		$like = (!is_array($like)) ? array($like) : $like;
		$like = (count($like) >= 1) ? " LIKE " . implode(" ", $like) : "";
		
		// Fonction ET entre WHERE et LIKE
		if (!empty($where) && !empty($like)) {
			$where .= "AND";
		}

		$limit = (!$limit) ? "" : " LIMIT " . $limit;
		$sql = "DELETE FROM " . $table . $where . $like . $limit;
		$this->sql = $sql;
	}
	
	/**
	 * Selection d'information
	 * 
	 * @param $table String
	 * @param $values array
	 * @param $where array
	 * @param $orderby array
	 * @param $limit String
	 */
	public function select($table, $values, $where = array(), $orderby = array(), $limit = false) {
		$this->lastSqlCommand = "SELECT";
		
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
		$this->sql = $sql;
	}
	
	/**
	 * Vérifie que le module mysql est chargé
	 * 
	 * @return boolean
	 */
	public function &test() {
		return (function_exists("mysql_connect"));
	}
	
	/**
	 * Retourne les dernieres erreurs
	 * 
	 * @return array
	 */
	public function &getLastError() {
		$error = parent::getLastError();
		$error[] ="<b>MySql response</b> : " . mysql_error();
		return $error;
	}
}
?>