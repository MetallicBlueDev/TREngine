<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Gestionnaire de base de données MySql (nouvelle version).
 *
 * @author Sébastien Villemain
 */
class Base_Mysqli extends Base_Model {

    public function dbConnect() {
        $this->connId = new mysqli($this->getDatabaseHost(), $this->getDatabaseUser(), $this->getDatabasePass());

        if ($this->getMysqli()->connect_error) {
            Core_Logger::addException("MySqli connect_error: " . $this->getMysqli()->connect_error);
            $this->connId = null;
        }
    }

    public function &dbSelect() {
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

        if (!$this->queries) {
            Core_Logger::addException("MySqli query: " . $this->getMysqli()->error);
        }
    }

    public function &fetchArray() {
        $values = array();
        $rslt = $this->getMysqliResult();

        if ($rslt !== null) {
            $nbRows = $rslt->num_rows;

            for ($i = 0; $i < $nbRows; $i++) {
                $values[] = $rslt->fetch_array(MYSQLI_ASSOC);
            }
        }
        return $values;
    }

    public function &fetchObject() {
        $values = null;
        $rslt = $this->getMysqliResult();

        if ($rslt !== null) {
            $nbRows = $rslt->num_rows;

            for ($i = 0; $i < $nbRows; $i++) {
                $values[] = $rslt->fetch_object();
            }
        }
        return $values;
    }

    public function &freeResult($query) {
        $success = false;
        $rslt = $this->getMysqliResult($query);

        if ($rslt !== null) {
            $rslt->free();
            $success = true;
        }
        return $success;
    }

    public function &affectedRows() {
        return $this->getMysqli()->affected_rows;
    }

    public function &insertId() {
        return $this->getMysqli()->insert_id;
    }

    public function test() {
        // Vérifie que le module mysql est chargé.
        return function_exists("mysqli_connect");
    }

    public function &getLastError() {
        $error = parent::getLastError();
        $error[] = "<b>MySqli response</b> : " . $this->getMysqli()->error;
        return $error;
    }

    public function &getVersion() {
        return $this->getMysqli()->server_info;
    }

    public function update($table, array $values, array $where, array $orderby = array(), $limit = "") {
        parent::update($table, $values, $where, $orderby, $limit);
    }

    public function select($table, array $values, array $where = array(), array $orderby = array(), $limit = "") {
        parent::select($table, $values, $where, $orderby, $limit);
    }

    public function insert($table, array $keys, array $values) {
        parent::insert($table, $keys, $values);
    }

    public function delete($table, array $where = array(), array $like = array(), $limit = "") {
        parent::delete($table, $where, $like, $limit);
    }

    protected function converEscapeString($str) {
        return $this->getMysqli()->escape_string($str);
    }

    /**
     * Retourne la connexion mysqli.
     *
     * @return mysqli
     */
    private function &getMysqli() {
        return $this->connId;
    }

    /**
     * Retourne le résultat de la dernière requête.
     *
     * @param resource $query
     * @return \mysqli_result
     */
    private function &getMysqliResult($query = null) {
        $object = null;

        if ($query === null) {
            $query = $this->queries;
        }

        if ($query instanceof mysqli_result) {
            $object = $query;
        }
        return $object;
    }

}
