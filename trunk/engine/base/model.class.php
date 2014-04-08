<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("model.class.php");
    new Core_Secure();
}

/**
 * Modèle de base de la communication SQL.
 *
 * @author Sébastien Villemain
 */
abstract class Base_Model {

    /**
     * Configuration de la base de données.
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
     * ID de la connexion.
     *
     * @var string
     */
    protected $connId = false;

    /**
     * Dernier resultat de la dernière requête SQL.
     *
     * @var string ressources ID.
     */
    protected $queries = "";

    /**
     * Dernière requête SQL.
     *
     * @var string
     */
    protected $sql = "";

    /**
     * Buffer sous forme de tableau array contenant des objets standards.
     *
     * @var array - object
     */
    protected $buffer = array();

    /**
     * Quote pour les objects, champs...
     *
     * @var string
     */
    protected $quoteKey = "`";

    /**
     * Quote pour les valeurs uniquement.
     *
     * @var string
     */
    protected $quoteValue = "'";

    /**
     * Tableau contenant les cles déjà quotées.
     *
     * @var array
     */
    protected $quoted = array();

    /**
     * Nouveau modèle de base.
     */
    public function __construct() {
        $this->database = null;
    }

    /**
     * Paramètre la connexion, test la connexion puis engage une connexion.
     *
     * @param array $database
     * @throws Exception
     */
    public function initializeBase(array $database) {
        if ($this->database === null) {
            $this->database = $database;

            if ($this->test()) {
                // Connexion au serveur
                $this->dbConnect();
                if (!$this->isConnected()) {
                    throw new Exception("sqlConnect");
                }

                // Sélection d'une base de données
                if (!$this->dbSelect()) {
                    throw new Exception("sqlDbSelect");
                }
            } else {
                throw new Exception("sqlTest");
            }
        }
    }

    /**
     * Destruction de la communication.
     */
    public function __destruct() {
        $this->dbDeconnect();
    }

    /**
     * Etablie une connexion à la base de données.
     */
    public function dbConnect() {

    }

    /**
     * Sélectionne une base de données.
     *
     * @return boolean true succes
     */
    public function dbSelect() {
        return false;
    }

    /**
     * Retourne le nombre de lignes affectées à la dernière requête.
     *
     * @return int
     */
    public function affectedRows() {
        return 0;
    }

    /**
     * Supprime des informations.
     *
     * @param string $table Nom de la table
     * @param array $where
     * @param array $like
     * @param string $limit
     */
    public function delete($table, array $where = array(), array $like = array(), $limit = false) {
        // Nom complet de la table
        $table = $this->getTableName($table);

        // Mise en place du WHERE
        $whereValue = (count($where) >= 1) ? " WHERE " . implode(" ", $where) : "";

        // Mise en place du LIKE
        $likeValue = (count($like) >= 1) ? " LIKE " . implode(" ", $like) : "";

        // Fonction ET entre WHERE et LIKE
        if (!empty($whereValue) && !empty($likeValue)) {
            $whereValue .= "AND";
        }

        $limitValue = (!$limit) ? "" : " LIMIT " . $limit;
        $sql = "DELETE FROM " . $table . $whereValue . $likeValue . $limitValue;
        $this->sql = $sql;
    }

    /**
     * Retourne un tableau qui contient la ligne demandée.
     *
     * @return array
     */
    public function fetchArray() {
        return array();
    }

    /**
     * Retourne un objet qui contient la ligne demandée.
     *
     * @return object
     */
    public function fetchObject() {
        return array();
    }

    /**
     * Insère une ou des valeurs dans une table.
     *
     * @param string $table Nom de la table
     * @param array $keys
     * @param array $values
     */
    public function insert($table, array $keys, array $values) {
        // Nom complet de la table
        $table = $this->getTableName($table);

        $sql = "INSERT INTO " . $table . " ("
        . implode(", ", $this->converKey($keys)) . ") VALUES ("
        . implode(", ", $this->converValue($values)) . ")";
        $this->sql = $sql;
    }

    /**
     * Retourne l'id de la dernière ligne inserée.
     *
     * @return int
     */
    public function insertId() {
        return 0;
    }

    /**
     * Envoi une requête Sql.
     *
     * @param $sql
     */
    public function query($sql) {

    }

    /**
     * Sélection d'information.
     *
     * @param $table string
     * @param $values array
     * @param $where array
     * @param $orderby array
     * @param $limit string
     */
    public function select($table, $values, $where = array(), $orderby = array(), $limit = false) {
        // Nom complet de la table
        $table = $this->getTableName($table);

        // Mise en place des valeurs sélectionnées
        if (!is_array($values)) {
            $values = array(
                $values);
        }
        $values = implode(", ", $values);

        // Mise en place du where
        if (!is_array($where)) {
            $where = array(
                $where);
        }
        $where = (count($where) >= 1) ? " WHERE " . implode(" ", $where) : "";
        // Mise en place de la limite
        $limit = (!$limit) ? "" : " LIMIT " . $limit;
        // Mise en place de l'ordre
        $orderby = (count($orderby) >= 1) ? " ORDER BY " . implode(", ", $orderby) : "";

        // Mise en forme de la requête finale
        $sql = "SELECT " . $values . " FROM " . $table . $where . $orderby . $limit;
        $this->sql = $sql;
    }

    /**
     * Mise à jour d'une table.
     *
     * @param $table Nom de la table
     * @param $values array) Sous la forme array("keyName" => "newValue")
     * @param $where array
     * @param $orderby array
     * @param $limit string
     */
    public function update($table, $values, $where, $orderby = array(), $limit = false) {
        // Nom complet de la table
        $table = $this->getTableName($table);

        // Affectation des clès à leurs valeurs
        foreach ($values as $key => $value) {
            $valuesString[] = $this->converKey($key) . " = " . $this->converValue($value, $key);
        }

        // Mise en place du where
        if (!is_array($where)) {
            $where = array(
                $where);
        }
        // Mise en place de la limite
        $limit = (!$limit) ? "" : " LIMIT " . $limit;
        // Mise en place de l'ordre
        $orderby = (count($orderby) >= 1) ? " ORDER BY " . implode(", ", $orderby) : "";

        // Mise en forme de la requête finale
        $sql = "UPDATE " . $table . " SET " . implode(", ", $valuesString);
        $sql .= (count($where) >= 1) ? " WHERE " . implode(" ", $where) : "";
        $sql .= $orderby . $limit;
        $this->sql = $sql;
    }

    /**
     * Retourne le dernier résultat de la dernière requête executée.
     *
     * @return Ressource ID
     */
    public function getQueries() {
        return $this->queries;
    }

    /**
     * Retourne la dernière requête sql.
     *
     * @return string
     */
    public function getSql() {
        return $this->sql;
    }

    /**
     * Retourne l'état de la connexion.
     *
     * @return boolean
     */
    public function isConnected() {
        return ($this->connId != false) ? true : false;
    }

    /**
     * Libère la mémoire du resultat.
     *
     * @param $querie Resource Id
     * @return boolean
     */
    public function freeResult($querie) {
        return false;
    }

    /**
     * Ajoute le dernier résultat dans le buffer.
     *
     * @param $name string
     * @param $key string clé à utiliser
     */
    public function addBuffer($name, $key) {
        if (!isset($this->buffer[$name])) {
            while ($row = $this->fetchObject()) {
                if (!empty($key)) {
                    $this->buffer[$name][$row->$key] = $row;
                } else {
                    $this->buffer[$name][] = $row;
                }
            }
            $reset = $this->buffer[$name][0];
        }
    }

    /**
     * Retourne le buffer courant puis l'incrémente.
     *
     * @param $name string
     * @return array - object
     */
    public function &fetchBuffer($name) {
        $buffer = current($this->buffer[$name]);
        next($this->buffer[$name]);
        return $buffer;
    }

    /**
     * Retourne le buffer complet demandé.
     *
     * @param $name string
     * @return array - object
     */
    public function getBuffer($name) {
        return $this->buffer[$name];
    }

    /**
     * Vérifie si la plateform est disponible.
     *
     * @return boolean
     */
    public function test() {
        return false;
    }

    /**
     * Retourne les dernières erreurs.
     *
     * @return array
     */
    public function &getLastError() {
        $error = array(
            "<b>Last Sql query</b> : " . $this->getSql());
        return $error;
    }

    /**
     * Quote les identifiants et les valeurs.
     *
     * @param $s string
     * @return string
     */
    protected function &addQuote($s, $isValue = false) {
        // Ne pas quoter les champs avec la notation avec les point
        if (($isValue && !Exec_Utils::inArray($s, $this->quoted)) || (!$isValue && strpos($s, ".") === false && !isset($this->quoted[$s]))) {
            if ($isValue) {
                $q = $this->quoteValue;
            } else {
                $q = $this->quoteKey;
            }
            $s = $q . $s . $q;
        }
        return $s;
    }

    /**
     * Marqué une cles et/ou une valeur comme déjà quoté.
     *
     * @param $key string
     */
    public function addQuoted($key, $value = 1) {
        if (!empty($key)) {
            $this->quoted[$key] = $value;
        } else {
            $this->quoted[] = $value;
        }
    }

    /**
     * Remise à zéro du tableau de cles déjà quoté.
     */
    public function resetQuoted() {
        $this->quoted = array();
    }

    /**
     * Conversion des valeurs dite PHP en valeurs semblable SQL
     *
     * @param $value object
     * @return object
     */
    protected function &converValue($value) {
        if (is_array($value)) {
            foreach ($value as $realKey => $realValue) {
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
        if (!is_array($value)) {
            $value = $this->addQuote($value, true);
        }
        return $value;
    }

    /**
     * Conversion des clès.
     *
     * @param $key object
     * @return object
     */
    protected function &converKey($key) {
        if (is_array($key)) {
            foreach ($key as $realKey => $keyValue) {
                $key[$realKey] = $this->converKey($keyValue);
            }
        } else {
            $key = $this->addQuote($key);
        }

        // Converti les multiples espaces (tabulation, espace en trop) en espace simple
        $key = preg_replace("/[\t ]+/", " ", $key);
        return $key;
    }

    /**
     * Retourne le bon espacement dans une string.
     *
     * @param $str string
     * @return string
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
     * Retourne la version de la base.
     *
     * @return string
     */
    public function getVersion() {
        return "?";
    }

    /**
     * Retourne le type d'encodage de la base de données.
     *
     * @return string
     */
    public function getCollation() {
        $this->query("SHOW FULL COLUMNS FROM " . $this->getTableName(Core_Table::$CONFIG_TABLE));
        $info = $this->fetchArray();
        return !empty($info['Collation']) ? $info['Collation'] : "?";
    }

    /**
     * Retourne le nom de la table avec le préfixage.
     *
     * @param string
     */
    public function getTableName($table) {
        return $this->database['prefix'] . "_" . $table;
    }

}
