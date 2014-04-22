<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Gestionnaire de base de données MySql.
 *
 * @author Sébastien Villemain
 */
class Base_Mysqli extends Base_Model {

    /**
     * Le type de la dernière commande.
     * SELECT, DELETE, UPDATE, INSERT, REPLACE.
     *
     * @var string
     */
    private $lastSqlCommand = "";

    public function dbConnect() {
        $this->connId = new mysqli($this->getDatabaseHost(), $this->getDatabaseUser(), $this->getDatabasePass());

        if ($this->getMysqli()->connect_error) {
            $this->connId = null;
        }
    }

    public function dbSelect() {
        $rslt = false;

        if ($this->connected()) {
            $rslt = $this->getMysqli()->select_db($this->getDatabaseName());
        }
        return $rslt;
    }

    public function dbDeconnect() {
        if ($this->connected()) {
            $this->getMysqli()->close();
        }

        $this->connId = null;
    }

    public function query($sql) {
        $this->queries = $this->getMysqli()->query($sql);
    }

    public function fetchArray() {
        $rslt = array();

        if ($this->queries instanceof mysqli_result) {
            $rslt = $this->queries->fetch_array(MYSQLI_ASSOC);
        }
        return $rslt;
    }

    public function fetchObject() {
        $rslt = null;

        if ($this->queries instanceof mysqli_result) {
            $rslt = $this->queries->fetch_object();
        }
        return $rslt;
    }

    public function freeResult($querie) {
        $rslt = false;

        if ($querie instanceof mysqli_result) {
            $querie->free();
            $rslt = true;
        }
        return $rslt;
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
     * Vérifie que le module mysql est chargé.
     *
     * @return boolean
     */
    public function test() {
        return (function_exists("mysql_connect"));
    }

    /**
     * Retourne les dernières erreurs
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
     * @return string
     */
    public function getVersion() {
        $version = mysql_get_server_info($this->connId);
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

    /**
     * Retourne le bon espacement dans une string.
     *
     * @param string $str
     * @return string
     */
    protected function &converEscapeString($str) {
        if (function_exists("mysql_real_escape_string") && is_resource($this->connId)) {
            $str = mysql_real_escape_string($str, $this->connId);
        } else if (function_exists("mysql_escape_string")) {// WARNING: DEPRECATED
            $str = mysql_escape_string($str);
        } else {
            $str = parent::converEscapeString($str);
        }
        return $str;
    }

    /**
     * Retourne l'objet mysqli.
     *
     * @return mysqli
     */
    private function getMysqli() {
        return $this->connId;
    }

}
