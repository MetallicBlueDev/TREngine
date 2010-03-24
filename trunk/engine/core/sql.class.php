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
	private static $base = null;
	
	/**
	 * Donnée de la base
	 * 
	 * @var array
	 */
	private static $dataBase = array();
	
	/**
	 * Démarre une instance de communication avec la base
	 */
	public static function makeInstance() {
		if (self::$base == null && !empty(self::$dataBase)) {
			// Chargement des drivers pour la base
			$BaseClass = "Base_" . ucfirst(self::$dataBase['type']);
			Core_Loader::classLoader($BaseClass);
			
			// Si la classe peut être utilisée
			if (Core_Loader::isCallable($BaseClass)) {
				try {
					self::$base = new $BaseClass();
				} catch (Exception $ie) {
					Core_Secure::getInstance()->debug($ie);
				}
			} else {
				Core_Secure::getInstance()->debug("sqlCode", $BaseClass);
			}
			
			// Charge les constantes de table
			Core_Loader::classLoader("Core_Table");
		}
	}
	
	/**
	 * Retourne la liste des types de base supporté
	 * 
	 * @return array
	 */
	public static function &listBases() {
		$baseList = array();
		$files = Core_CacheBuffer::listNames("engine/base");
		foreach($files as $key => $fileName) {
			// Nettoyage du nom de la page
			$pos = strpos($fileName, ".class");
			// Si c'est une page administrable
			if ($pos !== false && $pos > 0) {
				$baseList[] = substr($fileName, 0, $pos);
			}
		}
		return $baseList;
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
		return self::$base->dbSelect();;
	}
	
	/**
	 * Get number of LAST affected rows (for all : SELECT, SHOW, DELETE, INSERT, REPLACE and UPDATE)
	 * 
	 * @return int
	 */
	public static function affectedRows() {
		return self::$base->affectedRows();
	}
	
	/**
	 * Supprime des informations
	 * 
	 * @param $table String Nom de la table
	 * @param $where array
	 * @param $like array
	 * @param $limit String
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
	 * Retourne un tableau qui contient le ligne demandée
	 * 
	 * @return array
	 */
	public static function fetchArray() {
		return self::$base->fetchArray();
	}
	
	/**
	 * Retourne un objet qui contient le ligne demandée
	 * 
	 * @return object
	 */
	public static function fetchObject() {
		return self::$base->fetchObject();
	}
	
	/**
	 * Insere une ou des valeurs dans une table
	 * 
	 * @param $table String Nom de la table
	 * @param $keys array
	 * @param $values array
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
	 * Envoie une requête Sql
	 * 
	 * @param $Sql
	 */
	public static function query($sql = "") {
		$sql = (!empty($sql)) ? $sql : self::getSql();
		self::$base->query($sql);
		self::$base->resetQuoted();
		
		// Ajout la requête au log
		if (Core_Secure::isDebuggingMode()) Core_Exception::setSqlRequest($sql);
		
		// Création d'une exception si une réponse est négative (false)
		if (self::getQueries() === false) throw new Exception("sqlReq");
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
	 * @param $values array Sous la forme array("keyName" => "newValue")
	 * @param $where array
	 * @param $orderby array
	 * @param $limit String
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
	 * @return Ressource ID
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
	 * Retourne le buffer complet choisi
	 * Retourne un tableau Sdt object
	 * 
	 * @param $name String
	 * @return array - object
	 */
	public static function getBuffer($name) {
		return self::$base->getBuffer($name);
	}
	
	/**
	 * Retourne les dernieres erreurs
	 * 
	 * @return array
	 */
	public static function getLastError() {
		return self::$base->getLastError();
	}
	
	/**
	 * Marqué une cles comme déjà quoté
	 * 
	 * @param $key String
	 */
	public static function addQuoted($key, $value = 1) {
		self::$base->addQuoted($key, $value);
	}
	
	/**
	 * Retourne la version de la base de donnée
	 * 
	 * @return String
	 */
	public static function getVersion() {
		return self::$base->getVersion();
	}
	
	/**
	 * Retourne le type d'encodage de la base de donnée
	 * 
	 * @return String
	 */
	public static function getCollation() {
		return self::$base->getCollation();
	}
	
	/**
	 * Récupération des données de la base
	 * 
	 * @return array
	 */
	public static function getDatabase() {
		return self::$dataBase;
	}
	
	/**
	 * Injection des données de la base
	 * 
	 * @param array
	 */
	public static function setDatabase($database = array()) {
		self::$dataBase = $database;
	}
}

/**
 * Gestionnaire de la communication SQL
 * 
 * @author Sébastien Villemain
 * 
 */
abstract class Base_Model {
	
	/**
	 * Configuration de la base de donnée
	 * 
	 * @var array
	 */
	protected $database = array(
		"host" => "",
		"user" => "",
		"pass" => "",
		"name" => "",
		"type" => "",
		"prefix" => ""
	);
	
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
	 * Quote pour les objects, champs...
	 * 
	 * @var String
	 */
	protected $quoteKey = "`";
	
	/**
	 * Quote pour les valeurs uniquement
	 * 
	 * @var String
	 */
	protected $quoteValue = "'";
	
	/**
	 * Tableau contenant les cles déjà quoté
	 * 
	 * @var array
	 */
	protected $quoted = array();
	
	/**
	 * Parametre la connexion, test la connexion puis engage une connexion
	 * 
	 * @param $db array
	 */
	public function __construct() {
		$this->database = Core_Sql::getDatabase();
		
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
	 * Destruction de la communication
	 */
	public function __destruct() {
		$this->dbDeconnect();
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
	 * @param $table String Nom de la table
	 * @param $where array
	 * @param $like array
	 * @param $limit String
	 */
	public function delete($table, $where = array(), $like = array(), $limit = false) {
		// Nom complet de la table
		$table = $this->getTableName($table);
		
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
	 * Insere une ou des valeurs dans une table
	 * 
	 * @param $table String Nom de la table
	 * @param $keys array
	 * @param $values array
	 */
	public function insert($table, $keys, $values) {
		// Nom complet de la table
		$table = $this->getTableName($table);
		
		if (!is_array($keys)) $keys = array($keys);
		if (!is_array($values)) $values = array($values);
		
		$sql = "INSERT INTO " . $table . " ("
		. implode(", ", $this->converKey($keys)) . ") VALUES ('"
		. implode("', '", $this->converValue($values)) . "')";
		$this->sql = $sql;
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
	 * Envoie une requête Sql
	 * 
	 * @param $Sql
	 */
	public function query($sql) {
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
		// Nom complet de la table
		$table = $this->getTableName($table);
		
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
	 * Mise à jour d'une table
	 * 
	 * @param $table Nom de la table
	 * @param $values array) Sous la forme array("keyName" => "newValue")
	 * @param $where array
	 * @param $orderby array
	 * @param $limit String
	 */
	public function update($table, $values, $where, $orderby = array(), $limit = false) {
		// Nom complet de la table
		$table = $this->getTableName($table);
		
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
	 * Retourne le dernier résultat de la dernière requête executée
	 *
	 * @return Ressource ID
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
				if (!empty($key)) $this->buffer[$name][$row->$key] = $row;
				else $this->buffer[$name][] = $row;
			}
			$reset = $this->buffer[$name][0];
		}
	}
	
	/**
	 * Retourne le buffer courant puis l'incremente
	 * 
	 * @param $name String
	 * @return array - object
	 */
	public function &fetchBuffer($name) {
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
	
	/**
	 * Retourne les dernieres erreurs
	 * 
	 * @return array
	 */
	public function &getLastError() {
		$error = array("<b>Last Sql query</b> : " . $this->getSql());
		return $error;
	}
	
	/**
	 * Quote les identifiants et les valeurs
	 * 
	 * @param $s String
	 * @return String
	 */
	protected function &addQuote($s, $isValue = false) {
		// Ne pas quoter les champs avec la notation avec les point
		if (($isValue && !Exec_Utils::inArray($s, $this->quoted)) || (!$isValue && strpos($s, "." ) === false && !isset($this->quoted[$s]))) {
			if ($isValue) $q = $this->quoteValue;
			else $q = $this->quoteKey;
			$s = $q . $s . $q;
		}
		return $s;
	}
	
	/**
	 * Marqué une cles et/ou une valeur comme déjà quoté
	 * 
	 * @param $key String
	 */
	public function addQuoted($key, $value = 1) {
		if (!empty($key)) $this->quoted[$key] = $value;
		else $this->quoted[] = $value;
	}
	
	/**
	 * Remise à zéro du tableau de cles déjà quoté
	 */
	public function resetQuoted() {
		$this->quoted = array();
	}
	
	/**
	 * Conversion des valeurs dite PHP en valeurs semblable SQL
	 * 
	 * @param $value Object
	 * @return Object
	 */
	protected function &converValue($value) {
		if (is_array($value)) {
			foreach($value as $realKey => $realValue) {
				$value[$realKey] = $this->converValue($realValue);
			}
		}
		
		if (is_bool($value)) {
			$value = ($value == true) ? 1 : 0;
		} else if (is_null($value)) {
			$value = "NULL";
		} else if (is_string($value)) {
			$value = $this->converEscapeString($value);
		}
		$value = $this->addQuote($value, true);
		return $value;
	}
	
	/**
	 * Conversion des clès
	 * 
	 * @param $key Object
	 * @return Object
	 */
	protected function &converKey($key) {
		if (is_array($key)) {
			foreach($key as $realKey => $keyValue) {
				$key[$realKey] = $this->converKey($keyValue);
			}
		} else {
			$key = $this->addQuote($key);
		}
		
		// Convertie les multiples espaces (tabulation, espace en trop) en espace simple
		$key = preg_replace("/[\t ]+/", " ", $key);
		return $key;
	}
	
	/**
	 * Retourne le bon espacement dans une string
	 * 
	 * @param $str String
	 * @return String
	 */
	protected function &converEscapeString($str) {
		if (function_exists("mysql_real_escape_string") && is_resource($this->connId)) {
			$str = mysql_real_escape_string($str, $this->connId);
		} else if (function_exists("mysql_escape_string")) {// WARNING: DEPRECATED
			$str = mysql_escape_string($str);
		} else {
			$str = addslashes($str);
		}
		return $str;
	}
	
	/**
	 * Retourne la version de la base
	 * 
	 * @return String
	 */
	public function getVersion() {
		return "";
	}
	
	/**
	 * Retourne le type d'encodage de la base de donnée
	 * 
	 * @return String
	 */
	public function getCollation() {
		$this->query("SHOW FULL COLUMNS FROM " . Core_Table::$CONFIG_TABLE);
		$info = $this->fetchArray();
		return !empty($info['Collation']) ? $info['Collation'] : "?";
	}
	
	/**
	 * Retourne le nom de la table avec le préfixage
	 * 
	 * @param String
	 */
	public function getTableName($table) {
		return $this->database['prefix'] . "_" . $table;
	}
}
?>