<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Gestionnaire de transaction utilisant l'extension PHP Data Objects.
 * Supporte de nombreuse type base de données.
 *
 * @author Sébastien Villemain
 */
class Base_Pdo extends Base_Model {

    public function dbConnect() {
        try {
            // Host = mysql:dbname=testdb;host=127.0.0.1
            $this->connId = new PDO($this->getDatabaseHost(), $this->getDatabaseUser(), $this->getDatabasePass());
        } catch (PDOException $ex) {
            Core_Logger::addException("PDO exception: " . $ex->getMessage());
            $this->connId = null;
        }
    }

    public function &dbSelect() {
        $rslt = false;

        if ($this->connected()) {
            $rslt = $this->getPdo()->select_db($this->getDatabaseName());
        }
        return $rslt;
    }

    public function dbDeconnect() {
        if ($this->connected()) {
            $this->getPdo()->close();
        }

        $this->connId = null;
    }

    public function query($sql) {
        $this->queries = $this->getPdo()->query($sql);

        if (!$this->queries) {
            Core_Logger::addException("MySqli query: " . $this->getPdo()->error);
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
        return $this->getPdo()->affected_rows;
    }

    public function &insertId() {
        return $this->getPdo()->insert_id;
    }

    public function test() {
        // Vérifie que le module mysql est chargé.
        return function_exists("mysqli_connect");
    }

    public function &getLastError() {
        $error = parent::getLastError();
        $error[] = "<b>MySqli response</b> : " . $this->getPdo()->error;
        return $error;
    }

    public function &getVersion() {
        return $this->getPdo()->server_info;
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
        return $this->getPdo()->escape_string($str);
    }

    /**
     * Retourne la connexion PDO.
     *
     * @return PDO
     */
    private function &getPdo() {
        return $this->connId;
    }

}
