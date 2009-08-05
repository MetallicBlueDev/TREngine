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
abstract class Base_Model {
	
	/**
	 * Nom d'host de la base
	 * 
	 * @var String
	 */
	protected $dbHost = "";
	
	/**
	 * Nom d'utilisateur de la base
	 * 
	 * @var String
	 */
	protected $dbUser = "";
	
	/**
	 * Mot de passe de la base
	 * 
	 * @var String
	 */
	protected $dbPass = "";
	
	/**
	 * Nom de la base
	 * 
	 * @var String
	 */
	protected $dbName = "";
	
	/**
	 * Type de base de donnée
	 * 
	 * @var String
	 */
	protected $dbType = "";
	
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
	
	/**
	 * Buffer sous forme de tableau array contenant des objets stanndarts
	 * 
	 * @var array - object
	 */
	protected $buffer = array();
	
	/**
	 * Parametre la connexion, test la connexion puis engage une connexion
	 * 
	 * @param $db array
	 */
	public function __construct($db) {
		$this->dbHost = $db['host'];
		$this->dbUser = $db['user'];
		$this->dbPass = $db['pass'];
		$this->dbName = $db['name'];
		$this->dbType = $db['type'];
		
		if ($this->test()) {
			// Connexion au serveur
			$this->dbConnect();
			if (!$this->isConnected()) {
				throw new Exception("sqlConnect");
			}
			
			// Selection d'une base de donnée
			if (!$this->dbSelect()) {
				throw new Exception("sqlDbSelect");
			}
		} else {
			throw new Exception("sqlTest");
		}
	}
	
	/**
	 * Etablie une connexion à la base de donnée
	 */
	public function dbConnect() {
	}
	
	/**
	 * Selectionne une base de donnée
	 * 
	 * @return boolean true succes
	 */
	public function dbSelect() {
		return false;
	}
	
	/**
	 * Get number of LAST affected rows 
	 * 
	 * @return int
	 */
	public function affectedRows() {
		return 0;
	}
	
	/**
	 * Supprime des informations
	 * 
	 * @param $table Nom de la table
	 * @param $where
	 * @param $like
	 * @param $limit
	 */
	public function delete($table, $where = array(), $like = array(), $limit = false) {
	}
	
	/**
	 * Retourne un tableau qui contient les lignes demandées
	 * 
	 * @return array
	 */
	public function fetchArray() {
		return array();
	}
	
	/**
	 * Retourne un objet qui contient les lignes demandées
	 * 
	 * @return object
	 */
	public function fetchObject() {
		return array();
	}
	
	/**
	 * Insere de valeurs dans une table
	 * 
	 * @param $table Nom de la table
	 * @param $keys
	 * @param $values
	 */
	public function insert($table, $keys, $values) {
	}
	
	/**
	 * Retourne l'id de la dernière ligne inserée
	 * 
	 * @return int
	 */
	public function insertId() {
		return 0;
	}
	
	/**
	 * Get number of affected rows 
	 * 
	 * @param $queries
	 * @return int
	 */
	public function numRows($queries = "") {
		return 0;
	}
	
	/**
	 * Envoie une requête Sql
	 * 
	 * @param $Sql
	 */
	public function query($sql = "") {
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
	public function select($table, $values, $where = array(), $orderby = array(), $limit = false) {
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
	public function update($table, $values, $where, $orderby = array(), $limit = false) {
	}
	
	/**
	 * Destruction de la communication
	 */
	public function __destruct() {
		$this->dbDeconnect();
	}
	
	/**
	 * Retourne dernier résultat de la dernière requête executée
	 *
	 * @return mixed Ressource ID ou boolean false
	 */
	public function getQueries() {
		return $this->queries;
	}
	
	/**
	 * Retourne la derniere requête sql
	 * 
	 * @return String
	 */
	public function getSql() {
		return $this->sql;
	}
	
	/**
	 * Retourne l'etat de la connexion
	 * 
	 * @return boolean
	 */
	public function isConnected() {
		return ($this->connId != false) ? true : false;
	}
	
	/**
	 * Libere la memoire du resultat
	 * 
	 * @param $querie Resource Id
	 * @return boolean
	 */
	public function freeResult($querie) {
		return false;
	}
	
	/**
	 * Ajoute le dernier résultat dans le buffer
	 * 
	 * @param $key String cles a utiliser
	 * @param $name String
	 */
	public function addBuffer($name, $key) {
		if (!isset($this->buffer[$name])) {
			while ($row = $this->fetchObject()) {
				if ($key) $this->buffer[$name][$row->$key] = $row;
				else $this->buffer[$name][] = $row;
			}
			reset($this->buffer);
		}
	}
	
	/**
	 * Retourne le buffer courant puis l'incremente
	 * 
	 * @param $name String
	 * @return array - object
	 */
	public function fetchBuffer($name) {
		$buffer = current($this->buffer[$name]);
		next($this->buffer[$name]);
		return $buffer;
	}
	
	/**
	 * Retourne le buffer complet demandé
	 * 
	 * @param $name String
	 * @return array - object
	 */
	public function getBuffer($name) {
		return $this->buffer[$name];
	}
	
	/**
	 * Vérifie si la plateform est disponible
	 * 
	 * @return boolean
	 */
	public function test() {
		return false;
	}
}
?>