<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Gestionnaire de transaction utilisant l'extension MySqli (nouvelle version de MySql).
 * Ne supporte que les bases de données MySql.
 *
 * @author Sébastien Villemain
 */
class Base_Mysqli extends Base_Model {

    protected function canUse() {
        $rslt = function_exists("mysqli_connect");

        if (!$rslt) {
            Core_Logger::addException("MySqli function not found");
        }
        return $rslt;
    }

    public function netConnect() {
        $this->connId = new mysqli($this->getTransactionHost(), $this->getTransactionUser(), $this->getTransactionPass());

        if ($this->getMysqli()->connect_error) {
            Core_Logger::addException("MySqli connect_error: " . $this->getMysqli()->connect_error);
            $this->connId = null;
        }
    }

    public function &netSelect() {
        $rslt = false;

        if ($this->netConnected()) {
            $rslt = $this->getMysqli()->select_db($this->getDatabaseName());
        }
        return $rslt;
    }

    public function netDeconnect() {
        if ($this->netConnected()) {
            $this->getMysqli()->close();
        }

        $this->connId = null;
    }

    public function query($sql) {
        $this->queries = $this->getMysqli()->query($sql);

        if ($this->queries === false) {
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

    public function &fetchObject($className = null) {
        $values = array();
        $rslt = $this->getMysqliResult();

        if ($rslt !== null) {
            $nbRows = $rslt->num_rows;

            // Vérification avant de rentrer dans la boucle (optimisation)
            if (empty($className)) {
                for ($i = 0; $i < $nbRows; $i++) {
                    $values[] = $rslt->fetch_object();
                }
            } else {
                for ($i = 0; $i < $nbRows; $i++) {
                    $values[] = $rslt->fetch_object($className);
                }
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
        $nbRows = $this->getMysqli()->affected_rows;

        if ($nbRows < 0) {
            $rslt = $this->getMysqliResult();

            if ($rslt !== null) {
                $nbRows = $rslt->num_rows;
            }
        }
        return $nbRows;
    }

    public function &insertId() {
        return $this->getMysqli()->insert_id;
    }

    public function &getLastError() {
        $error = parent::getLastError();
        $error[] = "<b>MySqli response</b> : " . $this->getMysqli()->error;
        return $error;
    }

    public function &getVersion() {
        // Exemple : 5.6.15-log
        $version = $this->getMysqli()->server_info;
        return $version;
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
     * @return \mysqli
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
