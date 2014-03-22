<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Gestionnaire de base de donnée MySql.
 *
 * @author Sébastien Villemain
 */
class Base_Mysql extends Base_Model {

    /**
     * Le type de la derni�re commande
     * SELECT, DELETE, UPDATE, INSERT, REPLACE
     *
     * @var String
     */
    private $lastSqlCommand = "";

    /**
     * Etablie une connexion � la base de donn�e
     */
    public function dbConnect() {
        $this->connId = @mysql_connect($this->database['host'], $this->database['user'], $this->database['pass']);
    }

    /**
     * Selectionne une base de donn�e
     *
     * @return boolean true succes
     */
    public function dbSelect() {
        if ($this->connId) {
            return @mysql_select_db($this->database['name'], $this->connId);
        }
        return false;
    }

    /**
     * D�connexion � la base de donn�e
     */
    public function dbDeconnect() {
        if ($this->connId) {
            $this->connId = @mysql_close($this->connId);
        }
        $this->connId = false;
    }

    /**
     * Envoie une requ�te Sql
     *
     * @param $Sql
     */
    public function query($sql) {
        $this->queries = @mysql_query($sql, $this->connId);
    }

    /**
     * Retourne un tableau qui contient la ligne demand�e
     *
     * @return array
     */
    public function fetchArray() {
        if (is_resource($this->queries)) {
            return mysql_fetch_array($this->queries, MYSQL_ASSOC);
        }
        return array();
    }

    /**
     * Retourne un objet qui contient les ligne demand�e
     *
     * @return object
     */
    public function fetchObject() {
        return mysql_fetch_object($this->queries);
    }

    /**
     * Libere la memoire du resultat
     *
     * @param $querie Resource Id
     * @return boolean
     */
    public function freeResult($querie) {
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
    public function affectedRows() {
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
    private function mysqlAffectedRows() {
        return mysql_affected_rows($this->connId);
    }

    /**
     * Get number of affected rows (for SELECT only)
     *
     * @param $queries
     * @return int
     */
    private function mysqlNumRows($queries) {
        return mysql_num_rows($queries);
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
     * V�rifie que le module mysql est charg�
     *
     * @return boolean
     */
    public function test() {
        return (function_exists("mysql_connect"));
    }

    /**
     * Retourne les dernieres erreurs
     *
     * @return array
     */
    public function &getLastError() {
        $error = parent::getLastError();
        $error[] = "<b>MySql response</b> : " . mysql_error();
        return $error;
    }

    /**
     * Retourne la version de mysql
     *
     * @return String
     */
    public function getVersion() {
        $version = @mysql_get_server_info($this->connId);
        $version = ($version !== false) ? $version : "?";
        return $version;
    }

    public function update($table, $values, $where, $orderby = array(), $limit = false) {
        $this->lastSqlCommand = "UPDATE";
        parent::update($table, $values, $where, $orderby, $limit);
    }

    public function select($table, $values, $where = array(), $orderby = array(), $limit = false) {
        $this->lastSqlCommand = "SELECT";
        parent::select($table, $values, $where, $orderby, $limit);
    }

    public function insert($table, $keys, $values) {
        $this->lastSqlCommand = "INSERT";
        parent::insert($table, $keys, $values);
    }

    public function delete($table, $where = array(), $like = array(), $limit = false) {
        $this->lastSqlCommand = "DELETE";
        parent::delete($table, $where, $like, $limit);
    }

}

?>