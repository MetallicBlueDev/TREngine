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
    }

    public function &fetchArray() {
        $rslt = array();

        if ($this->queries instanceof mysqli_result) {
            $rslt = $this->queries->fetch_array(MYSQLI_ASSOC);
        }
        return $rslt;
    }

    public function &fetchObject() {
        $rslt = null;

        if ($this->queries instanceof mysqli_result) {
            $rslt = $this->queries->fetch_object();
        }
        return $rslt;
    }

    public function &freeResult($querie) {
        $rslt = false;

        if ($querie instanceof mysqli_result) {
            $querie->free();
            $rslt = true;
        }
        return $rslt;
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
     * Retourne l'objet mysqli.
     *
     * @return mysqli
     */
    private function getMysqli() {
        return $this->connId;
    }

}
