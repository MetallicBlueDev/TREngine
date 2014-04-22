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
    private $database = array(
        "host" => "",
        "user" => "",
        "pass" => "",
        "name" => "",
        "type" => "",
        "prefix" => ""
    );

    /**
     * Objet de connexion.
     *
     * @var mixed (resource / mysqli / etc.)
     */
    protected $connId = null;

    /**
     * Dernier resultat de la dernière requête SQL.
     *
     * @var resource
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
     * Tableau contenant les clés déjà quotées.
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
     * @throws Fail_Sql
     */
    public function initializeBase(array $database) {
        if ($this->database === null) {
            $this->database = $database;

            if ($this->test()) {
                // Connexion au serveur
                $this->dbConnect();

                if (!$this->connected()) {
                    throw new Fail_Sql("sqlConnect");
                }

                // Sélection d'une base de données
                if (!$this->dbSelect()) {
                    throw new Fail_Sql("sqlDbSelect");
                }
            } else {
                throw new Fail_Sql("sqlTest");
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
     * Déconnexion de la base de données.
     */
    public function dbDeconnect() {

    }

    /**
     * Sélectionne une base de données.
     *
     * @return boolean true succès
     */
    public function &dbSelect() {
        return false;
    }

    /**
     * Retourne le nombre de lignes affectées à la dernière requête.
     *
     * @return int
     */
    public function &affectedRows() {
        return 0;
    }

    /**
     * Retourne le nom de l'hôte.
     *
     * @return string
     */
    public function getDatabaseHost() {
        return $this->database['host'];
    }

    /**
     * Retourne le nom d'utilisateur.
     *
     * @return string
     */
    public function getDatabaseUser() {
        return $this->database['user'];
    }

    /**
     * Retourne le mot de passe.
     *
     * @return string
     */
    public function getDatabasePass() { // TODO NE PAS METTRE EN PUBLIC.
        return $this->database['pass'];
    }

    /**
     * Retourne le nom de la base de données.
     *
     * @return string
     */
    public function getDatabaseName() {
        return $this->database['name'];
    }

    /**
     * Retourne le type de base (exemple mysqli).
     *
     * @return string
     */
    public function getDatabaseType() {
        return $this->database['type'];
    }

    /**
     * Retourne le préfixe à utiliser sur les tables.
     *
     * @return string
     */
    public function getDatabasePrefix() {
        return $this->database['prefix'];
    }

    /**
     * Supprime des informations.
     *
     * @param string $table Nom de la table
     * @param array $where
     * @param array $like
     * @param string $limit
     */
    public function delete($table, array $where = array(), array $like = array(), $limit = "") {
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

        $limit = empty($limit) ? "" : " LIMIT " . $limit;

        $this->sql = "DELETE FROM " . $table . $whereValue . $likeValue . $limit;
    }

    /**
     * Retourne un tableau qui contient la ligne demandée.
     *
     * @return array
     */
    public function &fetchArray() {
        return array();
    }

    /**
     * Retourne un objet qui contient la ligne demandée.
     *
     * @return object
     */
    public function &fetchObject() {
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

        $this->sql = "INSERT INTO " . $table . " ("
        . implode(", ", $this->converKey($keys)) . ") VALUES ("
        . implode(", ", $this->converValue($values)) . ")";
    }

    /**
     * Retourne l'id de la dernière ligne inserée.
     *
     * @return int
     */
    public function &insertId() {
        return 0;
    }

    /**
     * Envoi une requête Sql.
     *
     * @param $sql
     */
    public function query($sql) {
        unset($sql);
    }

    /**
     * Sélection d'information.
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function select($table, array $values, array $where = array(), array $orderby = array(), $limit = "") {
        // Nom complet de la table
        $table = $this->getTableName($table);

        // Mise en place des valeurs sélectionnées
        $valuesValue = implode(", ", $values);

        // Mise en place du where
        $whereValue = (count($where) >= 1) ? " WHERE " . implode(" ", $where) : "";

        // Mise en place de la limite
        $limit = empty($limit) ? "" : " LIMIT " . $limit;

        // Mise en place de l'ordre
        $orderbyValue = (count($orderby) >= 1) ? " ORDER BY " . implode(", ", $orderby) : "";

        // Mise en forme de la requête finale
        $this->sql = "SELECT " . $valuesValue . " FROM " . $table . $whereValue . $orderbyValue . $limit;
    }

    /**
     * Mise à jour d'une table.
     *
     * @param string $table Nom de la table
     * @param array $values Sous la forme array("keyName" => "newValue")
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function update($table, array $values, array $where, array $orderby = array(), $limit = "") {
        // Nom complet de la table
        $table = $this->getTableName($table);

        // Affectation des clès à leurs valeurs
        $valuesString = array();
        foreach ($values as $key => $value) {
            $valuesString[] = $this->converKey($key) . " = " . $this->converValue($value, $key);
        }

        $whereValue = (count($where) >= 1) ? " WHERE " . implode(" ", $where) : "";

        // Mise en place de la limite
        $limit = empty($limit) ? "" : " LIMIT " . $limit;

        // Mise en place de l'ordre
        $orderbyValue = (count($orderby) >= 1) ? " ORDER BY " . implode(", ", $orderby) : "";

        // Mise en forme de la requête finale
        $this->sql = "UPDATE " . $table . " SET " . implode(", ", $valuesString) . $whereValue . $orderbyValue . $limit;
    }

    /**
     * Retourne le dernier résultat de la dernière requête executée.
     *
     * @return resource
     */
    public function &getQueries() {
        return $this->queries;
    }

    /**
     * Retourne la dernière requête sql.
     *
     * @return string
     */
    public function &getSql() {
        return $this->sql;
    }

    /**
     * Retourne l'état de la connexion.
     *
     * @return boolean
     */
    public function &connected() {
        return ($this->connId !== null) ? true : false;
    }

    /**
     * Libère la mémoire du resultat.
     *
     * @param resource $querie
     * @return boolean
     */
    public function &freeResult($querie) {
        unset($querie);
        return false;
    }

    /**
     * Ajoute le dernier résultat dans le buffer typé en tableau.
     *
     * @param string $name
     * @param string $key clé à utiliser
     */
    public function addArrayBuffer($name, $key) {
        if (!isset($this->buffer[$name])) {
            foreach ($this->fetchArray() as $row) {
                if (!empty($key)) {
                    $this->buffer[$name][$row[$key]] = $row;
                } else {
                    $this->buffer[$name][] = $row;
                }
            }

            reset($this->buffer[$name]);
        }
    }

    /**
     * Ajoute le dernier résultat dans le buffer typé en object.
     *
     * @param string $name
     * @param string $key clé à utiliser
     */
    public function addObjectBuffer($name, $key) {
        if (!isset($this->buffer[$name])) {
            foreach ($this->fetchObject() as $row) {
                if (!empty($key)) {
                    $this->buffer[$name][$row->$key] = $row;
                } else {
                    $this->buffer[$name][] = $row;
                }
            }

            reset($this->buffer[$name]);
        }
    }

    /**
     * Retourne le buffer courant puis l'incrémente.
     *
     * @param string $name
     * @return array / array - object
     */
    public function &fetchBuffer($name) {
        $buffer = current($this->buffer[$name]);
        next($this->buffer[$name]);
        return $buffer;
    }

    /**
     * Retourne le buffer complet demandé.
     *
     * @param string $name
     * @return array / array - object
     */
    public function &getBuffer($name) {
        return $this->buffer[$name];
    }

    /**
     * Vérifie si la plateforme est disponible.
     *
     * @return boolean
     */
    public function &test() {
        return false;
    }

    /**
     * Retourne les dernières erreurs.
     *
     * @return array
     */
    public function &getLastError() {
        return array(
            "<b>Last Sql query</b> : " . $this->getSql());
    }

    /**
     * Marquer une clé et/ou une valeur comme déjà quoté.
     *
     * @param string $key
     * @param boolean $value
     */
    public function addQuoted($key, $value = true) {
        if (!empty($key)) {
            $this->quoted[$key] = $value;
        } else {
            $this->quoted[] = $value;
        }
    }

    /**
     * Remise à zéro du tableau de clés déjà quoté.
     */
    public function resetQuoted() {
        $this->quoted = array();
    }

    /**
     * Retourne la version de la base.
     *
     * @return string
     */
    public function &getVersion() {
        return "?";
    }

    /**
     * Retourne le type d'encodage de la base de données.
     *
     * @return string
     */
    public function &getCollation() {
        $this->query("SHOW FULL COLUMNS FROM " . $this->getTableName(Core_Table::$CONFIG_TABLE));
        $info = $this->fetchArray();
        return !empty($info['Collation']) ? $info['Collation'] : "?";
    }

    /**
     * Quote les identifiants et les valeurs.
     *
     * @param string $s
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
     * Conversion des valeurs dite PHP en valeurs semblable SQL.
     *
     * @param object $value
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
     * @param object $key
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
     * @param string $str
     * @return string
     */
    protected function &converEscapeString($str) {
        return addslashes($str);
    }

    /**
     * Retourne le nom de la table avec le préfixage.
     *
     * @param string
     */
    protected function &getTableName($table) {
        return $this->getDatabasePrefix() . "_" . $table;
    }

}
