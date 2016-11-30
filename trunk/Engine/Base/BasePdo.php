<?php

namespace TREngine\Engine\Base;

use PDO;
use PDOException;
use PDOStatement;
use TREngine\Engine\Core\CoreLogger;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de transaction utilisant l'extension PHP Data Objects.
 * 
 * Supporte de nombreuse type base de données :
 * Firebird,
 * Informix,
 * Microsoft SQL Server (MSSQL),
 * MySQL,
 * Oracle Call Interface (OCI),
 * Open Database Connectivity (ODBC),
 * PostgreSQL (pgSQL),
 * SQLite.
 *
 * @author Sébastien Villemain
 */
class BasePdo extends BaseModel {

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    protected function canUse(): bool {
        $rslt = false;

        $driverName = $this->getTransactionHost();
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
            CoreLogger::addException("PDO driver not found: " . $driverName);
        }
        return $rslt;
    }

    /**
     * {@inheritDoc}
     */
    public function netConnect() {
        try {
            // Host = mysql:host=127.0.0.1
            $this->connId = new PDO($this->getTransactionHost(), $this->getTransactionUser(), $this->getTransactionPass());
        } catch (PDOException $ex) {
            CoreLogger::addException("PDO exception: " . $ex->getMessage());
            $this->connId = null;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function &netSelect(): bool {
        $rslt = false;

        if ($this->netConnected()) {
            $rslt = ($this->getPdo()->exec("USE " . $this->getDatabaseName()) !== false);
        }
        return $rslt;
    }

    /**
     * {@inheritDoc}
     */
    public function netDeconnect() {
        $this->connId = null;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $sql
     */
    public function query(string $sql = "") {
        $this->queries = $this->getPdo()->query($sql);

        if ($this->queries === false) {
            CoreLogger::addException("PDO query: " . $this->getPdoErrorMessage());
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function &fetchArray(): array {
        $values = array();
        $rslt = $this->getPdoResult();

        if ($rslt !== null) {
            $values = $rslt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $values;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $className
     * @return array
     */
    public function &fetchObject(string $className = null): array {
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

    /**
     * {@inheritDoc}
     *
     * @param mixed $query
     * @return bool
     */
    public function &freeResult($query = null): bool {
        if ($query !== null) {
            unset($query);
        }
        $success = true;
        return $success;
    }

    /**
     * {@inheritDoc}
     *
     * @return int
     */
    public function &affectedRows(): int {
        $value = -1;
        $rslt = $this->getPdoResult();

        if ($rslt !== null) {
            $value = $rslt->rowCount();
        }
        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &insertId(): string {
        return $this->getPdo()->lastInsertId();
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function &getLastError(): array {
        $error = parent::getLastError();
        $error[] = "<span class=\"text_bold\">Pdo response</span> : " . $this->getPdoErrorMessage();
        return $error;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function &getVersion(): string {
        // Exemple : 5.6.15-log
        $version = $this->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
        return $version;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function update(string $table, array $values, array $where, array $orderby = array(), string $limit = "") {
        parent::update($table, $values, $where, $orderby, $limit);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     * @param array $values
     * @param array $where
     * @param array $orderby
     * @param string $limit
     */
    public function select(string $table, array $values, array $where = array(), array $orderby = array(), string $limit = "") {
        parent::select($table, $values, $where, $orderby, $limit);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     * @param array $keys
     * @param array $values
     */
    public function insert(string $table, array $keys, array $values) {
        parent::insert($table, $keys, $values);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     * @param array $where
     * @param array $like
     * @param string $limit
     */
    public function delete(string $table, array $where = array(), array $like = array(), string $limit = "") {
        parent::delete($table, $where, $like, $limit);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $str
     * @return string
     */
    protected function converEscapeString(string $str): string {
        // Impossible d'utiliser $this->getPdo()->quote($str) car incompatible avec la méthode interne addQuote()
        return str_replace(array(
            '\\',
            "\0",
            "\n",
            "\r",
            "'",
            '"',
            "\x1a"), array(
            '\\\\',
            '\\0',
            '\\n',
            '\\r',
            "\\'",
            '\\"',
            '\\Z'), $str);
    }

    /**
     * Retourne la connexion PDO.
     *
     * @return PDO
     */
    private function &getPdo(): PDO {
        return $this->connId;
    }

    /**
     * Retourne le résultat de la dernière requête.
     *
     * @param mixed $query
     * @return PDOStatement
     */
    private function &getPdoResult($query = null): PDOStatement {
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
    private function &getPdoErrorMessage(): string {
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
