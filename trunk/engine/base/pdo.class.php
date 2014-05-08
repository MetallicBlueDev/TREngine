<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Gestionnaire de transaction utilisant l'extension PHP Data Objects.
 * Supporte de nombreuse type base de données.
 *
 * @author Sébastien Villemain
 */
class Base_Pdo extends Base_Model {

    protected function canUse() {
        $rslt = false;

        $driverName = $this->getDatabaseHost();
        $pos = strpos($driverName, ":");

        if ($pos !== false) {
            $driverName = substr($driverName, 0, $pos);
        }

        foreach (PDO::getAvailableDrivers() as $availableDriverName) {
            if ($availableDriverName === $driverName) {
                $rslt = true;
                break;
            }
        }

        if (!$rslt) {
            Core_Logger::addException("PDO driver not found: " . $driverName);
        }
        return $rslt;
    }

    public function dbConnect() {
        try {
            // Host = mysql:host=127.0.0.1
            $this->connId = new PDO($this->getDatabaseHost(), $this->getDatabaseUser(), $this->getDatabasePass());
        } catch (PDOException $ex) {
            Core_Logger::addException("PDO exception: " . $ex->getMessage());
            $this->connId = null;
        }
    }

    public function &dbSelect() {
        $rslt = false;

        if ($this->dbConnected()) {
            $rslt = ($this->getPdo()->exec("USE " . $this->getDatabaseName()) !== false);
        }
        return $rslt;
    }

    public function dbDeconnect() {
        $this->connId = null;
    }

    public function query($sql) {
        $this->queries = $this->getPdo()->query($sql);

        if ($this->queries === false) {
            Core_Logger::addException("PDO query: " . $this->getPdoErrorMessage());
        }
    }

    public function &fetchArray() {
        $values = array();
        $rslt = $this->getPdoResult();

        if ($rslt !== null) {
            $values = $rslt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $values;
    }

    public function &fetchObject($className = null) {
        $values = array();
        $rslt = $this->getPdoResult();

        if ($rslt !== null) {
            if (empty($className)) {
                $values = $rslt->fetchAll(PDO::FETCH_CLASS);
            } else {
                $values = $rslt->fetchAll(PDO::FETCH_CLASS, $className);
            }
        }
        return $values;
    }

    public function &freeResult($query) {
        unset($query);
        $success = true;
        return $success;
    }

    public function &affectedRows() {
        $value = -1;
        $rslt = $this->getPdoResult();

        if ($rslt !== null) {
            $value = $rslt->rowCount();
        }
        return $value;
    }

    public function &insertId() {
        return $this->getPdo()->lastInsertId();
    }

    public function &getLastError() {
        $error = parent::getLastError();
        $error[] = "<b>Pdo response</b> : " . $this->getPdoErrorMessage();
        return $error;
    }

    public function &getVersion() {
        // Exemple : 5.6.15-log
        $version = $this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
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
        return $this->getPdo()->escape_string($str);
    }

    /**
     * Retourne la connexion PDO.
     *
     * @return \PDO
     */
    private function &getPdo() {
        return $this->connId;
    }

    /**
     * Retourne le résultat de la dernière requête.
     *
     * @param resource $query
     * @return \PDOStatement
     */
    private function &getPdoResult($query = null) {
        $object = null;

        if ($query === null) {
            $query = $this->queries;
        }

        if ($query instanceof PDOStatement) {
            $object = $query;
        }
        return $object;
    }

    /**
     * Retourne le dernier message d'erreur.
     *
     * @return string
     */
    private function &getPdoErrorMessage() {
        $message = "";
        $error = $this->getPdo()->errorInfo();

        if (count($error) >= 3) {
            $message = $error[2];
        } else {
            $message = implode(" // ", $error);
        }
        return $message;
    }

}
