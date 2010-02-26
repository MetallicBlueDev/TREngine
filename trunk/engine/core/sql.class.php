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
	protected static $base = null;
	
	/**
	 * Démarre une instance de communication avec la base
	 * 
	 * @param $db array
	 * @return Base_Type
	 */
	public static function &makeInstance($db = array()) {
		if (self::$base == null && count($db) >= 5) {
			// Vérification du type de base de donnée
			if (!is_file(TR_ENGINE_DIR . "/engine/base/" . $db['type'] . ".class.php")) {
				Core_Secure::getInstance()->debug("sqlType");
			}
			
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
	 * Retourne la liste des types de base supporté
	 * 
	 * @return array
	 */
	public static function &listBase() {
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
		
		if (Core_Secure::isDebuggingMode()) Core_Exception::setSqlRequest($sql);
		
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
	 * @param $values array) Sous la forme array("keyName" => "newValue")
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
		if (($isValue && !in_array($s, $this->quoted)) || (!$isValue && strpos($s, "." ) === false && !isset($this->quoted[$s]))) {
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
			$value = ($value == false) ? 0 : 1;
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
		} elseif (function_exists("mysql_escape_string")) {// WARNING: DEPRECATED
			$str = mysql_escape_string($str);
		} else {
			$str = addslashes($str);
		}
		return $str;
	}
}
?>